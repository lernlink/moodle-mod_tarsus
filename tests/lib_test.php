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

use mod_tarsuszero\output\teacher_dashboard;

/**
 * Tests for mod_tarsuszero library functions and the teacher dashboard.
 *
 * @package    mod_tarsuszero
 * @covers     ::tarsuszero_add_instance
 * @covers     \mod_tarsuszero\output\teacher_dashboard
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {

    /**
     * Load lib.php before each test so global module functions are available.
     *
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/tarsuszero/lib.php');
        parent::setUp();
    }

    /**
     * Adding, updating and deleting an instance round-trips through the DB.
     *
     * @return void
     */
    public function test_instance_lifecycle(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();

        $data = (object) [
            'course' => $course->id,
            'name' => 'My RAG activity',
            'intro' => '<p>Intro</p>',
            'introformat' => FORMAT_HTML,
        ];
        $id = tarsuszero_add_instance($data);
        $this->assertGreaterThan(0, $id);
        $this->assertSame('My RAG activity', $DB->get_field('tarsuszero', 'name', ['id' => $id]));

        $data->instance = $id;
        $data->name = 'Renamed';
        $this->assertTrue(tarsuszero_update_instance($data));
        $this->assertSame('Renamed', $DB->get_field('tarsuszero', 'name', ['id' => $id]));

        $this->assertTrue(tarsuszero_delete_instance($id));
        $this->assertFalse($DB->record_exists('tarsuszero', ['id' => $id]));
        $this->assertFalse(tarsuszero_delete_instance($id));
    }

    /**
     * tarsuszero_supports answers known features.
     *
     * @return void
     */
    public function test_supports(): void {
        $this->assertTrue(tarsuszero_supports(FEATURE_MOD_INTRO));
        $this->assertFalse(tarsuszero_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertNull(tarsuszero_supports('some_unknown_feature'));
    }

    /**
     * The dashboard exports supported course activities with a badge per bit.
     *
     * @return void
     */
    public function test_dashboard_exports_activities(): void {
        global $PAGE;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => '<p>Body</p>',
            'contentformat' => FORMAT_HTML,
        ]);
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        $dashboard = new teacher_dashboard($tz->cmid, (int) $course->id, true);
        $context = $dashboard->export_for_template($PAGE->get_renderer('core'));

        $this->assertTrue($context['canmanage']);
        $this->assertTrue($context['hasactivities']);
        $this->assertNotEmpty($context['resources']);
        $bit = $context['resources'][0];
        $this->assertSame('badge-light', $bit['badgeclass']);
        $this->assertNotEmpty($bit['resourceid']);
        $this->assertTrue($bit['canindex']);
        $this->assertFalse($bit['canremove']);
        $this->assertTrue($bit['hasurl']);
        $this->assertFalse($context['hasmultiplepages']);
        $this->assertStringContainsString('action=indexcourse', $context['indexurl']);
    }

    /**
     * The reconciliation list pages at ten materials, exposing a paging bar.
     *
     * @return void
     */
    public function test_dashboard_pages_materials(): void {
        global $PAGE;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        for ($i = 0; $i < 12; $i++) {
            $this->getDataGenerator()->create_module('page', [
                'course' => $course->id,
                'content' => '<p>Body ' . $i . '</p>',
                'contentformat' => FORMAT_HTML,
            ]);
        }
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);

        // First page: capped at ten materials, with a paging bar shown.
        $first = (new teacher_dashboard($tz->cmid, (int) $course->id, true, 0))
            ->export_for_template($PAGE->get_renderer('core'));
        $this->assertCount(10, $first['resources']);
        $this->assertTrue($first['hasmultiplepages']);
        $this->assertNotEmpty($first['pagingbar']);

        // Second page: the remaining materials.
        $second = (new teacher_dashboard($tz->cmid, (int) $course->id, true, 1))
            ->export_for_template($PAGE->get_renderer('core'));
        $this->assertCount(2, $second['resources']);

        // Out-of-range pages clamp to the last available page.
        $clamped = (new teacher_dashboard($tz->cmid, (int) $course->id, true, 99))
            ->export_for_template($PAGE->get_renderer('core'));
        $this->assertCount(2, $clamped['resources']);
    }
}
