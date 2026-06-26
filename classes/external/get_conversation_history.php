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

namespace mod_tarsuszero\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_module;

/**
 * External function to get the conversation history for a tarsuszero activity.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_conversation_history extends external_api {

    /**
     * Declares parameters for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'The course module id of the tarsuszero activity.'),
        ]);
    }

    /**
     * Retrieve chat history from local database.
     *
     * @param int $cmid The course module id.
     * @return array List of chat message structures.
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public static function execute(int $cmid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
        ]);

        $cm = get_coursemodule_from_id('tarsuszero', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/tarsuszero:view', $context);

        $chats = $DB->get_records('tarsuszero_chats', [
            'tarsuszeroid' => $cm->instance,
            'userid'       => $USER->id,
        ], 'timecreated ASC, id ASC');

        $result = [];
        foreach ($chats as $chat) {
            $sources = [];
            if ($chat->sources !== null && $chat->sources !== '') {
                $decoded = json_decode($chat->sources, true);
                if (is_array($decoded)) {
                     foreach ($decoded as $src) {
                         $sources[] = [
                             'title'      => (string) ($src['title'] ?? ''),
                             'resourceid' => (string) ($src['resourceid'] ?? ''),
                         ];
                     }
                }
            }

            $result[] = [
                'id'          => (int) $chat->id,
                'query'       => (string) $chat->query,
                'answer'      => (string) $chat->answer,
                'sources'     => $sources,
                'timecreated' => (int) $chat->timecreated,
            ];
        }

        return $result;
    }

    /**
     * Declares return structure.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id'          => new external_value(PARAM_INT, 'Chat entry ID.'),
                'query'       => new external_value(PARAM_RAW, 'The question asked.'),
                'answer'      => new external_value(PARAM_RAW, 'The grounded answer.'),
                'sources'     => new external_multiple_structure(
                    new external_single_structure([
                        'title'      => new external_value(PARAM_TEXT, 'Source title.'),
                        'resourceid' => new external_value(PARAM_RAW, 'Namespaced source resource id.'),
                    ]),
                    'Sources list.',
                    VALUE_OPTIONAL
                ),
                'timecreated' => new external_value(PARAM_INT, 'Time this message was logged.'),
            ])
        );
    }
}