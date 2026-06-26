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
 * German strings for mod_tarsuszero.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Tarsus Zero';
$string['modulename'] = 'Tarsus Zero';
$string['modulenameplural'] = 'Tarsus-Zero-Aktivitäten';
$string['pluginadministration'] = 'Tarsus-Zero-Verwaltung';
$string['tarsuszeroname'] = 'Name';
$string['tarsuszero:addinstance'] = 'Eine neue Tarsus-Zero-Aktivität hinzufügen';
$string['tarsuszero:view'] = 'Tarsus-Zero-Aktivität ansehen';
$string['tarsuszero:manage'] = 'Tarsus-Zero-Materialindexierung verwalten';

// Reconciliation-Übersicht für Lehrende.
$string['indexcourse'] = 'Diesen Kurs indexieren';
$string['indexcourse_help'] = 'Sendet jede unterstützte Aktivität dieses Kurses an das Brain. Neues Material wird eingereiht; unverändertes Material wird übersprungen.';
$string['indexqueued'] = 'Die Kursindexierung wurde eingereiht. Der Status wird aktualisiert, sobald das Brain die einzelnen Elemente verarbeitet.';
$string['col_activity'] = 'Aktivität';
$string['col_material'] = 'Material';
$string['col_type'] = 'Typ';
$string['col_status'] = 'Brain-Status';
$string['nomaterials'] = 'Dieser Kurs enthält noch kein indexierbares Material.';
$string['learnerplaceholder'] = 'Das Tarsus-Zero-Lernerlebnis erscheint hier.';

// Datenschutz.
$string['privacy:metadata'] = 'Die Tarsus-Zero-Aktivität speichert selbst keine personenbezogenen Daten; Indexierungs- und Nutzungsdaten werden vom Plugin local_tarsus verarbeitet.';
