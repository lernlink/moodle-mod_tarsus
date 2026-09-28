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

use mod_tarsuszero\external\get_practice_widget;
use mod_tarsuszero\external\submit_practice_answer;

/**
 * Tests for the Test knowledge (practice) external functions.
 *
 * @package    mod_tarsuszero
 * @covers     \mod_tarsuszero\external\get_practice_widget
 * @covers     \mod_tarsuszero\external\get_activity_feed
 * @covers     \mod_tarsuszero\external\submit_practice_answer
 * @covers     \mod_tarsuszero\local\conversation
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class practice_test extends \advanced_testcase {

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
     * @return array{0: \stdClass, 1: \stdClass} The activity record and the student.
     */
    private function make_ready_activity(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

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
        $DB->insert_record('tarsuszero_inclusions', (object) [
            'tarsuszeroid' => $tz->id,
            'resourceid' => $resourceid,
            'timecreated' => time(),
        ]);

        $this->setUser($student);
        set_config('mockmode', '1', 'local_tarsus');

        return [$tz, $student];
    }

    /**
     * Generating a question returns the widget without correctness data and
     * carries the activity's practice settings to the brain.
     */
    public function test_get_practice_widget_returns_a_question(): void {
        [$tz] = $this->make_ready_activity();

        $result = get_practice_widget::execute((int) $tz->cmid);

        $this->assertEquals('success', $result['status']);
        // One click renders as many cards as the brain generated.
        $this->assertCount(2, $result['widgets']);
        $widget = $result['widgets'][0];
        $this->assertSame('mock-widget-1', $widget['id']);
        $this->assertSame('singleChoiceQuestionWidget', $widget['type']);
        $this->assertSame('trueFalseQuestionWidget', $result['widgets'][1]['type']);

        // The default practice settings echo back through the mock.
        $this->assertStringContainsString('[tax: 1,2,3]', $widget['question']);
        $this->assertStringContainsString('[types: 1:2 2:3 3:1]', $widget['question']);

        $this->assertCount(4, $widget['choices']);
        $this->assertSame('Alpha', $widget['choices'][0]['value']);
        $this->assertSame('Alpha', $widget['choices'][0]['label']);
        // The generation response must not reveal the verdict.
        $this->assertArrayNotHasKey('iscorrect', $widget['choices'][0]);

        $this->assertSame(6.0, $result['progress']['totalxp']);
    }

    /**
     * The teacher's practice settings travel with the generation request.
     */
    public function test_practice_settings_travel_with_the_request(): void {
        global $DB;
        [$tz] = $this->make_ready_activity();

        $DB->set_field('tarsuszero', 'taxonomylevels', '2', ['id' => $tz->id]);
        $DB->set_field('tarsuszero', 'questiontypes', '{"3":["openQuestionWidget"]}', ['id' => $tz->id]);

        $result = get_practice_widget::execute((int) $tz->cmid);

        $this->assertEquals('success', $result['status']);
        $this->assertStringContainsString('[tax: 2]', $result['widgets'][0]['question']);
        // The brain requires every grade to carry a type, so the grades the
        // teacher left empty travel with their defaults (2 and 3 types).
        $this->assertStringContainsString('[types: 1:2 2:3 3:1]', $result['widgets'][0]['question']);
    }

    /**
     * Reloading the page reopens the feed: answered questions frozen with
     * their verdicts, unanswered ones still answerable, figures included.
     */
    public function test_practice_state_reopens_the_feed(): void {
        [$tz] = $this->make_ready_activity();

        $result = \mod_tarsuszero\external\get_activity_feed::execute((int) $tz->cmid);

        $this->assertEquals('success', $result['status']);
        $this->assertCount(4, $result['items']);

        [, , $answered, $open] = $result['items'];
        $this->assertTrue($answered['answered']);
        $this->assertTrue($answered['correct']);
        $this->assertSame('Alpha', $answered['useranswer']);
        $this->assertStringContainsString('<strong>Mock stored clarification.</strong>', $answered['feedback']);

        $this->assertFalse($open['answered']);
        $this->assertSame('trueFalseQuestionWidget', $open['type']);
        // The true/false values render with localized labels.
        $this->assertSame('true', $open['choices'][0]['value']);
        $this->assertSame(get_string('choice_true', 'mod_tarsuszero'), $open['choices'][0]['label']);

        $this->assertSame(60.0, $result['progress']['practiceprogress']);
        $this->assertSame(9.0, $result['progress']['earnedxp']);
    }

    /**
     * With nothing included the feed is empty without calling the brain.
     */
    public function test_practice_state_without_scope_is_empty(): void {
        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        set_config('mockmode', '1', 'local_tarsus');

        $result = \mod_tarsuszero\external\get_activity_feed::execute((int) $tz->cmid);
        $this->assertEquals('success', $result['status']);
        $this->assertSame([], $result['items']);
    }

    /**
     * With nothing included the practice refuses locally, like the chat.
     */
    public function test_practice_without_included_material_is_refused(): void {
        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        set_config('mockmode', '1', 'local_tarsus');

        $result = get_practice_widget::execute((int) $tz->cmid);
        $this->assertEquals('noscope', $result['status']);

        $result = submit_practice_answer::execute((int) $tz->cmid, 'w1', 'singleChoiceQuestionWidget', 'Alpha');
        $this->assertEquals('noscope', $result['status']);
    }

    /**
     * A correct answer is judged, clarified and paid out.
     */
    public function test_submit_correct_answer(): void {
        [$tz] = $this->make_ready_activity();

        $result = submit_practice_answer::execute(
            (int) $tz->cmid, 'mock-widget-1', 'singleChoiceQuestionWidget', 'Alpha');

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['hasverdict']);
        $this->assertTrue($result['correct']);
        // Feedback arrives as rendered Markdown.
        $this->assertStringContainsString('<strong>Mock clarification:</strong>', $result['feedback']);
        $this->assertTrue($result['choices'][0]['iscorrect']);
        $this->assertSame(1.0, $result['progress']['earnedxp']);
    }

    /**
     * A wrong answer is judged wrong, with the correct choice marked.
     */
    public function test_submit_wrong_answer(): void {
        [$tz] = $this->make_ready_activity();

        $result = submit_practice_answer::execute(
            (int) $tz->cmid, 'mock-widget-1', 'singleChoiceQuestionWidget', 'Beta');

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['hasverdict']);
        $this->assertFalse($result['correct']);
        $this->assertTrue($result['choices'][0]['iscorrect']);
        $this->assertSame(0.0, $result['progress']['earnedxp']);
    }

    /**
     * A rule-of-three question is answered per variable and judged per
     * variable; an incomplete answer set is rejected before any API call.
     */
    public function test_submit_rule_of_three_answers(): void {
        [$tz] = $this->make_ready_activity();

        $result = submit_practice_answer::execute(
            (int) $tz->cmid, 'mock-widget-3', 'crossMultiplicationQuestionWidget', '',
            [['variable' => 'A_E', 'answer' => 'Alpha'], ['variable' => 'A_D', 'answer' => '42']]);

        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['hasverdict']);
        $this->assertFalse($result['correct']);
        $this->assertCount(2, $result['answers']);
        $this->assertSame('A_E', $result['answers'][0]['variable']);
        $this->assertTrue($result['answers'][0]['iscorrect']);
        $this->assertFalse($result['answers'][1]['iscorrect']);
        $this->assertStringContainsString('Alpha', $result['answers'][1]['clarification']);

        $this->expectException(\moodle_exception::class);
        submit_practice_answer::execute(
            (int) $tz->cmid, 'mock-widget-3', 'crossMultiplicationQuestionWidget', '',
            [['variable' => 'A_E', 'answer' => 'Alpha'], ['variable' => 'A_D', 'answer' => '   ']]);
    }

    /**
     * An unsupported widget type is rejected before any API call.
     */
    public function test_submit_with_unsupported_type_is_rejected(): void {
        [$tz] = $this->make_ready_activity();

        $this->expectException(\moodle_exception::class);
        submit_practice_answer::execute((int) $tz->cmid, 'mock-widget-1', 'bogusWidget', 'Alpha');
    }

    /**
     * The guardrail killswitch blocks practice like it blocks the chat.
     */
    public function test_practice_with_killswitch_engaged(): void {
        [$tz] = $this->make_ready_activity();
        set_config('killswitch', '1', 'local_tarsus');

        $result = get_practice_widget::execute((int) $tz->cmid);
        $this->assertEquals('blocked', $result['status']);
        $this->assertEquals(get_string('guardrail_blocked_killswitch', 'mod_tarsuszero'), $result['error']);
    }
}
