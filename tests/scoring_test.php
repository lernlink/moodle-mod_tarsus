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

use mod_tarsus\local\scoring;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Grades and completion from the brain's figures.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_tarsus\local\scoring
 * @covers     \mod_tarsus\completion\custom_completion
 */
final class scoring_test extends \advanced_testcase {

    /** @var array The figures the brain reported in a live session. */
    private const FIGURES = [
        'earnedxp' => 1.48, 'totalxp' => 9.0, 'earnedunits' => 9.0, 'totalunits' => 9.0,
        'practiceprogress' => 77.78, 'learnprogress' => 100.0, 'progress' => 88.89,
    ];

    /**
     * An activity with the given modes, in a course with completion on.
     *
     * @param array $fields Extra tarsus fields.
     * @return array{course: \stdClass, tz: \stdClass, activity: \stdClass, user: \stdClass}
     */
    private function make(array $fields = []): array {
        global $DB;
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $tz = $this->getDataGenerator()->create_module('tarsus', ['course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC] + $fields);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        return ['course' => $course, 'tz' => $tz, 'activity' => $DB->get_record('tarsus', ['id' => $tz->id]), 'user' => $user];
    }

    /**
     * Test knowledge alone: the share of experience points.
     */
    public function test_grade_test_only(): void {
        $this->resetAfterTest(true);
        $data = $this->make(['testenabled' => 1, 'buildenabled' => 0]);
        $this->assertSame(16.44, scoring::grade($data['activity'], (object) self::FIGURES));
        $this->assertSame(77.78, scoring::progress($data['activity'], (object) self::FIGURES));
    }

    /**
     * Build knowledge alone: the share of learning units.
     */
    public function test_grade_build_only(): void {
        $this->resetAfterTest(true);
        $data = $this->make(['testenabled' => 0, 'buildenabled' => 1]);
        $this->assertSame(100.0, scoring::grade($data['activity'], (object) self::FIGURES));
        $this->assertSame(100.0, scoring::progress($data['activity'], (object) self::FIGURES));
    }

    /**
     * Both: the mean of the two shares; the brain's overall progress.
     */
    public function test_grade_both_modes(): void {
        $this->resetAfterTest(true);
        $data = $this->make(['testenabled' => 1, 'buildenabled' => 1]);
        $this->assertSame(58.22, scoring::grade($data['activity'], (object) self::FIGURES));
        $this->assertSame(88.89, scoring::progress($data['activity'], (object) self::FIGURES));
    }

    /**
     * Chat alone carries no grade; nothing earnable yet means 0.
     */
    public function test_grade_edges(): void {
        $this->resetAfterTest(true);
        $askonly = $this->make(['testenabled' => 0, 'buildenabled' => 0, 'askenabled' => 1]);
        $this->assertNull(scoring::grade($askonly['activity'], (object) self::FIGURES));
        $fresh = $this->make(['testenabled' => 1, 'buildenabled' => 1]);
        $this->assertSame(0.0, scoring::grade($fresh['activity'], null));
        $this->assertSame(0.0, scoring::grade($fresh['activity'], (object) ['earnedxp' => 0, 'totalxp' => 0,
            'earnedunits' => 0, 'totalunits' => 0]));
    }

    /**
     * Recording figures stores them, grades the learner in the gradebook and
     * completes the activity once the threshold is reached; clearing undoes it.
     */
    public function test_record_grades_and_completes(): void {
        global $DB;
        $this->resetAfterTest(true);
        $data = $this->make(['testenabled' => 1, 'buildenabled' => 1, 'completionprogress' => 80]);

        scoring::record($data['activity'], (int) $data['user']->id, self::FIGURES);

        $row = scoring::row((int) $data['activity']->id, (int) $data['user']->id);
        $this->assertEqualsWithDelta(1.48, (float) $row->earnedxp, 0.001);
        $grades = grade_get_grades($data['course']->id, 'mod', 'tarsus', $data['activity']->id, $data['user']->id);
        $this->assertEqualsWithDelta(58.22, (float) $grades->items[0]->grades[$data['user']->id]->grade, 0.01);

        $cm = get_coursemodule_from_instance('tarsus', $data['activity']->id);
        $completion = new \completion_info($data['course']);
        $this->assertSame(COMPLETION_COMPLETE, (int) $completion->get_data($cm, false, $data['user']->id)->completionstate);

        scoring::clear($data['activity'], (int) $data['user']->id);
        $this->assertNull(scoring::row((int) $data['activity']->id, (int) $data['user']->id));
        $grades = grade_get_grades($data['course']->id, 'mod', 'tarsus', $data['activity']->id, $data['user']->id);
        $this->assertNull($grades->items[0]->grades[$data['user']->id]->grade);
        $this->assertSame(COMPLETION_INCOMPLETE, (int) $completion->get_data($cm, false, $data['user']->id)->completionstate);
    }

    /**
     * Below the threshold the activity stays incomplete.
     */
    public function test_threshold_not_reached(): void {
        $this->resetAfterTest(true);
        $data = $this->make(['testenabled' => 1, 'buildenabled' => 0, 'completionprogress' => 80]);

        scoring::record($data['activity'], (int) $data['user']->id, self::FIGURES);

        $cm = get_coursemodule_from_instance('tarsus', $data['activity']->id);
        $completion = new \completion_info($data['course']);
        $this->assertSame(COMPLETION_INCOMPLETE, (int) $completion->get_data($cm, false, $data['user']->id)->completionstate);
    }

    /**
     * Regrading from the gradebook (as the grade report does) re-reads the stored figures.
     */
    public function test_update_grades_for_everyone(): void {
        $this->resetAfterTest(true);
        $data = $this->make(['testenabled' => 0, 'buildenabled' => 1]);
        scoring::record($data['activity'], (int) $data['user']->id, self::FIGURES);

        tarsus_update_grades($data['activity']);

        $grades = tarsus_get_user_grades($data['activity']);
        $this->assertSame(100.0, $grades[$data['user']->id]->rawgrade);
    }
}
