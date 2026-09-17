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

$string['pluginname'] = 'Tarsus';
$string['modulename'] = 'Tarsus';
$string['modulenameplural'] = 'Tarsus activities';
$string['pluginadministration'] = 'Tarsus administration';
$string['tarsuszeroname'] = 'Name';
$string['tarsuszero:addinstance'] = 'Add a new Tarsus activity';
$string['tarsuszero:view'] = 'View Tarsus activity';
$string['tarsuszero:manage'] = 'Manage Tarsus material indexation';
$string['tarsuszero:resetchat'] = 'Erase own chat conversation with the assistant';

// Teacher reconciliation dashboard.
$string['indexcourse'] = 'Index this course';
$string['indexcourse_help'] = 'Sends every supported activity in this course to the Brain. New material is queued; unchanged material is skipped.';
$string['indexqueued'] = 'Course indexing has been queued. Status will update as the Brain processes each item.';
$string['col_activity'] = 'Activity';
$string['col_type'] = 'Type';
$string['col_status'] = 'Status';
$string['col_included'] = 'Included';
$string['col_actions'] = 'Actions';

// Bulk actions on the current page.
$string['bulk_index'] = 'Index this page';
$string['bulk_sync'] = 'Sync this page with the brain';
$string['bulk_help'] = 'Bulk actions apply only to the materials listed on this page. The brain answers up to 10 resources per call, so one page costs one call.';
$string['toomanyresources'] = 'Too many resources in one batch: at most {$a} may be checked per call.';
$string['included_on'] = 'Included in this activity';
$string['included_off'] = 'Excluded from this activity';
$string['invalidinclusion'] = 'Only indexed resources can be included in an activity.';
$string['nomaterials'] = 'This course has no indexable material yet.';
$string['learnerplaceholder'] = 'The Tarsus learning experience will appear here.';
$string['previewchat'] = 'Preview the learner chat';

// Per-resource index actions.
$string['action_index'] = 'Index';
$string['action_reindex'] = 'Re-index';
$string['action_remove'] = 'Remove';
$string['action_retry'] = 'Retry';
$string['confirmremove_title'] = 'Remove from the Brain?';
$string['confirmremove_body'] = 'This material will be removed from the Brain and will no longer be used to answer questions. You can re-index it again later.';
$string['invalidaction'] = 'Unknown index action requested.';
$string['invalidresource'] = 'The resource does not belong to this course.';
$string['actionfailed'] = 'The requested action could not be applied to this resource.';
$string['includenotindexed'] = 'Only indexed resources can be included. Index it first, then switch it on.';

// Per-activity didactic settings.
$string['didactics'] = 'Didactic settings';
$string['conversationstyle'] = 'Conversation style';
$string['conversationstyle_help'] = 'How the assistant leads the conversation in this activity: from Closed (short, factual answers) through Lexical, Tutorial and Socratic (guiding counter-questions) to Open (free-ranging discussion).';
$string['style_closed'] = 'Closed (Geschlossen)';
$string['style_lexical'] = 'Lexical (Lexikalisch)';
$string['style_tutorial'] = 'Tutorial (Tutorisch)';
$string['style_socratic'] = 'Socratic (Sokratisch)';
$string['style_open'] = 'Open (Offen)';
$string['dqr'] = 'Difficulty level (DQR)';
$string['dqr_help'] = 'The German Qualifications Framework (DQR) level the assistant pitches its answers at, from 2-year vocational training programmes (level 3) to Master Professional (level 7).';
$string['dqr_level3'] = '2-year vocational training programmes';
$string['dqr_level4'] = '3-year vocational training programmes';
$string['dqr_level5'] = 'Specialist';
$string['dqr_level6'] = 'Bachelor Professional';
$string['dqr_level7'] = 'Master Professional';
$string['industry'] = 'Domain / field of expertise';
$string['industry_help'] = 'The subject domain of this activity, e.g. "Steuerrecht" or "E-commerce". Helps the assistant use the right terminology. May be left empty.';
$string['fulltextsearch'] = 'Full-text search';
$string['fulltextsearch_help'] = 'When enabled, the assistant may additionally use exact full-text search over the included materials, on top of semantic search.';
$string['taxonomylevels'] = 'Taxonomy levels';
$string['taxonomylevels_help'] = 'The Bloom taxonomy levels the practice questions may target in the Test knowledge mode. At least one level must stay enabled.';
$string['taxonomy_knowledge'] = 'Knowledge (Wissen)';
$string['taxonomy_understanding'] = 'Understanding (Verstehen)';
$string['taxonomy_application'] = 'Application (Anwenden)';
$string['questiontypes'] = 'Question types';
$string['questiontypes_help'] = 'Which question types the assistant may generate in this difficulty grade of the Test knowledge mode. A grade with no types selected is skipped; at least one type in at least one grade is required.';
$string['questiontypes_grade'] = 'Question types — {$a}';
$string['difficulty_easy'] = 'Easy (Einfach)';
$string['difficulty_medium'] = 'Medium (Mittel)';
$string['difficulty_hard'] = 'Hard (Schwer)';
$string['qtype_singlechoice'] = 'Single choice';
$string['qtype_truefalse'] = 'True / false';
$string['qtype_open'] = 'Open question';
$string['error_taxonomyrequired'] = 'Enable at least one taxonomy level.';
$string['error_questiontypesrequired'] = 'Enable at least one question type in at least one difficulty grade.';
$string['practice_start'] = 'Start';
$string['practice_next'] = 'Next';
$string['practice_check'] = 'Check';
$string['practice_answer_placeholder'] = 'Write your answer here...';
$string['practice_nowidget'] = 'The assistant could not come up with a question right now. Please try again.';
$string['practice_modelanswer'] = 'Model answer';
$string['choice_true'] = 'True';
$string['choice_false'] = 'False';
$string['xp'] = 'XP';

// Learner RAG chat UI strings.
$string['mode_switch'] = 'Interaction mode';
$string['mode_build'] = 'Build knowledge';
$string['mode_test'] = 'Test knowledge';
$string['mode_ask'] = 'Ask a question';
$string['mode_comingsoon'] = 'This mode is coming soon.';
$string['send'] = 'Send';
$string['sources'] = 'Sources';
$string['chat_placeholder'] = 'Ask a question about the course materials...';
$string['loading_history'] = 'Loading conversation history...';
$string['resetchat'] = 'Reset chat history';
$string['resetchat_confirm'] = 'Erase your entire conversation with the assistant in "{$a}"? The assistant will forget everything you have discussed, and the next question starts a fresh conversation. This cannot be undone.';
$string['resetchat_done'] = 'Your conversation has been erased.';
$string['resetchat_failed'] = 'Your conversation could not be erased right now. Please try again later.';
$string['chat_noscope'] = 'No materials are available to this assistant yet. A teacher has to index course content and switch it on in the Included column first.';
$string['guardrail_blocked_killswitch'] = 'API calls are disabled by system administrators at the moment.';
$string['guardrail_blocked_breaker'] = 'The Tarsus system is undergoing high latency and is temporarily resting. Please try again in a few minutes.';
$string['guardrail_blocked_budget'] = 'Daily query budget limits reached for this context. Pleases try again tomorrow.';
$string['guardrail_blocked_ratelimit'] = 'Too many requests. Please wait a few seconds and try again.';
$string['transport_error'] = 'Could not contact the Brain. Please check your network connection and try again.';

// Privacy.
$string['privacy:metadata'] = 'The Tarsus activity stores no personal data itself; indexation and usage data are handled by the local_tarsus plugin.';
