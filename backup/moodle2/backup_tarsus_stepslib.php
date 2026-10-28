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
 * Backup structure step for the Tarsus activity.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines what one Tarsus activity writes into tarsus.xml.
 */
class backup_tarsus_activity_structure_step extends backup_activity_structure_step {

    /**
     * The activity's settings and its inclusion list.
     *
     * Inclusions are teacher configuration, not user data, so they travel
     * regardless of the userinfo setting. Their resource ids embed the
     * course and module ids; the restore task remaps them.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $tarsus = new backup_nested_element('tarsus', ['id'], [
            'name', 'intro', 'introformat', 'timecreated', 'timemodified',
            'conversationstyle', 'dqr', 'industry', 'taxonomylevels', 'questiontypes',
            'operators', 'anticheat', 'learntempo', 'askenabled', 'testenabled', 'buildenabled',
            'gifenabled', 'learningmethod', 'completionprogress',
        ]);

        $inclusions = new backup_nested_element('inclusions');
        $inclusion = new backup_nested_element('inclusion', ['id'], ['resourceid', 'timecreated']);

        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', ['id'], [
            'userid', 'earnedxp', 'totalxp', 'earnedunits', 'totalunits',
            'practiceprogress', 'learnprogress', 'progress', 'timemodified',
        ]);

        $tarsus->add_child($inclusions);
        $inclusions->add_child($inclusion);
        $tarsus->add_child($progresses);
        $progresses->add_child($progress);

        $tarsus->set_source_table('tarsus', ['id' => backup::VAR_ACTIVITYID]);
        $inclusion->set_source_table('tarsus_inclusions', ['tarsusid' => backup::VAR_PARENTID]);
        if ($userinfo) {
            $progress->set_source_table('tarsus_progress', ['tarsusid' => backup::VAR_PARENTID]);
        }

        $progress->annotate_ids('user', 'userid');

        $tarsus->annotate_files('mod_tarsus', 'intro', null);

        return $this->prepare_activity_structure($tarsus);
    }
}
