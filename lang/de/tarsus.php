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
 * German strings for mod_tarsus.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Tarsus';
$string['modulename'] = 'Tarsus';
$string['modulenameplural'] = 'Tarsus-Aktivitäten';
$string['pluginadministration'] = 'Tarsus-Verwaltung';
$string['tarsusname'] = 'Name';
$string['tarsus:addinstance'] = 'Eine neue Tarsus-Aktivität hinzufügen';
$string['tarsus:view'] = 'Tarsus-Aktivität ansehen';
$string['tarsus:manage'] = 'Tarsus-Materialindexierung verwalten';

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
$string['settings_general'] = 'KI-Einstellungen: Allgemein';
$string['settings_ask'] = 'KI-Einstellungen: Frage stellen';
$string['settings_test'] = 'KI-Einstellungen: Wissen testen';
$string['settings_build'] = 'KI-Einstellungen: Wissen aufbauen';
$string['interactiontype'] = 'Interaktionsart';
$string['interactiontype_help'] = 'Wer die Antworten und Lernkarten gestaltet. „KI-Manager-gesteuert“ behält die hier konfigurierten Textantworten und Folienkarten. „Agentengesteuert“ lässt den Tarsus-Agenten mit selbst gebauten interaktiven Karten antworten, etwa Diagrammen und Schritt-für-Schritt-Ansichten.';
$string['interactiontype_aidriven'] = 'Agentengesteuert';
$string['interactiontype_trainerdriven'] = 'KI-Manager-gesteuert';
$string['web_frame_title'] = 'Interaktiver Inhalt des Assistenten';
$string['askenabled'] = 'Frage stellen aktivieren';
$string['askenabled_help'] = 'Wenn ausgeschaltet, wird der Chat-Modus in dieser Aktivität für Lernende ausgeblendet.';
$string['testenabled'] = 'Wissen testen aktivieren';
$string['testenabled_help'] = 'Wenn ausgeschaltet, wird der Übungsmodus in dieser Aktivität für Lernende ausgeblendet.';
$string['buildenabled'] = 'Wissen aufbauen aktivieren';
$string['buildenabled_help'] = 'Wenn ausgeschaltet, wird der Modus Wissen aufbauen in dieser Aktivität für Lernende ausgeblendet.';
$string['gifenabled'] = 'GIFs zulassen';
$string['gifenabled_help'] = 'Wenn aktiviert, dürfen die Lernkarten GIF-Animationen enthalten. GIFs können mehr AI Credits verbrauchen.';
$string['learningmethod'] = 'Didaktisches Konzept';
$string['learningmethod_help'] = 'Das didaktische Konzept des Modus Wissen aufbauen. Microlearning vermittelt Wissen in kurzen, zielgerichteten Einheiten, die sich leicht in den Alltag integrieren lassen und unmittelbar anwendbar sind.';
$string['learningmethod_microlearning'] = 'Microlearning';
$string['error_modesrequired'] = 'Aktivieren Sie mindestens einen der Modi.';
$string['learn_slide_of'] = 'Folie {$a->current} von {$a->total}';
$string['learn_previous'] = 'Zurück';
$string['learn_image_enlarge'] = 'Bild vergrößern';
$string['learn_image_close'] = 'Schließen';
$string['learn_next'] = 'Weiter';
$string['learn_audio'] = 'Lerneinheit anhören';
$string['learn_audio_play'] = 'Abspielen';
$string['learn_audio_pause'] = 'Pause';
$string['learn_audio_unavailable'] = 'Das Brain konnte diese Lerneinheit gerade nicht vorlesen.';
$string['learn_ask_about'] = 'Dazu fragen';
$string['chat_reply_about'] = 'Frage zu: {$a}';
$string['chat_reply_clear'] = 'Bezug entfernen';
$string['learn_complete_title'] = 'Super! Du hast die Lerneinheit geschafft.';
$string['learn_motivation_title'] = 'Super! Du bist einen Schritt weiter.';
$string['learn_objectives'] = 'Lernziele';
$string['learn_nowidget'] = 'Der Assistent konnte gerade keine Lerneinheit aus den einbezogenen Materialien erstellen. Bitte versuche es erneut.';
$string['chat_modedisabled'] = 'Dieser Modus ist in dieser Aktivität ausgeschaltet.';
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
$string['industry'] = 'Zielgruppe';
$string['industry_help'] = 'Für wen diese Aktivität gedacht ist – das Fachgebiet bzw. die Domäne, z. B. "Steuerrecht" oder "E-Commerce". Hilft dem Assistenten, passende Situationen und die richtige Terminologie zu wählen. Kann leer bleiben.';
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
$string['qtype_crossmultiplication'] = 'Dreisatz';
$string['qtype_tradecosting'] = 'Tabellenkalkulation';
$string['qtype_unknown'] = 'Unbekannter Fragetyp';
$string['taxonomylevels_placeholder'] = 'Taxonomiestufe hinzufügen...';
$string['taxonomylevels_none'] = 'Keine Taxonomiestufen ausgewählt';
$string['questiontypes_placeholder'] = 'Fragetyp hinzufügen...';
$string['questiontypes_none'] = 'Keine Fragetypen in diesem Schwierigkeitsgrad';
$string['operators'] = 'Operatoren';
$string['operators_help'] = 'Welcher Operatoren-Taxonomie die Übungsfragen folgen. Standard erlaubt alle Operatoren; IHK beschränkt die Fragen auf die vier IHK-Operatoren (nennen, beschreiben, erklären, erläutern).';
$string['operators_standard'] = 'Standard (alle Operatoren)';
$string['operators_ihk'] = 'IHK (nennen, beschreiben, erklären, erläutern)';
$string['anticheat'] = 'Schummelschutz';
$string['anticheat_help'] = 'Wenn aktiviert, können Lernende den Chat nicht zu einer Übungsfrage befragen, die der Assistent ihnen gerade gestellt hat.';
$string['learntempo'] = 'Lerntempo';
$string['learntempo_help'] = 'Steuert zweierlei: wie schnell der Schwierigkeitsgrad der Fragen steigt oder fällt, und wie schnell die Erfahrungspunkte wachsen. Ein höheres Tempo bedeutet weniger Fragen, schnelleren Fortschritt und geringere KI-Nutzung; ein niedrigeres Tempo mehr Fragen und mehr Punkte pro Thema. x1 ist das empfohlene Tempo.';
$string['learntempo_recommended'] = '{$a} (empfohlen)';
$string['error_taxonomyrequired'] = 'Aktivieren Sie mindestens eine Taxonomiestufe.';
$string['error_questiontypesrequired'] = 'Wählen Sie für diesen Schwierigkeitsgrad mindestens einen Fragetyp.';
$string['jump_top'] = 'Nach oben';
$string['practice_start'] = 'Starten';
$string['practice_next'] = 'Weiter';
$string['practice_check'] = 'Prüfen';
$string['practice_answer_placeholder'] = 'Schreibe hier deine Antwort...';
$string['practice_nowidget'] = 'Der Assistent konnte aus den einbezogenen Materialien keine Frage der gewählten Typen erstellen. Versuche es erneut oder bitte deine Lehrkraft, die Fragetypen oder die Materialien anzupassen.';
$string['practice_modelanswer'] = 'Musterantwort';
$string['practice_modelanswers'] = 'Musterantworten';
$string['practice_variable_placeholder'] = 'Deine Antwort';
$string['practice_table_position'] = 'Position';
$string['practice_table_rate'] = 'Satz';
$string['practice_table_amount'] = 'Betrag';
$string['choice_true'] = 'Wahr';
$string['choice_false'] = 'Falsch';
$string['xp'] = 'XP';
$string['learning_units'] = 'LU';
$string['practice_score'] = 'Bewertung der Antwort';
$string['practice_progress'] = 'Übungsfortschritt';
$string['mode_switch'] = 'Interaktionsmodus';
$string['mode_build'] = 'Wissen aufbauen';
$string['mode_test'] = 'Wissen testen';
$string['mode_ask'] = 'Frage stellen';

$string['chat_placeholder'] = 'Stelle eine Frage zu den Kursmaterialien...';
$string['loading_history'] = 'Unterhaltungshistorie wird geladen...';
$string['resetchat'] = 'Chat-Verlauf zurücksetzen';
$string['resetchat_confirm'] = 'Den gesamten Verlauf mit dem Assistenten in "{$a}" löschen – den Chat, alle Übungsfragen und die Erfahrungspunkte? Der Assistent vergisst alles Besprochene, und die nächste Frage beginnt ein neues Gespräch. Dies kann nicht rückgängig gemacht werden.';
$string['resetchat_done'] = 'Chat, Übungsverlauf und Erfahrungspunkte wurden gelöscht.';
$string['resetchat_failed'] = 'Deine Unterhaltung konnte gerade nicht gelöscht werden. Bitte versuche es später erneut.';
$string['chat_noscope'] = 'Diesem Assistenten stehen noch keine Materialien zur Verfügung. Eine Lehrkraft muss zuerst Kursinhalte indexieren und in der Spalte "Einbezogen" einschalten.';
$string['guardrail_blocked_killswitch'] = 'Schnittstellenaufrufe sind derzeit von Systemadministratoren deaktiviert.';
$string['guardrail_blocked_breaker'] = 'Das Tarsus-System ist überlastet und ruht kurzzeitig. Bitte versuche es in wenigen Minuten noch einmal.';
$string['guardrail_blocked_budget'] = 'Die täglichen Budget-Guthaben für diesen Bereich sind aufgebraucht. Bitte versuche es morgen erneut.';
$string['guardrail_blocked_ratelimit'] = 'Zu viele Anfragen. Bitte warte einen Moment und versuche es erneut.';
$string['transport_error'] = 'Konnte das Brain nicht kontaktieren. Bitte überprüfe deine Netzwerkverbindung und versuche es erneut.';

// Datenschutz.
$string['privacy:metadata'] = 'Die Tarsus-Aktivität speichert je Lernendem die zuletzt vom Brain gemeldeten Werte (Erfahrungspunkte, Lerneinheiten, Fortschritt) für Bewertung und Abschluss; Gespräch, Indexierungs- und Nutzungsdaten liegen im Brain und im Plugin local_tarsus.';

// Die Schritte der Verarbeitungsanzeige (was das Brain während einer Anfrage tut).
$string['proc_ask_classify'] = 'Ihre Frage wird nach Thema und Absicht eingeordnet';
$string['proc_ask_search'] = 'Semantische Suche im indexierten Kurswissen';
$string['proc_ask_media'] = 'Text-, Bild-, Audio- und Videostellen im Abgleich';
$string['proc_ask_select'] = 'Die relevantesten Passagen werden ausgewählt';
$string['proc_ask_cube'] = 'Ihr Stand im Lernwürfel wird gelesen';
$string['proc_ask_dqr'] = 'Die Erklärtiefe wird auf Ihr DQR-Niveau abgestimmt';
$string['proc_ask_compose'] = 'Die Antwort wird aus den gefundenen Quellen formuliert';
$string['proc_ask_verify'] = 'Gegenprüfung der Antwort an den Originalstellen';
$string['proc_ask_polish'] = 'Letzter Feinschliff der Antwort';
$string['proc_practice_cube_create'] = 'Ihr Lernwürfel für diesen Kurs wird angelegt';
$string['proc_practice_baseline'] = 'Ihr Ausgangsniveau für das Thema wird geschätzt';
$string['proc_practice_evaluate'] = 'Ihre letzten Antworten werden ausgewertet';
$string['proc_practice_compare'] = 'Erwartete und tatsächliche Trefferquote im Vergleich';
$string['proc_practice_surprise'] = 'Überraschende Antworten zählen mehr als erwartbare';
$string['proc_practice_reestimate'] = 'Ihr Themenwert wird neu geschätzt';
$string['proc_practice_align'] = 'Abgleich von Thema, Taxonomiestufe und Wissensart';
$string['proc_practice_cells'] = 'Aktive Zellen mit niedrigem Stand werden gesucht';
$string['proc_practice_history'] = 'Verlauf wird geprüft: Wachstum, Schwankung, Vergessen';
$string['proc_practice_action'] = 'Die nächste beste Lernaktion wird gewählt';
$string['proc_practice_taxonomy'] = 'Taxonomiestufe: Wissen, Verstehen oder Anwenden';
$string['proc_practice_difficulty'] = 'Der Schwierigkeitsgrad wird gesetzt';
$string['proc_practice_format'] = 'Passendes Aufgabenformat wird ausgewählt';
$string['proc_practice_generate'] = 'Die Aufgabe wird aus Ihren Kursinhalten erzeugt';
$string['proc_practice_operators'] = 'Die Fragestellung wird nach den Operatoren formuliert';
$string['proc_practice_options'] = 'Antwortoptionen und Erklärungen werden ausgearbeitet';
$string['proc_practice_solution'] = 'Musterlösung und Bewertungsmaßstab werden festgelegt';
$string['proc_practice_polish'] = 'Letzter Feinschliff Ihrer Aufgabe';
$string['proc_learn_path_create'] = 'Ihr Lernpfad für diesen Kurs wird angelegt';
$string['proc_learn_progress'] = 'Ihr Lernfortschritt wird gelesen';
$string['proc_learn_objectives'] = 'Erreichte und offene Lernziele werden abgeglichen';
$string['proc_learn_cells'] = 'Offene Zellen im Lernwürfel werden bestimmt';
$string['proc_learn_next'] = 'Nächstes Lernziel: Wissen, Verstehen oder Anwenden';
$string['proc_learn_passages'] = 'Passende Passagen aus Ihrem Kurs werden gesammelt';
$string['proc_learn_dqr'] = 'Die Erklärungen werden auf Ihr DQR-Niveau abgestimmt';
$string['proc_learn_split'] = 'Die Lerneinheit wird in einzelne Karten aufgeteilt';
$string['proc_learn_examples'] = 'Beispiele und Bilder werden ausgewählt';
$string['proc_learn_link'] = 'Anschluss an bereits Gelerntes wird hergestellt';
$string['proc_learn_check'] = 'Die Lernkarten werden auf Verständlichkeit geprüft';
$string['proc_learn_path_update'] = 'Ihr Lernpfad wird fortgeschrieben';
$string['proc_learn_polish'] = 'Letzter Feinschliff Ihrer Lerneinheit';

// Bewertung und Abschluss.
$string['completionprogress'] = 'Fortschritt erreicht';
$string['completionprogress_enabled'] = 'Fortschritt der Lernenden erreicht';
$string['completionprogress_help'] = 'Die Aktivität gilt als abgeschlossen, sobald der vom Brain gemeldete Fortschritt diesen Prozentwert erreicht. „Wissen testen“ zählt den Übungsfortschritt, „Wissen aufbauen“ den Lernfortschritt, beide zusammen den Gesamtfortschritt des Brains.';
$string['completiondetail:progress'] = '{$a}% Fortschritt erreichen';
$string['error_completionprogress'] = 'Geben Sie einen Prozentwert zwischen 1 und 100 ein.';
$string['privacy:metadata:tarsus_progress'] = 'Die zuletzt vom Brain gemeldeten Werte je Lernendem: Erfahrungspunkte, Lerneinheiten und Fortschritt, aus denen Bewertung und Abschluss abgeleitet werden.';
$string['privacy:metadata:tarsus_progress:userid'] = 'Die lernende Person.';
$string['privacy:metadata:tarsus_progress:earnedxp'] = 'In „Wissen testen“ erreichte Erfahrungspunkte.';
$string['privacy:metadata:tarsus_progress:totalxp'] = 'Erreichbare Erfahrungspunkte.';
$string['privacy:metadata:tarsus_progress:earnedunits'] = 'In „Wissen aufbauen“ erreichte Lerneinheiten.';
$string['privacy:metadata:tarsus_progress:totalunits'] = 'Erreichbare Lerneinheiten.';
$string['privacy:metadata:tarsus_progress:practiceprogress'] = 'Übungsfortschritt in Prozent.';
$string['privacy:metadata:tarsus_progress:learnprogress'] = 'Lernfortschritt in Prozent.';
$string['privacy:metadata:tarsus_progress:progress'] = 'Gesamtfortschritt in Prozent.';
$string['privacy:metadata:tarsus_progress:timemodified'] = 'Zeitpunkt der letzten Meldung.';
$string['privacy:metadata:core_grades'] = 'Die daraus abgeleitete Bewertung wird im Bewertungsbuch gespeichert.';
$string['privacy:path:progress'] = 'Fortschritt';
