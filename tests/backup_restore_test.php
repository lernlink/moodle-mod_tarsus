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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_tarsus;

use local_tarsus\material\resource_id;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Backup and restore of the activity: settings travel, inclusions are remapped,
 * and the recycle bin can back the activity up before deleting it.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \backup_tarsus_activity_task
 * @covers     \restore_tarsus_activity_task
 */
final class backup_restore_test extends \advanced_testcase {
    /**
     * A course with a page and an activity that includes the page.
     *
     * @return array{course: \stdClass, page: \stdClass, tz: \stdClass, resourceid: string}
     */
    private function setup_course(): array {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'content' => '<p>Body</p>']);
        $tz = $this->getDataGenerator()->create_module('tarsus', [
            'course' => $course->id, 'dqr' => 5, 'operators' => 'ihk', 'gifenabled' => 1, 'anticheat' => 1, 'learntempo' => 0.5,
        ]);
        $resourceid = resource_id::build((int) $course->id, 'mod_page', (int) $page->cmid);
        $DB->insert_record('tarsus_inclusions', (object) [
            'tarsusid' => $tz->id, 'resourceid' => $resourceid, 'timecreated' => time(),
        ]);
        return ['course' => $course, 'page' => $page, 'tz' => $tz, 'resourceid' => $resourceid];
    }

    /**
     * Duplicating the activity in place copies its settings and keeps the
     * inclusion of a page that still lives in the same course.
     */
    public function test_duplicate_keeps_settings_and_inclusion(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->setup_course();

        // Moodle 5.2 moved module duplication to the format actions; older
        // supported versions still use the global function.
        if (class_exists(\core_courseformat\local\cmactions::class)) {
            $newcm = (new \core_courseformat\local\cmactions($data['course']))->duplicate($data['tz']->cmid);
        } else {
            $newcm = duplicate_module($data['course'], get_fast_modinfo($data['course'])->get_cm($data['tz']->cmid));
        }
        $copy = $DB->get_record('tarsus', ['id' => $newcm->instance], '*', MUST_EXIST);

        $this->assertNotEquals($data['tz']->id, $copy->id);
        $this->assertSame(5, (int) $copy->dqr);
        $this->assertSame('ihk', $copy->operators);
        $this->assertSame(1, (int) $copy->gifenabled);
        $this->assertSame(1, (int) $copy->anticheat);
        $this->assertEqualsWithDelta(0.5, (float) $copy->learntempo, 0.001);
        $this->assertSame(
            [$data['resourceid']],
            array_values($DB->get_fieldset_select('tarsus_inclusions', 'resourceid', 'tarsusid = ?', [$copy->id]))
        );
    }

    /**
     * Restoring the course elsewhere on the same site remaps the inclusion to
     * the restored page's new module id.
     */
    public function test_restore_into_new_course_remaps_inclusion(): void {
        global $DB, $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->setup_course();

        // MODE_IMPORT keeps the unpacked backup in the temp dir, where the
        // restore controller picks it up by id (as core's own tests do).
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $data['course']->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $newcourse = $this->getDataGenerator()->create_course();
        $rc = new \restore_controller(
            $backupid,
            $newcourse->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $restored = $DB->get_record('tarsus', ['course' => $newcourse->id], '*', MUST_EXIST);
        $this->assertSame('ihk', $restored->operators);
        $newpagecm = $DB->get_record_sql(
            "SELECT cm.id FROM {course_modules} cm JOIN {modules} m ON m.id = cm.module WHERE m.name = 'page' AND cm.course = ?",
            [$newcourse->id],
            MUST_EXIST
        );
        $expected = resource_id::build((int) $newcourse->id, 'mod_page', (int) $newpagecm->id);
        $this->assertSame(
            [$expected],
            array_values($DB->get_fieldset_select('tarsus_inclusions', 'resourceid', 'tarsusid = ?', [$restored->id]))
        );
    }

    /**
     * Deleting the activity with the course recycle bin enabled backs it up
     * first — the path that used to fail for want of a backup task — and
     * removes its inclusions.
     */
    public function test_recycle_bin_can_back_up_before_deleting(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        if (!\core_component::get_component_directory('tool_recyclebin')) {
            $this->markTestSkipped('The recycle bin is not installed.');
        }
        set_config('coursebinenable', 1, 'tool_recyclebin');
        $data = $this->setup_course();

        if (class_exists(\core_courseformat\local\cmactions::class)) {
            (new \core_courseformat\local\cmactions($data['course']))->delete($data['tz']->cmid);
        } else {
            course_delete_module($data['tz']->cmid);
        }

        $this->assertFalse($DB->record_exists('tarsus', ['id' => $data['tz']->id]));
        $this->assertFalse($DB->record_exists('tarsus_inclusions', ['tarsusid' => $data['tz']->id]));
        $this->assertSame(1, $DB->count_records('tool_recyclebin_course', ['courseid' => $data['course']->id]));
    }
}
