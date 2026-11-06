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

namespace mod_tarsus;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use mod_tarsus\external\get_activity_feed;
use mod_tarsus\external\get_learn_widget;
use mod_tarsus\external\send_chat_message;
use mod_tarsus\local\didactics;

/**
 * Tests for the interaction type and the interactive web cards it brings.
 *
 * @package    mod_tarsus
 * @covers     \mod_tarsus\external\send_chat_message
 * @covers     \mod_tarsus\external\get_learn_widget
 * @covers     \mod_tarsus\external\get_activity_feed
 * @covers     \mod_tarsus\external\practice_labels
 * @covers     \mod_tarsus\local\didactics
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class web_card_test extends \advanced_testcase {

    /**
     * Set up tests.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Course, activity and an enrolled student with included material.
     *
     * @param string $interactiontype The activity's interaction type.
     * @return \stdClass The activity record.
     */
    private function make_ready_activity(string $interactiontype): \stdClass {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsus', ['course' => $course->id]);
        $DB->set_field('tarsus', 'interactiontype', $interactiontype, ['id' => $tz->id]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setAdminUser();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => '<p>Body</p>',
            'contentformat' => FORMAT_HTML,
        ]);
        $cm = get_fast_modinfo($course->id)->get_cm($page->cmid);
        $resourceid = (new \local_tarsus\indexer())->index_cm($cm)[0];
        (new \local_tarsus\index\repository())->mark_uploaded($resourceid, 'hash-1');
        (new \local_tarsus\index\repository())->mark_indexed($resourceid, 'bit-1', 'hash-1');
        $DB->insert_record('tarsus_inclusions', (object) [
            'tarsusid' => $tz->id,
            'resourceid' => $resourceid,
            'timecreated' => time(),
        ]);

        $this->setUser($student);
        set_config('mockmode', '1', 'local_tarsus');

        return $tz;
    }

    /**
     * The stored value is normalised; unknown values fall back to the
     * brain's own default.
     */
    public function test_interaction_type_is_normalised(): void {
        $this->assertSame('trainer-driven', didactics::interaction_type(null));
        $this->assertSame('trainer-driven', didactics::interaction_type('anything'));
        $this->assertSame('ai-driven', didactics::interaction_type(' AI-Driven '));
        $this->assertSame('trainer-driven', didactics::interaction_type('trainer-driven'));
    }

    /**
     * A new activity is trainer-driven, and the chat answers with text.
     */
    public function test_trainer_driven_chat_answers_with_text(): void {
        $tz = $this->make_ready_activity('trainer-driven');

        $result = send_chat_message::execute((int) $tz->cmid, 'What is biology?');
        $result = external_api::clean_returnvalue(send_chat_message::execute_returns(), $result);

        $this->assertEquals('success', $result['status']);
        $this->assertStringContainsString('Mock chat answer', $result['answer']);
        $this->assertSame(['answer'], array_column($result['items'], 'kind'));
        $this->assertSame($result['answer'], $result['items'][0]['html']);
    }

    /**
     * Agent-driven, the chat answers with web cards: every one the brain
     * sent, the document untouched by the text filters, and no text answer.
     */
    public function test_agent_driven_chat_answers_with_web_cards(): void {
        $tz = $this->make_ready_activity('ai-driven');

        $result = send_chat_message::execute((int) $tz->cmid, 'Show me the steps.');
        $result = external_api::clean_returnvalue(send_chat_message::execute_returns(), $result);

        $this->assertEquals('success', $result['status']);
        $this->assertSame('', $result['answer']);
        $this->assertSame(['web', 'web'], array_column($result['items'], 'kind'));
        $this->assertSame('mock-web-answer-1', $result['items'][0]['id']);
        $this->assertStringStartsWith('<!DOCTYPE html>', $result['items'][0]['html']);
        $this->assertStringContainsString('Mock web answer for: Show me the steps.', $result['items'][0]['html']);
        $this->assertStringContainsString('second card', $result['items'][1]['html']);
    }

    /**
     * Agent-driven, a learning step may arrive as a web card, labelled like
     * a slide and carrying the document.
     */
    public function test_agent_driven_learning_step_is_a_web_card(): void {
        $tz = $this->make_ready_activity('ai-driven');

        $result = get_learn_widget::execute((int) $tz->cmid);
        $result = external_api::clean_returnvalue(get_learn_widget::execute_returns(), $result);

        $this->assertEquals('success', $result['status']);
        $web = end($result['items']);
        $this->assertSame('web', $web['kind']);
        $this->assertSame('learn', $web['origin']);
        $this->assertSame('mock-web-step', $web['id']);
        $this->assertSame('Mock web step', $web['title']);
        $this->assertStringContainsString('<strong>interactive</strong>', $web['text']);
        $this->assertNotSame('', $web['taxonomy']);
        $this->assertStringStartsWith('<!DOCTYPE html>', $web['code']);

        // Trainer-driven, the same call brings no web card.
        $tz = $this->make_ready_activity('trainer-driven');
        $result = get_learn_widget::execute((int) $tz->cmid);
        $this->assertNotContains('web', array_column($result['items'], 'kind'));
    }

    /**
     * A stored web card comes back in the feed with its question and document.
     */
    public function test_feed_replays_web_cards(): void {
        $tz = $this->make_ready_activity('ai-driven');

        $feed = get_activity_feed::execute((int) $tz->cmid);
        $feed = external_api::clean_returnvalue(get_activity_feed::execute_returns(), $feed);

        $this->assertEquals('success', $feed['status']);
        $kinds = array_column($feed['items'], 'kind');
        $this->assertContains('web', $kinds);
        $web = $feed['items'][array_search('web', $kinds, true)];
        $this->assertSame('ask', $web['origin']);
        $this->assertSame('Mock web question?', $web['query']);
        $this->assertStringContainsString('Mock stored web answer', $web['code']);
    }
}
