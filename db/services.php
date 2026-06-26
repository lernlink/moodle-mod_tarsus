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
    'mod_tarsuszero_get_conversation_history' => [
        'classname'   => 'mod_tarsuszero\external\get_conversation_history',
        'methodname'  => 'execute',
        'description' => 'Retrieve conversation history for a given tarsuszero activity and user.',
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
];