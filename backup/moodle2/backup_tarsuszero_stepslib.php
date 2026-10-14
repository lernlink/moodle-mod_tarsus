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
 * Backup structure step for the Tarsus Zero activity.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines what one Tarsus Zero activity writes into tarsuszero.xml.
 */
class backup_tarsuszero_activity_structure_step extends backup_activity_structure_step {

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

        $tarsuszero = new backup_nested_element('tarsuszero', ['id'], [
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

        $tarsuszero->add_child($inclusions);
        $inclusions->add_child($inclusion);
        $tarsuszero->add_child($progresses);
        $progresses->add_child($progress);

        $tarsuszero->set_source_table('tarsuszero', ['id' => backup::VAR_ACTIVITYID]);
        $inclusion->set_source_table('tarsuszero_inclusions', ['tarsuszeroid' => backup::VAR_PARENTID]);
        if ($userinfo) {
            $progress->set_source_table('tarsuszero_progress', ['tarsuszeroid' => backup::VAR_PARENTID]);
        }

        $progress->annotate_ids('user', 'userid');

        $tarsuszero->annotate_files('mod_tarsuszero', 'intro', null);

        return $this->prepare_activity_structure($tarsuszero);
    }
}
