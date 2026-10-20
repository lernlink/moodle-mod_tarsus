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
$string['settings_general'] = 'AI settings: general';
$string['settings_ask'] = 'AI settings: Ask a question';
$string['settings_test'] = 'AI settings: Test knowledge';
$string['settings_build'] = 'AI settings: Build knowledge';
$string['askenabled'] = 'Enable Ask a question';
$string['askenabled_help'] = 'When switched off, the chat mode is hidden from learners in this activity.';
$string['testenabled'] = 'Enable Test knowledge';
$string['testenabled_help'] = 'When switched off, the practice mode is hidden from learners in this activity.';
$string['buildenabled'] = 'Enable Build knowledge';
$string['buildenabled_help'] = 'When switched off, the Build knowledge mode is hidden from learners in this activity.';
$string['gifenabled'] = 'Add GIFs';
$string['gifenabled_help'] = 'When enabled, the learning cards may include GIF animations. GIFs can use more AI credits.';
$string['learningmethod'] = 'Didactic concept';
$string['learningmethod_help'] = 'The didactic concept the Build knowledge mode follows. Micro-learning teaches in short, focused units that are easy to fit into everyday work and immediately applicable.';
$string['learningmethod_microlearning'] = 'Microlearning';
$string['error_modesrequired'] = 'Enable at least one of the modes.';
$string['learn_slide_of'] = 'Slide {$a->current} of {$a->total}';
$string['learn_previous'] = 'Previous';
$string['learn_image_enlarge'] = 'Enlarge image';
$string['learn_image_close'] = 'Close';
$string['learn_next'] = 'Next';
$string['learn_audio'] = 'Listen to this unit';
$string['learn_audio_play'] = 'Play';
$string['learn_audio_pause'] = 'Pause';
$string['learn_audio_unavailable'] = 'The brain could not read this unit aloud right now.';
$string['learn_ask_about'] = 'Ask about this';
$string['learn_ask_about_hint'] = 'Ask a question about this slide';
$string['practice_ask_about_hint'] = 'Ask a question about this question or your answer';
$string['chat_reply_about'] = 'Asking about: {$a}';
$string['chat_reply_clear'] = 'Remove the reference';
$string['learn_complete_title'] = 'Well done! You have completed the learning unit.';
$string['learn_motivation_title'] = 'Great! One step further.';
$string['learn_objectives'] = 'Learning objectives';
$string['learn_nowidget'] = 'The assistant could not prepare a learning unit from the included materials right now. Please try again.';
$string['chat_modedisabled'] = 'This mode is switched off in this activity.';
$string['conversationstyle'] = 'Conversation style';
$string['conversationstyle_help'] = 'How the assistant leads the conversation in this activity: from Closed (short, factual answers) through Lexical, Tutorial and Socratic (guiding counter-questions) to Open (free-ranging discussion).';
$string['style_closed'] = 'Closed';
$string['style_lexical'] = 'Lexical';
$string['style_tutorial'] = 'Tutorial';
$string['style_socratic'] = 'Socratic';
$string['style_open'] = 'Open';
$string['dqr'] = 'Difficulty level (DQR)';
$string['dqr_help'] = 'The German Qualifications Framework (DQR) level the assistant pitches its answers at, from 2-year vocational training programmes (level 3) to Master Professional (level 7).';
$string['dqr_level3'] = '2-year vocational training programmes';
$string['dqr_level4'] = '3-year vocational training programmes';
$string['dqr_level5'] = 'Specialist';
$string['dqr_level6'] = 'Bachelor Professional';
$string['dqr_level7'] = 'Master Professional';
$string['industry'] = 'Target group';
$string['industry_help'] = 'Who this activity is for — the domain or field of expertise, e.g. "Steuerrecht" or "E-commerce". Helps the assistant pick the right situations and terminology. May be left empty.';
$string['taxonomylevels'] = 'Taxonomy levels';
$string['taxonomylevels_help'] = 'The Bloom taxonomy levels the practice questions may target in the Test knowledge mode. At least one level must stay enabled.';
$string['taxonomy_knowledge'] = 'Knowledge';
$string['taxonomy_understanding'] = 'Understanding';
$string['taxonomy_application'] = 'Application';
$string['questiontypes'] = 'Question types';
$string['questiontypes_help'] = 'Which question types the assistant may generate in this difficulty grade of the Test knowledge mode. Every grade needs at least one type.';
$string['questiontypes_grade'] = 'Question types — {$a}';
$string['difficulty_easy'] = 'Easy';
$string['difficulty_medium'] = 'Medium';
$string['difficulty_hard'] = 'Hard';
$string['qtype_singlechoice'] = 'Single choice';
$string['qtype_truefalse'] = 'True / false';
$string['qtype_open'] = 'Open question';
$string['qtype_crossmultiplication'] = 'Rule of three';
$string['qtype_tradecosting'] = 'Tabellenkalkulation';
$string['qtype_unknown'] = 'Unknown question type';
$string['taxonomylevels_placeholder'] = 'Add a taxonomy level...';
$string['taxonomylevels_none'] = 'No taxonomy levels selected';
$string['questiontypes_placeholder'] = 'Add a question type...';
$string['questiontypes_none'] = 'No question types in this grade';
$string['operators'] = 'Task operators';
$string['operators_help'] = 'Which operator taxonomy the practice questions follow. Standard allows any task operator; IHK limits questions to the four IHK operators (name, describe, explain, elaborate).';
$string['operators_standard'] = 'Standard (any operator)';
$string['operators_ihk'] = 'IHK (name, describe, explain, elaborate)';
$string['anticheat'] = 'Cheat prevention';
$string['anticheat_help'] = 'When enabled, learners cannot ask the chat about a practice question the assistant has just set them.';
$string['learntempo'] = 'Learning pace';
$string['learntempo_help'] = 'Steers two things at once: how fast the difficulty of the questions rises or falls, and how fast experience points grow. A faster pace means fewer questions, quicker progress and lower AI usage; a slower pace means more questions and more points per topic. x1 is the recommended pace.';
$string['learntempo_recommended'] = '{$a} (recommended)';
$string['error_taxonomyrequired'] = 'Enable at least one taxonomy level.';
$string['error_questiontypesrequired'] = 'Select at least one question type for this difficulty grade.';
$string['jump_top'] = 'Back to top';
$string['practice_start'] = 'Start';
$string['practice_next'] = 'Next';
$string['practice_check'] = 'Check';
$string['practice_answer_placeholder'] = 'Write your answer here...';
$string['practice_nowidget'] = 'The assistant could not create a question of the selected types from the included materials. Try again, or ask your teacher to adjust the question types or the included materials.';
$string['practice_modelanswer'] = 'Model answer';
$string['practice_modelanswers'] = 'Model answers';
$string['practice_variable_placeholder'] = 'Your answer';
$string['practice_table_position'] = 'Position';
$string['practice_table_rate'] = 'Rate';
$string['practice_table_amount'] = 'Amount';
$string['choice_true'] = 'True';
$string['choice_false'] = 'False';
$string['xp'] = 'XP';
$string['learning_units'] = 'LU';
$string['practice_score'] = 'Answer score';
$string['practice_progress'] = 'Practice progress';

// Learner RAG chat UI strings.
$string['mode_switch'] = 'Interaction mode';
$string['mode_build'] = 'Build knowledge';
$string['mode_test'] = 'Test knowledge';
$string['mode_ask'] = 'Ask a question';
$string['send'] = 'Send';
$string['sources'] = 'Sources';
$string['chat_placeholder'] = 'Ask a question about the course materials...';
$string['loading_history'] = 'Loading conversation history...';
$string['resetchat'] = 'Reset chat history';
$string['resetchat_confirm'] = 'Erase your entire history with the assistant in "{$a}" — the chat, every practice question and your experience points? The assistant will forget everything you have discussed, and the next question starts a fresh conversation. This cannot be undone.';
$string['resetchat_done'] = 'Your chat, practice history and experience points have been erased.';
$string['resetchat_failed'] = 'Your conversation could not be erased right now. Please try again later.';
$string['chat_noscope'] = 'No materials are available to this assistant yet. A teacher has to index course content and switch it on in the Included column first.';
$string['guardrail_blocked_killswitch'] = 'API calls are disabled by system administrators at the moment.';
$string['guardrail_blocked_breaker'] = 'The Tarsus system is undergoing high latency and is temporarily resting. Please try again in a few minutes.';
$string['guardrail_blocked_budget'] = 'Daily query budget limits reached for this context. Pleases try again tomorrow.';
$string['guardrail_blocked_ratelimit'] = 'Too many requests. Please wait a few seconds and try again.';
$string['transport_error'] = 'Could not contact the Brain. Please check your network connection and try again.';

// Privacy.
$string['privacy:metadata'] = 'The Tarsus activity keeps the latest figures the brain reported per learner (experience points, learning units, progress) for the grade and the completion state; the conversation, indexation and usage data live in the brain and in local_tarsus.';

// The processing indicator's steps (what the brain does while a request runs).
$string['proc_ask_classify'] = 'Your question is sorted by topic and intent';
$string['proc_ask_search'] = 'Semantic search through the indexed course knowledge';
$string['proc_ask_media'] = 'Matching text, image, audio and video passages';
$string['proc_ask_select'] = 'The most relevant passages are selected';
$string['proc_ask_cube'] = 'Your position in the learning cube is read';
$string['proc_ask_dqr'] = 'The depth of explanation is tuned to your DQR level';
$string['proc_ask_compose'] = 'The answer is formulated from the sources found';
$string['proc_ask_verify'] = 'The answer is cross-checked against the original passages';
$string['proc_ask_polish'] = 'Final polish of the answer';
$string['proc_practice_cube_create'] = 'Your learning cube for this course is set up';
$string['proc_practice_baseline'] = 'Your starting level for the topic is estimated';
$string['proc_practice_evaluate'] = 'Your latest answers are evaluated';
$string['proc_practice_compare'] = 'Expected and actual hit rate compared';
$string['proc_practice_surprise'] = 'Surprising answers count more than expected ones';
$string['proc_practice_reestimate'] = 'Your topic score is re-estimated';
$string['proc_practice_align'] = 'Aligning topic, taxonomy level and kind of knowledge';
$string['proc_practice_cells'] = 'Looking for active cells with a low score';
$string['proc_practice_history'] = 'Checking the trend: growth, fluctuation, forgetting';
$string['proc_practice_action'] = 'The next best learning action is chosen';
$string['proc_practice_taxonomy'] = 'Taxonomy level: knowledge, understanding or application';
$string['proc_practice_difficulty'] = 'The difficulty is set';
$string['proc_practice_format'] = 'A fitting task format is chosen';
$string['proc_practice_generate'] = 'The task is generated from your course contents';
$string['proc_practice_operators'] = 'The question is phrased according to the operators';
$string['proc_practice_options'] = 'Answer options and explanations are worked out';
$string['proc_practice_solution'] = 'Model solution and marking scheme are fixed';
$string['proc_practice_polish'] = 'Final polish of your task';
$string['proc_learn_path_create'] = 'Your learning path for this course is set up';
$string['proc_learn_progress'] = 'Your learning progress is read';
$string['proc_learn_objectives'] = 'Reached and open learning objectives are compared';
$string['proc_learn_cells'] = 'Open cells in the learning cube are determined';
$string['proc_learn_next'] = 'Next objective: knowledge, understanding or application';
$string['proc_learn_passages'] = 'Fitting passages from your course are gathered';
$string['proc_learn_dqr'] = 'The explanations are tuned to your DQR level';
$string['proc_learn_split'] = 'The learning unit is split into single cards';
$string['proc_learn_examples'] = 'Examples and pictures are selected';
$string['proc_learn_link'] = 'Links to what you already learned are made';
$string['proc_learn_check'] = 'The learning cards are checked for clarity';
$string['proc_learn_path_update'] = 'Your learning path is carried forward';
$string['proc_learn_polish'] = 'Final polish of your learning unit';

// Grades and completion.
$string['completionprogress'] = 'Progress reached';
$string['completionprogress_enabled'] = 'Learner\'s progress reaches';
$string['completionprogress_help'] = 'The activity completes once the learner\'s progress, as the brain reports it, reaches this percentage. Test knowledge counts its practice progress, Build knowledge its learning progress, both together the brain\'s overall progress.';
$string['completiondetail:progress'] = 'Reach {$a}% progress';
$string['error_completionprogress'] = 'Enter a percentage between 1 and 100.';
$string['privacy:metadata:tarsuszero_progress'] = 'The latest figures the brain reported per learner: experience points, learning units and progress, from which the grade and the completion state are derived.';
$string['privacy:metadata:tarsuszero_progress:userid'] = 'The learner.';
$string['privacy:metadata:tarsuszero_progress:earnedxp'] = 'Experience points earned in Test knowledge.';
$string['privacy:metadata:tarsuszero_progress:totalxp'] = 'Experience points available.';
$string['privacy:metadata:tarsuszero_progress:earnedunits'] = 'Learning units earned in Build knowledge.';
$string['privacy:metadata:tarsuszero_progress:totalunits'] = 'Learning units available.';
$string['privacy:metadata:tarsuszero_progress:practiceprogress'] = 'Practice progress in percent.';
$string['privacy:metadata:tarsuszero_progress:learnprogress'] = 'Learning progress in percent.';
$string['privacy:metadata:tarsuszero_progress:progress'] = 'Overall progress in percent.';
$string['privacy:metadata:tarsuszero_progress:timemodified'] = 'When the figures were last reported.';
$string['privacy:metadata:core_grades'] = 'The grade derived from the figures is stored in the gradebook.';
$string['privacy:path:progress'] = 'Progress';
