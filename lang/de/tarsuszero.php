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

$string['pluginname'] = 'Tarsus';
$string['modulename'] = 'Tarsus';
$string['modulenameplural'] = 'Tarsus-Aktivitäten';
$string['pluginadministration'] = 'Tarsus-Verwaltung';
$string['tarsuszeroname'] = 'Name';
$string['tarsuszero:addinstance'] = 'Eine neue Tarsus-Aktivität hinzufügen';
$string['tarsuszero:view'] = 'Tarsus-Aktivität ansehen';
$string['tarsuszero:manage'] = 'Tarsus-Materialindexierung verwalten';

// Reconciliation-Übersicht für Lehrende.
$string['indexcourse'] = 'Diesen Kurs indexieren';
$string['indexcourse_help'] = 'Sendet jede unterstützte Aktivität dieses Kurses an das Brain. Neues Material wird eingereiht; unverändertes Material wird übersprungen.';
$string['indexqueued'] = 'Die Kursindexierung wurde eingereiht. Der Status wird aktualisiert, sobald das Brain die einzelnen Elemente verarbeitet.';
$string['col_activity'] = 'Aktivität';
$string['col_type'] = 'Typ';
$string['col_status'] = 'Status';
$string['col_included'] = 'Einbezogen';
$string['col_actions'] = 'Aktionen';

// Sammelaktionen für die aktuelle Seite.
$string['bulk_index'] = 'Diese Seite indexieren';
$string['bulk_sync'] = 'Diese Seite mit dem Brain abgleichen';
$string['bulk_help'] = 'Sammelaktionen gelten nur für die auf dieser Seite gelisteten Materialien. Das Brain beantwortet bis zu 10 Ressourcen pro Aufruf, eine Seite kostet also einen Aufruf.';
$string['toomanyresources'] = 'Zu viele Ressourcen in einem Stapel: höchstens {$a} pro Aufruf.';
$string['included_on'] = 'In dieser Aktivität einbezogen';
$string['included_off'] = 'Aus dieser Aktivität ausgeschlossen';
$string['invalidinclusion'] = 'Nur indexierte Ressourcen können in eine Aktivität einbezogen werden.';
$string['nomaterials'] = 'Dieser Kurs enthält noch kein indexierbares Material.';
$string['learnerplaceholder'] = 'Das Tarsus-Lernerlebnis erscheint hier.';
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
$string['includenotindexed'] = 'Nur indexierte Ressourcen können einbezogen werden. Zuerst indexieren, dann einschalten.';

// Learner RAG chat UI strings.
$string['send'] = 'Senden';
$string['sources'] = 'Quellen';
// Didaktische Einstellungen pro Aktivität.
$string['didactics'] = 'Didaktische Einstellungen';
$string['conversationstyle'] = 'Gesprächsstil';
$string['conversationstyle_help'] = 'Wie der Assistent das Gespräch in dieser Aktivität führt: von Geschlossen (kurze, faktische Antworten) über Lexikalisch, Tutorisch und Sokratisch (leitende Gegenfragen) bis Offen (freies Gespräch).';
$string['style_closed'] = 'Geschlossen';
$string['style_lexical'] = 'Lexikalisch';
$string['style_tutorial'] = 'Tutorisch';
$string['style_socratic'] = 'Sokratisch';
$string['style_open'] = 'Offen';
$string['dqr'] = 'Schwierigkeitsniveau (DQR)';
$string['dqr_help'] = 'Das DQR-Niveau, auf dem der Assistent antwortet, von 2-jährigen Ausbildungsberufen (Niveau 3) bis Master Professional (Niveau 7).';
$string['dqr_level3'] = '2-jährige Ausbildungsberufe';
$string['dqr_level4'] = '3-jährige Ausbildungsberufe';
$string['dqr_level5'] = 'Spezialist';
$string['dqr_level6'] = 'Bachelor Professional';
$string['dqr_level7'] = 'Master Professional';
$string['industry'] = 'Fachgebiet / Domäne';
$string['industry_help'] = 'Das Fachgebiet dieser Aktivität, z. B. "Steuerrecht" oder "E-Commerce". Hilft dem Assistenten, die richtige Terminologie zu verwenden. Kann leer bleiben.';
$string['fulltextsearch'] = 'Volltextsuche';
$string['fulltextsearch_help'] = 'Wenn aktiviert, darf der Assistent zusätzlich zur semantischen Suche eine exakte Volltextsuche über die einbezogenen Materialien verwenden.';
$string['taxonomylevels'] = 'Taxonomiestufen';
$string['taxonomylevels_help'] = 'Die Taxonomiestufen nach Bloom, auf die die Übungsfragen im Modus "Wissen testen" abzielen dürfen. Mindestens eine Stufe muss aktiviert bleiben.';
$string['taxonomy_knowledge'] = 'Wissen';
$string['taxonomy_understanding'] = 'Verstehen';
$string['taxonomy_application'] = 'Anwenden';
$string['questiontypes'] = 'Fragetypen';
$string['questiontypes_help'] = 'Welche Fragetypen der Assistent in diesem Schwierigkeitsgrad des Modus "Wissen testen" erzeugen darf. Jeder Schwierigkeitsgrad braucht mindestens einen Typ.';
$string['questiontypes_grade'] = 'Fragetypen — {$a}';
$string['difficulty_easy'] = 'Einfach';
$string['difficulty_medium'] = 'Mittel';
$string['difficulty_hard'] = 'Schwer';
$string['qtype_singlechoice'] = 'Single Choice';
$string['qtype_truefalse'] = 'Wahr / Falsch';
$string['qtype_open'] = 'Offene Frage';
$string['qtype_unknown'] = 'Unbekannter Fragetyp';
$string['taxonomylevels_placeholder'] = 'Taxonomiestufe hinzufügen...';
$string['taxonomylevels_none'] = 'Keine Taxonomiestufen ausgewählt';
$string['questiontypes_placeholder'] = 'Fragetyp hinzufügen...';
$string['questiontypes_none'] = 'Keine Fragetypen in diesem Schwierigkeitsgrad';
$string['error_taxonomyrequired'] = 'Aktivieren Sie mindestens eine Taxonomiestufe.';
$string['error_questiontypesrequired'] = 'Wählen Sie für diesen Schwierigkeitsgrad mindestens einen Fragetyp.';
$string['practice_start'] = 'Starten';
$string['practice_next'] = 'Weiter';
$string['practice_check'] = 'Prüfen';
$string['practice_answer_placeholder'] = 'Schreibe hier deine Antwort...';
$string['practice_nowidget'] = 'Der Assistent konnte gerade keine Frage erstellen. Bitte versuche es erneut.';
$string['practice_modelanswer'] = 'Musterantwort';
$string['choice_true'] = 'Wahr';
$string['choice_false'] = 'Falsch';
$string['xp'] = 'XP';
$string['mode_switch'] = 'Interaktionsmodus';
$string['mode_build'] = 'Wissen aufbauen';
$string['mode_test'] = 'Wissen testen';
$string['mode_ask'] = 'Frage stellen';
$string['mode_comingsoon'] = 'Dieser Modus ist bald verfügbar.';

$string['chat_placeholder'] = 'Stelle eine Frage zu den Kursmaterialien...';
$string['loading_history'] = 'Unterhaltungshistorie wird geladen...';
$string['resetchat'] = 'Chat-Verlauf zurücksetzen';
$string['resetchat_confirm'] = 'Deine gesamte Unterhaltung mit dem Assistenten in "{$a}" löschen? Der Assistent vergisst alles Besprochene, und die nächste Frage beginnt eine neue Unterhaltung. Dies kann nicht rückgängig gemacht werden.';
$string['resetchat_done'] = 'Deine Unterhaltung wurde gelöscht.';
$string['resetchat_failed'] = 'Deine Unterhaltung konnte gerade nicht gelöscht werden. Bitte versuche es später erneut.';
$string['chat_noscope'] = 'Diesem Assistenten stehen noch keine Materialien zur Verfügung. Eine Lehrkraft muss zuerst Kursinhalte indexieren und in der Spalte "Einbezogen" einschalten.';
$string['guardrail_blocked_killswitch'] = 'Schnittstellenaufrufe sind derzeit von Systemadministratoren deaktiviert.';
$string['guardrail_blocked_breaker'] = 'Das Tarsus-System ist überlastet und ruht kurzzeitig. Bitte versuche es in wenigen Minuten noch einmal.';
$string['guardrail_blocked_budget'] = 'Die täglichen Budget-Guthaben für diesen Bereich sind aufgebraucht. Bitte versuche es morgen erneut.';
$string['guardrail_blocked_ratelimit'] = 'Zu viele Anfragen. Bitte warte einen Moment und versuche es erneut.';
$string['transport_error'] = 'Konnte das Brain nicht kontaktieren. Bitte überprüfe deine Netzwerkverbindung und versuche es erneut.';

// Datenschutz.
$string['privacy:metadata'] = 'Die Tarsus-Aktivität speichert selbst keine personenbezogenen Daten; Indexierungs- und Nutzungsdaten werden vom Plugin local_tarsus verarbeitet.';
