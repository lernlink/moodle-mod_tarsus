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
 * Restore structure step for the Tarsus Zero activity.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Reads tarsuszero.xml back into the tables.
 */
class restore_tarsuszero_activity_structure_step extends restore_activity_structure_step {

    /**
     * The paths to process.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        $paths = [
            new restore_path_element('tarsuszero', '/activity/tarsuszero'),
            new restore_path_element('tarsuszero_inclusion', '/activity/tarsuszero/inclusions/inclusion'),
        ];

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the activity record.
     *
     * @param array $data The element's data.
     */
    protected function process_tarsuszero($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('tarsuszero', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore one included resource as stored; the task remaps its id afterwards.
     *
     * @param array $data The element's data.
     */
    protected function process_tarsuszero_inclusion($data) {
        global $DB;

        $data = (object) $data;
        $data->tarsuszeroid = $this->get_new_parentid('tarsuszero');
        if ($DB->record_exists('tarsuszero_inclusions', ['tarsuszeroid' => $data->tarsuszeroid, 'resourceid' => $data->resourceid])) {
            return;
        }
        $DB->insert_record('tarsuszero_inclusions', $data);
    }

    /**
     * Bring the description's files along.
     */
    protected function after_execute() {
        $this->add_related_files('mod_tarsuszero', 'intro', null);
    }
}
