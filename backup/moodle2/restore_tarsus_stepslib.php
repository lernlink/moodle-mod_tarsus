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
 * Restore structure step for the Tarsus activity.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Reads tarsus.xml back into the tables.
 */
class restore_tarsus_activity_structure_step extends restore_activity_structure_step {

    /**
     * The paths to process.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        $paths = [
            new restore_path_element('tarsus', '/activity/tarsus'),
            new restore_path_element('tarsus_inclusion', '/activity/tarsus/inclusions/inclusion'),
        ];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('tarsus_progress', '/activity/tarsus/progresses/progress');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the activity record.
     *
     * @param array $data The element's data.
     */
    protected function process_tarsus($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('tarsus', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore one included resource as stored; the task remaps its id afterwards.
     *
     * @param array $data The element's data.
     */
    protected function process_tarsus_inclusion($data) {
        global $DB;

        $data = (object) $data;
        $data->tarsusid = $this->get_new_parentid('tarsus');
        if ($DB->record_exists('tarsus_inclusions', ['tarsusid' => $data->tarsusid, 'resourceid' => $data->resourceid])) {
            return;
        }
        $DB->insert_record('tarsus_inclusions', $data);
    }

    /**
     * Restore one learner's figures.
     *
     * @param array $data The element's data.
     */
    protected function process_tarsus_progress($data) {
        global $DB;

        $data = (object) $data;
        $data->tarsusid = $this->get_new_parentid('tarsus');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (empty($data->userid)) {
            return;
        }
        $DB->insert_record('tarsus_progress', $data);
    }

    /**
     * Bring the description's files along.
     */
    protected function after_execute() {
        $this->add_related_files('mod_tarsus', 'intro', null);
    }
}
