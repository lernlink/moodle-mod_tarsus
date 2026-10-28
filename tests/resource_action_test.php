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

use local_tarsus\index\course_report;
use local_tarsus\index\status;
use mod_tarsus\external\set_resource_action;
use mod_tarsus\external\get_index_status;

/**
 * Tests for the per-resource index action external functions.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_tarsus\external\set_resource_action
 * @covers     \mod_tarsus\external\get_index_status
 */
final class resource_action_test extends \advanced_testcase {

    /**
     * Build a course with one page and a tarsus activity.
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
        $tz = $this->getDataGenerator()->create_module('tarsus', ['course' => $course->id]);

        $report = (new course_report())->for_course((int) $course->id);
        $resourceid = $report[0]['bits'][0]['resourceid'];

        return ['course' => $course, 'tz' => $tz, 'resourceid' => $resourceid];
    }

    /**
     * Indexing a text bit runs the light upload inline: the click already
     * returns the awaiting-confirmation state, not just "queued".
     *
     * @return void
     */
    public function test_index_action_uploads_inline(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->setup_course();

        $result = set_resource_action::execute($data['tz']->cmid, $data['resourceid'], 'index');

        $this->assertSame(status::PENDING_CONFIRMATION, $result['status']);
        $this->assertTrue($result['inflight']);
        $this->assertFalse($result['canindex']);
    }

    /**
     * Removing an indexed resource untracks it within the same click.
     *
     * @return void
     */
    public function test_remove_action_untracks_inline(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->setup_course();

        set_resource_action::execute($data['tz']->cmid, $data['resourceid'], 'index');
        (new \local_tarsus\index\repository())->mark_indexed($data['resourceid'], 'bit-1', 'hash-1');

        $result = set_resource_action::execute($data['tz']->cmid, $data['resourceid'], 'remove');

        $this->assertSame(status::NOT_INDEXED, $result['status']);
        $this->assertTrue($result['canindex']);
        $this->assertNull((new \local_tarsus\index\repository())->get($data['resourceid']));
    }

    /**
     * An upload the brain never confirms offers no action at first, then
     * Remove once it counts as stuck — and Remove really untracks it.
     *
     * @return void
     */
    public function test_stuck_confirmation_can_be_removed(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->setup_course();
        $repo = new \local_tarsus\index\repository();

        set_resource_action::execute($data['tz']->cmid, $data['resourceid'], 'index');
        $repo->set_status($data['resourceid'], status::PENDING_CONFIRMATION);

        $fresh = $this->status_row($data['tz']->cmid, $data['resourceid']);
        $this->assertTrue($fresh['inflight']);
        $this->assertFalse($fresh['canremove']);

        $DB->set_field('local_tarsus_index', 'timemodified',
            time() - \mod_tarsus\local\status_presenter::STUCK_AFTER - 1, ['resourceid' => $data['resourceid']]);
        $stuck = $this->status_row($data['tz']->cmid, $data['resourceid']);
        $this->assertTrue($stuck['canremove']);
        $this->assertFalse($stuck['canindex']);

        $result = set_resource_action::execute($data['tz']->cmid, $data['resourceid'], 'remove');
        $this->assertSame(status::NOT_INDEXED, $result['status']);
        $this->assertNull($repo->get($data['resourceid']));
    }

    /**
     * The polling endpoint's row for one resource.
     *
     * @param int    $cmid       The activity's course module id.
     * @param string $resourceid The resource id.
     * @return array<string, mixed>
     */
    private function status_row(int $cmid, string $resourceid): array {
        $status = get_index_status::execute($cmid);
        foreach ($status['resources'] as $row) {
            if ($row['resourceid'] === $resourceid) {
                return $row;
            }
        }
        $this->fail('Resource missing from the status report.');
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
        $this->assertSame(status::PENDING_CONFIRMATION, reset($found)['status']);
    }

    /**
     * A blocked action surfaces the reason and reverts the row.
     *
     * @return void
     */
    public function test_blocked_action_surfaces_and_reverts(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->setup_course();

        set_config('killswitch', 1, 'local_tarsus');

        try {
            set_resource_action::execute($data['tz']->cmid, $data['resourceid'], 'index');
            $this->fail('Expected the kill switch to block the action.');
        } catch (\moodle_exception $e) {
            $this->assertSame('blocked_killswitch', $e->errorcode);
        }

        // The row must not stay mid-flight: the Index action is available again.
        $row = (new \local_tarsus\index\repository())->get($data['resourceid']);
        $this->assertSame(status::NOT_INDEXED, $row->status);
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
