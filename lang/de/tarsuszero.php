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
$string['col_actions'] = 'Aktionen';
$string['nomaterials'] = 'Dieser Kurs enthält noch kein indexierbares Material.';
$string['learnerplaceholder'] = 'Das Tarsus-Zero-Lernerlebnis erscheint hier.';
$string['previewchat'] = 'Lernenden-Chat in der Vorschau';

// Per-resource index actions.
$string['action_index'] = 'Indexieren';
$string['action_reindex'] = 'Neu indexieren';
$string['action_remove'] = 'Entfernen';
$string['action_retry'] = 'Erneut versuchen';
$string['confirmremove_title'] = 'Aus dem Brain entfernen?';
$string['confirmremove_body'] = 'Dieses Material wird aus dem Brain entfernt und nicht mehr zur Beantwortung von Fragen verwendet. Sie können es später erneut indexieren.';
$string['invalidaction'] = 'Unbekannte Index-Aktion angefordert.';
$string['invalidresource'] = 'Die Ressource gehört nicht zu diesem Kurs.';
$string['actionfailed'] = 'Die angeforderte Aktion konnte nicht auf diese Ressource angewendet werden.';

// Learner RAG chat UI strings.
$string['send'] = 'Senden';
$string['sources'] = 'Quellen';
$string['chat_placeholder'] = 'Stelle eine Frage zu den Kursmaterialien...';
$string['loading_history'] = 'Unterhaltungshistorie wird geladen...';
$string['chat_welcome'] = 'Hallo! Ich bin dein Tarsus-Zero-Assistent. Stelle mir eine Frage zu deinen Kursunterlagen, und ich suche fundierte Antworten für dich heraus!';
$string['guardrail_blocked_killswitch'] = 'Schnittstellenaufrufe sind derzeit von Systemadministratoren deaktiviert.';
$string['guardrail_blocked_breaker'] = 'Das Tarsus-System ist überlastet und ruht kurzzeitig. Bitte versuche es in wenigen Minuten noch einmal.';
$string['guardrail_blocked_budget'] = 'Die täglichen Budget-Guthaben für diesen Bereich sind aufgebraucht. Bitte versuche es morgen erneut.';
$string['guardrail_blocked_ratelimit'] = 'Zu viele Anfragen. Bitte warte einen Moment und versuche es erneut.';
$string['transport_error'] = 'Konnte das Brain nicht kontaktieren. Bitte überprüfe deine Netzwerkverbindung und versuche es erneut.';

// Datenschutz.
$string['privacy:metadata'] = 'Die Tarsus-Zero-Aktivität speichert selbst keine personenbezogenen Daten; Indexierungs- und Nutzungsdaten werden vom Plugin local_tarsus verarbeitet.';
