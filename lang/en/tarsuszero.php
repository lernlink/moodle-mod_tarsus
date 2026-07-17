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
$string['col_type'] = 'Type';
$string['col_status'] = 'Status';
$string['col_included'] = 'Included';
$string['col_actions'] = 'Actions';
$string['included_on'] = 'Included in this activity';
$string['included_off'] = 'Excluded from this activity';
$string['invalidinclusion'] = 'Only indexed resources can be included in an activity.';
$string['nomaterials'] = 'This course has no indexable material yet.';
$string['learnerplaceholder'] = 'The Tarsus Zero learning experience will appear here.';
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

// Learner RAG chat UI strings.
$string['send'] = 'Send';
$string['sources'] = 'Sources';
$string['chat_placeholder'] = 'Ask a question about the course materials...';
$string['loading_history'] = 'Loading conversation history...';
$string['chat_welcome'] = 'Hello! I am your Tarsus Zero virtual assistant. Ask me anything about your course materials, and I will find grounded answers for you!';
$string['guardrail_blocked_killswitch'] = 'API calls are disabled by system administrators at the moment.';
$string['guardrail_blocked_breaker'] = 'The Tarsus system is undergoing high latency and is temporarily resting. Please try again in a few minutes.';
$string['guardrail_blocked_budget'] = 'Daily query budget limits reached for this context. Pleases try again tomorrow.';
$string['guardrail_blocked_ratelimit'] = 'Too many requests. Please wait a few seconds and try again.';
$string['transport_error'] = 'Could not contact the Brain. Please check your network connection and try again.';

// Privacy.
$string['privacy:metadata'] = 'The Tarsus Zero activity stores no personal data itself; indexation and usage data are handled by the local_tarsus plugin.';
