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

use local_tarsus\index\course_report;
use local_tarsus\index\status;
use mod_tarsuszero\external\set_resource_action;
use mod_tarsuszero\external\get_index_status;

/**
 * Tests for the per-resource index action external functions.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_tarsuszero\external\set_resource_action
 * @covers     \mod_tarsuszero\external\get_index_status
 */
final class resource_action_test extends \advanced_testcase {

    /**
     * Build a course with one page and a tarsuszero activity.
     *
     * @return array{course: \stdClass, tz: \stdClass, resourceid: string}
     */
    private function setup_course(): array {
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => '<p>Body</p>',
            'contentformat' => FORMAT_HTML,
        ]);
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        $report = (new course_report())->for_course((int) $course->id);
        $resourceid = $report[0]['bits'][0]['resourceid'];

        return ['course' => $course, 'tz' => $tz, 'resourceid' => $resourceid];
    }

    /**
     * Indexing a not-indexed resource moves it to a pending, in-flight state.
     *
     * @return void
     */
    public function test_index_action_queues_resource(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->setup_course();

        $result = set_resource_action::execute($data['tz']->cmid, $data['resourceid'], 'index');

        $this->assertSame(status::PENDING, $result['status']);
        $this->assertTrue($result['inflight']);
        $this->assertFalse($result['canindex']);
    }

    /**
     * The polling endpoint reports the live status of every bit.
     *
     * @return void
     */
    public function test_get_index_status_reflects_action(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->setup_course();

        set_resource_action::execute($data['tz']->cmid, $data['resourceid'], 'index');
        $status = get_index_status::execute($data['tz']->cmid);

        $this->assertNotEmpty($status['resources']);
        $found = array_filter($status['resources'], static function ($row) use ($data) {
            return $row['resourceid'] === $data['resourceid'];
        });
        $this->assertCount(1, $found);
        $this->assertSame(status::PENDING, reset($found)['status']);
    }

    /**
     * A resource id from another course is rejected (isolation guard).
     *
     * @return void
     */
    public function test_cross_course_resource_is_rejected(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->setup_course();

        // Forge a resource id that parses but points at a different course.
        $parts = explode(':', $data['resourceid']);
        $parts[1] = (string) ((int) $parts[1] + 1000);
        $forged = implode(':', $parts);

        $this->expectException(\moodle_exception::class);
        set_resource_action::execute($data['tz']->cmid, $forged, 'index');
    }

    /**
     * A learner without the manage capability cannot trigger actions.
     *
     * @return void
     */
    public function test_manage_capability_is_required(): void {
        $this->resetAfterTest(true);
        $data = $this->setup_course();

        $student = $this->getDataGenerator()->create_and_enrol($data['course'], 'student');
        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);
        set_resource_action::execute($data['tz']->cmid, $data['resourceid'], 'index');
    }
}
