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

use local_tarsus\index\repository;
use local_tarsus\indexer;
use mod_tarsuszero\external\set_resource_action;
use mod_tarsuszero\external\set_resource_inclusion;

/**
 * Tests for the activity inclusion rules.
 *
 * @package    mod_tarsuszero
 * @covers     \mod_tarsuszero\external\set_resource_inclusion
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class inclusion_test extends \advanced_testcase {

    /**
     * Build a course with a tarsuszero activity and a tracked page.
     *
     * @return array{course: \stdClass, tz: \stdClass, resourceid: string}
     */
    private function setup_course(): array {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsuszero', ['course' => $course->id]);
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'content' => '<p>Body</p>',
            'contentformat' => FORMAT_HTML,
        ]);
        $cm = get_fast_modinfo($course->id)->get_cm($page->cmid);
        $resourceid = (new indexer())->index_cm($cm)[0];

        return ['course' => $course, 'tz' => $tz, 'resourceid' => $resourceid];
    }

    /**
     * Including content the brain does not hold is refused.
     *
     * @return void
     */
    public function test_include_requires_indexed(): void {
        $this->resetAfterTest(true);
        $data = $this->setup_course();

        try {
            set_resource_inclusion::execute($data['tz']->cmid, $data['resourceid'], true);
            $this->fail('Expected the inclusion to be refused.');
        } catch (\moodle_exception $e) {
            $this->assertSame('includenotindexed', $e->errorcode);
        }

        global $DB;
        $this->assertFalse($DB->record_exists('tarsuszero_inclusions', ['resourceid' => $data['resourceid']]));
    }

    /**
     * Indexed content can be included, and exclusion needs no status at all.
     *
     * @return void
     */
    public function test_include_indexed_and_exclude_any(): void {
        global $DB;
        $this->resetAfterTest(true);
        $data = $this->setup_course();

        $repo = new repository();
        $repo->mark_uploaded($data['resourceid'], 'hash-1');
        $repo->mark_indexed($data['resourceid'], 'bit-1', 'hash-1');

        $result = set_resource_inclusion::execute($data['tz']->cmid, $data['resourceid'], true);
        $this->assertTrue($result['included']);
        $this->assertTrue($DB->record_exists('tarsuszero_inclusions', ['resourceid' => $data['resourceid']]));

        // Excluding must work even once the resource is no longer indexed,
        // or a stale choice could never be cleaned up from the interface.
        $repo->delete($data['resourceid']);
        $result = set_resource_inclusion::execute($data['tz']->cmid, $data['resourceid'], false);
        $this->assertFalse($result['included']);
        $this->assertFalse($DB->record_exists('tarsuszero_inclusions', ['resourceid' => $data['resourceid']]));
    }

    /**
     * Removing a resource from the brain removes it from every inclusion set.
     *
     * @return void
     */
    public function test_remove_action_clears_inclusions(): void {
        global $DB;
        $this->resetAfterTest(true);
        $data = $this->setup_course();

        set_config('mockmode', '1', 'local_tarsus');

        $repo = new repository();
        $repo->mark_uploaded($data['resourceid'], 'hash-1');
        $repo->mark_indexed($data['resourceid'], 'bit-1', 'hash-1');
        set_resource_inclusion::execute($data['tz']->cmid, $data['resourceid'], true);

        set_resource_action::execute($data['tz']->cmid, $data['resourceid'], 'remove');

        $this->assertNull($repo->get($data['resourceid']));
        $this->assertFalse($DB->record_exists('tarsuszero_inclusions', ['resourceid' => $data['resourceid']]));
    }
}
