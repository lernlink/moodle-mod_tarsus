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

/**
 * Restore task for the Tarsus activity.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/tarsus/backup/moodle2/restore_tarsus_stepslib.php');

use local_tarsus\material\resource_id;

/**
 * Restores one Tarsus activity and remaps its inclusion list.
 */
class restore_tarsus_activity_task extends restore_activity_task {

    /**
     * No activity-specific settings.
     */
    protected function define_my_settings() {
    }

    /**
     * One structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_tarsus_activity_structure_step('tarsus_structure', 'tarsus.xml'));
    }

    /**
     * Contents to decode (links inside the description).
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [new restore_decode_content('tarsus', ['intro'], 'tarsus')];
    }

    /**
     * Rules to decode links to this activity's pages.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('TARSUSVIEWBYID', '/mod/tarsus/view.php?id=$1', 'course_module'),
            new restore_decode_rule('TARSUSINDEX', '/mod/tarsus/index.php?id=$1', 'course'),
        ];
    }

    /**
     * No legacy log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * Remap the inclusion list once every module of the course is restored.
     *
     * A resource id embeds the site token, the course id and the module id
     * of the material. On the same site the module id follows the restore
     * mapping (a module restored alongside), or stays when the material
     * itself still exists in the target course (an activity duplicated in
     * place). Anything else — another site, a material that did not come
     * along — is dropped: the teacher includes material again once it is
     * indexed here.
     */
    public function after_restore() {
        global $DB;

        $instanceid = (int) $this->get_activityid();
        $courseid = (int) $this->get_courseid();
        $rows = $DB->get_records('tarsus_inclusions', ['tarsusid' => $instanceid]);

        foreach ($rows as $row) {
            $parts = resource_id::parse((string) $row->resourceid);
            $newcmid = 0;
            if ($parts !== null && $parts['sitetoken'] === resource_id::site_token()) {
                $mapping = restore_dbops::get_backup_ids_record($this->get_restoreid(), 'course_module', $parts['cmid']);
                $newcmid = $mapping ? (int) $mapping->newitemid : 0;
                if ($newcmid === 0 && (int) $parts['courseid'] === $courseid
                        && $DB->record_exists('course_modules', ['id' => $parts['cmid'], 'course' => $courseid])) {
                    $newcmid = (int) $parts['cmid'];
                }
            }

            if ($newcmid === 0) {
                $DB->delete_records('tarsus_inclusions', ['id' => $row->id]);
                continue;
            }

            $remapped = resource_id::build($courseid, $parts['component'], $newcmid, $parts['bitkey']);
            if ($remapped !== $row->resourceid) {
                if ($DB->record_exists('tarsus_inclusions', ['tarsusid' => $instanceid, 'resourceid' => $remapped])) {
                    $DB->delete_records('tarsus_inclusions', ['id' => $row->id]);
                } else {
                    $DB->set_field('tarsus_inclusions', 'resourceid', $remapped, ['id' => $row->id]);
                }
            }
        }
    }
}
