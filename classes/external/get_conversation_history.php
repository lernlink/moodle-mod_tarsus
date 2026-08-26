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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use context_module;
use local_tarsus\client\client_factory;
use local_tarsus\guardrail\blocked_exception;
use local_tarsus\guardrail\pipeline;
use local_tarsus\material\resource_id;
use local_tarsus\pseudonymizer;
use mod_tarsuszero\local\scope;
use moodle_exception;

/**
 * External function to get the conversation history for a tarsuszero activity.
 *
 * The brain is the only store of the dialogue — it already keeps every turn
 * for its own memory, so the plugin reads the conversation back from there
 * instead of maintaining a second copy that could drift.
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

    /** @var int Conversation turns fetched per page open. */
    public const PAGE_SIZE = 50;

    /**
     * Read the user's conversation for this activity back from the brain.
     *
     * @param int $cmid The course module id.
     * @return array List of chat message structures, oldest first.
     * @throws moodle_exception
     */
    public static function execute(int $cmid): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
        ]);

        $cm = get_coursemodule_from_id('tarsuszero', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/tarsuszero:view', $context);

        // The t0 surface requires the scope even to read. With nothing
        // included there is nothing to converse about — and nothing to show.
        $allowed = scope::allowed_resource_ids((int) $cm->instance);
        if ($allowed === []) {
            return [];
        }

        try {
            $response = (new pipeline())->execute(fn() => client_factory::get_client()->retrieve_widgets([
                'contextid' => resource_id::site_token() . ':tarsuszero:' . (int) $cm->instance,
                'userid' => pseudonymizer::pseudonymize((int) $USER->id),
                'resourceids' => $allowed,
                'offset' => 0,
                'pagesize' => self::PAGE_SIZE,
            ]));
        } catch (blocked_exception $e) {
            throw new moodle_exception('blocked_' . $e->reason, 'local_tarsus');
        }

        $result = [];
        foreach (($response['turns'] ?? []) as $i => $turn) {
            $result[] = [
                'id'          => $i + 1,
                'query'       => (string) ($turn['query'] ?? ''),
                // The brain speaks Markdown; render it to clean HTML the
                // same way a fresh answer is rendered.
                'answer'      => format_text((string) ($turn['answer'] ?? ''), FORMAT_MARKDOWN, ['context' => $context]),
                'sources'     => [],
                'timecreated' => (int) ($turn['timecreated'] ?? 0),
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