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
 * English strings for mod_tarsuszero.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Tarsus Zero';
$string['modulename'] = 'Tarsus Zero';
$string['modulenameplural'] = 'Tarsus Zero activities';
$string['pluginadministration'] = 'Tarsus Zero administration';
$string['tarsuszeroname'] = 'Name';
$string['tarsuszero:addinstance'] = 'Add a new Tarsus Zero activity';
$string['tarsuszero:view'] = 'View Tarsus Zero activity';
$string['tarsuszero:manage'] = 'Manage Tarsus Zero material indexation';

// Teacher reconciliation dashboard.
$string['indexcourse'] = 'Index this course';
$string['indexcourse_help'] = 'Sends every supported activity in this course to the Brain. New material is queued; unchanged material is skipped.';
$string['indexqueued'] = 'Course indexing has been queued. Status will update as the Brain processes each item.';
$string['col_activity'] = 'Activity';
$string['col_material'] = 'Material';
$string['col_type'] = 'Type';
$string['col_status'] = 'Brain status';
$string['nomaterials'] = 'This course has no indexable material yet.';
$string['learnerplaceholder'] = 'The Tarsus Zero learning experience will appear here.';

// Privacy.
$string['privacy:metadata'] = 'The Tarsus Zero activity stores no personal data itself; indexation and usage data are handled by the local_tarsus plugin.';
