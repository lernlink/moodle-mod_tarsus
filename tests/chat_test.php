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

        // Initially history should be empty.
        $history = get_conversation_history::execute((int) $tz->cmid);
        $this->assertEmpty($history);

        // Send a mock query which should pass pipeline.
        // We configure local_tarsus mockmode config to ensure mock client is used.
        set_config('mockmode', '1', 'local_tarsus');

        $result = send_chat_message::execute((int) $tz->cmid, 'What is biology?');

        $this->assertEquals('success', $result['status']);
        $this->assertNotEmpty($result['answer']);
        $this->assertNotEmpty($result['sources']);
        $this->assertEmpty($result['error']);

        // Verify history contains the message.
        $history = get_conversation_history::execute((int) $tz->cmid);
        $this->assertCount(1, $history);
        $this->assertEquals('What is biology?', $history[0]['query']);
        $this->assertEquals($result['answer'], $history[0]['answer']);
        $this->assertCount(2, $history[0]['sources']); // mock_client returns 2 sources
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
        $this->setUser($student);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        // Turn on the local_tarsus killswitch.
        set_config('killswitch', '1', 'local_tarsus');

        $result = send_chat_message::execute((int) $tz->cmid, 'Who is Gregor Mendel?');

        $this->assertEquals('blocked', $result['status']);
        $this->assertEmpty($result['answer']);
        $this->assertEquals(get_string('guardrail_blocked_killswitch', 'mod_tarsuszero'), $result['error']);
    }
}