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
use local_tarsus\client\transport_exception;
use local_tarsus\guardrail\blocked_exception;
use local_tarsus\guardrail\pipeline;
use local_tarsus\usage_meter;
use moodle_exception;

/**
 * External function to send a user message, query the Tarsus Brain and return the answer.
 *
 * Checks capabilites, pseudonymizes the user, invokes Tarsus with rate-limit and other
 * guardrails, registers the credit cost and records conversation history.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_chat_message extends external_api {

    /**
     * Declares parameters for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'  => new external_value(PARAM_INT, 'The course module id of the tarsuszero activity.'),
            'query' => new external_value(PARAM_RAW, 'The query that is sent to the brain.'),
        ]);
    }

    /**
     * Send student question to Tarsus using guardrails and pseudonymization.
     *
     * @param int    $cmid  The course module.
     * @param string $query The query text.
     * @return array Resulting answer, status and optional sources.
     * @throws moodle_exception
     */
    public static function execute(int $cmid, string $query): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid'  => $cmid,
            'query' => $query,
        ]);

        $querytext = trim($params['query']);
        if ($querytext === '') {
            throw new moodle_exception('empty_query', 'mod_tarsuszero');
        }

        $cm = get_coursemodule_from_id('tarsuszero', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/tarsuszero:view', $context);

        // Grounded answers cost ~6 credits according to spec.
        $estimatedcredits = 6;

        $client   = client_factory::get_client();
        $pipeline = new pipeline();

        // The query operates in the course context to maintain course-level isolation inside the shared brain.
        // Form context namespace string (course context).
        $coursecontext = \context_course::instance($cm->course);
        $namespacescope = 'course:' . $coursecontext->id;

        try {
            // Outbound API request through the guardrail pipeline.
            $response = $pipeline->execute(function() use ($client, $namespacescope, $querytext) {
                return $client->search_grounded($namespacescope, $querytext);
            }, $estimatedcredits);

            $answer  = (string) ($response['answer'] ?? '');
            $sources = $response['sources'] ?? [];
            $credits = (int) ($response['credits'] ?? 6);

            // Record local context-level usage.
            $usage = new usage_meter();
            $usage->record((int) $coursecontext->id, (int) $USER->id, 'search_grounded', $credits);

            // Save chat interaction to local DB.
            $chat = new \stdClass();
            $chat->tarsuszeroid = $cm->instance;
            $chat->userid       = $USER->id;
            $chat->query        = $querytext;
            $chat->answer       = $answer;
            $chat->sources      = json_encode($sources);
            $chat->timecreated  = time();

            $chat->id = $DB->insert_record('tarsuszero_chats', $chat);

            $formatted_sources = [];
            foreach ($sources as $src) {
                $formatted_sources[] = [
                    'title'      => (string) ($src['title'] ?? ''),
                    'resourceid' => (string) ($src['resourceid'] ?? ''),
                ];
            }

            return [
                'status'  => 'success',
                'answer'  => $answer,
                'sources' => $formatted_sources,
                'error'   => '',
            ];

        } catch (blocked_exception $e) {
            // Safe user-facing feedback for blocked guardrails.
            return [
                'status'  => 'blocked',
                'answer'  => '',
                'sources' => [],
                'error'   => get_string('guardrail_blocked_' . $e->getMessage(), 'mod_tarsuszero'),
            ];
        } catch (transport_exception $e) {
            // Safe user-facing feedback for server/connection errors.
            return [
                'status'  => 'error',
                'answer'  => '',
                'sources' => [],
                'error'   => get_string('transport_error', 'mod_tarsuszero'),
            ];
        }
    }

    /**
     * Declares return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status'  => new external_value(PARAM_ALPHA, 'Status (success, blocked, error).'),
            'answer'  => new external_value(PARAM_RAW, 'The grounded answer.'),
            'sources' => new external_multiple_structure(
                new external_single_structure([
                    'title'      => new external_value(PARAM_TEXT, 'Source title.'),
                    'resourceid' => new external_value(PARAM_RAW, 'Source resource ID.'),
                ]),
                'References/Sources used.',
                VALUE_OPTIONAL
            ),
            'error'   => new external_value(PARAM_RAW, 'Error message if failed or blocked.'),
        ]);
    }
}