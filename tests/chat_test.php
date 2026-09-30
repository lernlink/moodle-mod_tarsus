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
use mod_tarsuszero\external\get_activity_feed;
use mod_tarsuszero\output\learner_chat;

/**
 * Tests for the Learner RAG chat UI external APIs & output classes.
 *
 * @package    mod_tarsuszero
 * @covers     \mod_tarsuszero\external\send_chat_message
 * @covers     \mod_tarsuszero\external\get_activity_feed
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
        // Every mode is on by default.
        $this->assertTrue($exported['askenabled']);
        $this->assertTrue($exported['testenabled']);
        $this->assertTrue($exported['buildenabled']);
        $this->assertSame('build,test,ask', $exported['modes']);
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

        // With nothing included there is no scope, hence no feed to
        // read — and no call to the brain.
        $feed = get_activity_feed::execute((int) $tz->cmid);
        $this->assertEquals('success', $feed['status']);
        $this->assertEmpty($feed['items']);

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

        // The activity's didactic defaults travel with the request.
        $this->assertStringContainsString('[style: 3, dqr: 6]', $result['answer']);

        // Informational widgets are relayed as sent, rendered like answers.
        $this->assertCount(1, $result['messages']);
        $this->assertStringContainsString('<strong>Mock welcome</strong>', $result['messages'][0]);

        // The feed is read back from the brain, oldest first, all modes
        // interleaved and rendered like fresh answers: the welcome message,
        // a chat exchange and the practice questions in original order.
        $feed = get_activity_feed::execute((int) $tz->cmid);
        $this->assertEquals('success', $feed['status']);
        $this->assertSame(['message', 'exchange', 'practice', 'practice', 'stack', 'complete'],
            array_column($feed['items'], 'kind'));
        $this->assertStringContainsString('<strong>completed</strong>', $feed['items'][5]['text']);
        // A stored learning unit renders its slides.
        $this->assertSame('mock-stack-0', $feed['items'][4]['id']);
        $this->assertStringContainsString('<strong>stored</strong>', $feed['items'][4]['slides'][0]['text']);
        $this->assertStringContainsString('<strong>Mock welcome</strong>', $feed['items'][0]['text']);
        $this->assertEquals('Mock question?', $feed['items'][1]['query']);
        $this->assertStringContainsString('Mock stored answer', $feed['items'][1]['answer']);
    }

    /**
     * The teacher's didactic settings for the activity reach the brain.
     */
    public function test_didactic_settings_travel_with_the_request(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setAdminUser();
        $this->make_included_page($course, $tz);

        // The teacher tunes this activity away from the defaults.
        $DB->set_field('tarsuszero', 'conversationstyle', 4, ['id' => $tz->id]);
        $DB->set_field('tarsuszero', 'dqr', 7, ['id' => $tz->id]);

        $this->setUser($student);
        set_config('mockmode', '1', 'local_tarsus');

        $result = send_chat_message::execute((int) $tz->cmid, 'What is biology?');

        $this->assertEquals('success', $result['status']);
        $this->assertStringContainsString('[style: 4, dqr: 7]', $result['answer']);
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
     * A switched-off chat mode refuses before any API call.
     */
    public function test_send_refused_when_chat_mode_is_off(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setAdminUser();
        $this->make_included_page($course, $tz);
        $DB->set_field('tarsuszero', 'askenabled', 0, ['id' => $tz->id]);

        $this->setUser($student);
        set_config('mockmode', '1', 'local_tarsus');

        $result = send_chat_message::execute((int) $tz->cmid, 'What is biology?');

        $this->assertEquals('disabled', $result['status']);
        $this->assertEquals(get_string('chat_modedisabled', 'mod_tarsuszero'), $result['error']);
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