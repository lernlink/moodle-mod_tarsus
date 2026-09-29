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
 * Web services services registration for mod_tarsuszero.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_tarsuszero_get_activity_feed' => [
        'classname'   => 'mod_tarsuszero\external\get_activity_feed',
        'methodname'  => 'execute',
        'description' => 'Reopen the interleaved activity feed (chat and practice) from the brain.',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_tarsuszero_send_chat_message' => [
        'classname'   => 'mod_tarsuszero\external\send_chat_message',
        'methodname'  => 'execute',
        'description' => 'Send a chat message to the Tarsus Q&A API and store/retrieve response details.',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_tarsuszero_get_practice_widget' => [
        'classname'   => 'mod_tarsuszero\external\get_practice_widget',
        'methodname'  => 'execute',
        'description' => 'Generate the next practice question widget (Test knowledge mode).',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_tarsuszero_get_learn_widget' => [
        'classname'   => 'mod_tarsuszero\external\get_learn_widget',
        'methodname'  => 'execute',
        'description' => 'Generate the next learning unit (Build knowledge mode).',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_tarsuszero_submit_practice_answer' => [
        'classname'   => 'mod_tarsuszero\external\submit_practice_answer',
        'methodname'  => 'execute',
        'description' => 'Evaluate the learner\'s answer to a practice question widget.',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_tarsuszero_set_resource_action' => [
        'classname'   => 'mod_tarsuszero\external\set_resource_action',
        'methodname'  => 'execute',
        'description' => 'Trigger a per-resource index action (index, reindex, remove, retry).',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_tarsuszero_get_index_status' => [
        'classname'   => 'mod_tarsuszero\external\get_index_status',
        'methodname'  => 'execute',
        'description' => 'Return the live index status of every bit in the course for dashboard polling.',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_tarsuszero_set_resource_inclusion' => [
        'classname'   => 'mod_tarsuszero\external\set_resource_inclusion',
        'methodname'  => 'execute',
        'description' => 'Include or exclude an indexed resource from a specific tarsuszero activity scope.',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_tarsuszero_sync_page_status' => [
        'classname'   => 'mod_tarsuszero\external\sync_page_status',
        'methodname'  => 'execute',
        'description' => 'Reconcile one page of course material against the brain in a single batch call.',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];