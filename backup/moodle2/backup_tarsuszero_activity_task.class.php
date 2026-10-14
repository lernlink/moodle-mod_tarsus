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
 * Backup task for the Tarsus Zero activity.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/tarsuszero/backup/moodle2/backup_tarsuszero_stepslib.php');

/**
 * Backs up one Tarsus Zero activity: its settings and the resources the teacher included.
 *
 * The conversation itself lives in the brain and is not part of the backup.
 */
class backup_tarsuszero_activity_task extends backup_activity_task {

    /**
     * No activity-specific settings.
     */
    protected function define_my_settings() {
    }

    /**
     * One structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_tarsuszero_activity_structure_step('tarsuszero_structure', 'tarsuszero.xml'));
    }

    /**
     * Encode links to this activity's pages so restore can rewrite them.
     *
     * @param string $content The content to encode.
     * @return string The encoded content.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');
        $search = '/(' . $base . '\/mod\/tarsuszero\/index\.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@TARSUSZEROINDEX*$2@$', $content);
        $search = '/(' . $base . '\/mod\/tarsuszero\/view\.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@TARSUSZEROVIEWBYID*$2@$', $content);

        return $content;
    }
}
