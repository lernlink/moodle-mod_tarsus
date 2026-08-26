<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

namespace mod_tarsuszero;

defined('MOODLE_INTERNAL') || die();

use mod_tarsuszero\external\send_chat_message;
use mod_tarsuszero\external\get_conversation_history;
use mod_tarsuszero\output\learner_chat;

/**
 * Tests for the Learner RAG chat UI external APIs & output classes.
 *
 * @package    mod_tarsuszero
 * @covers     \mod_tarsuszero\external\send_chat_message
 * @covers     \mod_tarsuszero\external\get_conversation_history
 * @covers     \mod_tarsuszero\output\learner_chat
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chat_test extends \advanced_testcase {

    /**
     * Set up tests.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Create a page in the course, mark it indexed and include it in the activity.
     *
     * @param \stdClass $course The course.
     * @param \stdClass $tz     The tarsuszero activity record.
     * @param string    $content The page body.
     * @return string The included resource id.
     */
    private function make_included_page(\stdClass $course, \stdClass $tz, string $content = '<p>Body</p>'): string {
        global $DB;

        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => $content,
            'contentformat' => FORMAT_HTML,
        ]);
        $cm = get_fast_modinfo($course->id)->get_cm($page->cmid);

        $resourceid = (new \local_tarsus\indexer())->index_cm($cm)[0];
        (new \local_tarsus\index\repository())->mark_uploaded($resourceid, 'hash-1');
        (new \local_tarsus\index\repository())->mark_indexed($resourceid, 'bit-1', 'hash-1');

        $DB->insert_record('tarsuszero_inclusions', (object) [
            'tarsuszeroid' => $tz->id,
            'resourceid' => $resourceid,
            'timecreated' => time(),
        ]);

        return $resourceid;
    }

    /**
     * Test learner_chat export render details.
     */
    public function test_learner_chat_export(): void {
        global $PAGE;
        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        $chat = new learner_chat((int) $tz->cmid);
        $exported = $chat->export_for_template($PAGE->get_renderer('core'));

        $this->assertEquals($tz->cmid, $exported['cmid']);
        $this->assertEquals('tarsus-chat-box-' . $tz->cmid, $exported['containerid']);
    }

    /**
     * Test send chat messages and history retrieval logic.
     */
    public function test_send_and_get_chat_history(): void {
        global $DB, $USER;

        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);

        // Enrol the user to circumvent capability blocks.
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        set_config('mockmode', '1', 'local_tarsus');

        // With nothing included there is no scope, hence no conversation to
        // read — and no call to the brain.
        $history = get_conversation_history::execute((int) $tz->cmid);
        $this->assertEmpty($history);

        // Give the assistant something to answer from: an indexed, included page.
        $this->setAdminUser();
        $resourceid = $this->make_included_page($course, $tz);
        $this->setUser($student);

        $result = send_chat_message::execute((int) $tz->cmid, 'What is biology?');

        $this->assertEquals('success', $result['status']);
        $this->assertNotEmpty($result['answer']);
        $this->assertEmpty($result['error']);

        // The brain speaks Markdown; the client must receive rendered HTML.
        $this->assertStringContainsString('<p>', $result['answer']);
        $this->assertStringContainsString('Mock chat answer', $result['answer']);

        // The mock echoes its scope: exactly the included resource was
        // offered to the brain.
        $this->assertStringContainsString('[scope: ' . $resourceid . ']', $result['answer']);

        // Informational widgets are relayed as sent, rendered like answers.
        $this->assertCount(1, $result['messages']);
        $this->assertStringContainsString('<strong>Mock welcome</strong>', $result['messages'][0]);

        // History is read back from the brain, oldest first, rendered like
        // fresh answers: an assistant-only welcome turn, then an exchange.
        $history = get_conversation_history::execute((int) $tz->cmid);
        $this->assertCount(2, $history);
        $this->assertSame('', $history[0]['query']);
        $this->assertStringContainsString('<strong>Mock welcome</strong>', $history[0]['answer']);
        $this->assertEquals('Mock question?', $history[1]['query']);
        $this->assertStringContainsString('Mock stored answer', $history[1]['answer']);
        $this->assertCount(0, $history[1]['sources']);
    }

    /**
     * With nothing included the chat refuses locally: an empty restriction
     * list would make the shared brain search everything.
     */
    public function test_send_without_included_material_is_refused(): void {
        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        set_config('mockmode', '1', 'local_tarsus');

        $result = send_chat_message::execute((int) $tz->cmid, 'What is biology?');

        $this->assertEquals('noscope', $result['status']);
        $this->assertEmpty($result['answer']);
        $this->assertEquals(get_string('chat_noscope', 'mod_tarsuszero'), $result['error']);
    }

    /**
     * An inclusion whose resource is no longer indexed drops out of scope.
     */
    public function test_stale_inclusion_is_filtered_at_read_time(): void {
        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setAdminUser();
        $resourceid = $this->make_included_page($course, $tz);

        // The resource leaves the brain behind the inclusion's back
        // (e.g. an observer untracked it): the row is gone, the choice stays.
        (new \local_tarsus\index\repository())->delete($resourceid);

        $this->setUser($student);
        set_config('mockmode', '1', 'local_tarsus');

        $result = send_chat_message::execute((int) $tz->cmid, 'What is biology?');

        $this->assertEquals('noscope', $result['status']);
    }

    /**
     * Test sending empty/blank message throws exception.
     */
    public function test_send_empty_query(): void {
        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->expectException(\moodle_exception::class);
        send_chat_message::execute((int) $tz->cmid, '   ');
    }

    /**
     * Test that guardrail failure flows trigger descriptive safe blocks.
     */
    public function test_send_with_killswitch_engaged(): void {
        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        // Scope exists, so the request reaches the guardrails.
        $this->setAdminUser();
        $this->make_included_page($course, $tz);
        $this->setUser($student);

        // Turn on the local_tarsus killswitch.
        set_config('killswitch', '1', 'local_tarsus');

        $result = send_chat_message::execute((int) $tz->cmid, 'Who is Gregor Mendel?');

        $this->assertEquals('blocked', $result['status']);
        $this->assertEmpty($result['answer']);
        $this->assertEquals(get_string('guardrail_blocked_killswitch', 'mod_tarsuszero'), $result['error']);
    }
}