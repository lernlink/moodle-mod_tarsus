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
use local_tarsus\index\repository;
use local_tarsus\index\status;
use local_tarsus\material\resolver;
use local_tarsus\pseudonymizer;
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

        // The answer may only be grounded in what the teacher included in
        // this activity — and of that, only what is actually live in the
        // brain right now. An empty scope must never reach the API: the
        // brain reads an empty restriction list as "search everything",
        // which would cross course boundaries on the shared brain.
        $allowed = self::allowed_resource_ids((int) $cm->instance);
        if ($allowed === []) {
            return [
                'status'  => 'noscope',
                'answer'  => '',
                'sources' => [],
                'error'   => get_string('chat_noscope', 'mod_tarsuszero'),
            ];
        }

        // Grounded answers cost ~6 credits according to spec.
        $estimatedcredits = 6;

        $client   = client_factory::get_client();
        $pipeline = new pipeline();
        $coursecontext = \context_course::instance($cm->course);
        $pseudonym = pseudonymizer::pseudonymize((int) $USER->id);

        try {
            // Outbound API request through the guardrail pipeline.
            $response = $pipeline->execute(function() use ($client, $querytext, $pseudonym, $allowed) {
                return $client->search_grounded($querytext, $pseudonym, $allowed);
            }, $estimatedcredits);

            $answer  = (string) ($response['answer'] ?? '');
            $sources = self::present_sources($response['sources'] ?? []);
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

            return [
                'status'  => 'success',
                'answer'  => $answer,
                'sources' => $sources,
                'error'   => '',
            ];

        } catch (blocked_exception $e) {
            // Safe user-facing feedback for blocked guardrails.
            return [
                'status'  => 'blocked',
                'answer'  => '',
                'sources' => [],
                'error'   => get_string('guardrail_blocked_' . $e->reason, 'mod_tarsuszero'),
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
     * The resource ids allowed to ground this activity's answers.
     *
     * The teacher's inclusion set, filtered down to rows the brain actually
     * holds right now. Filtering at read time means a resource that was
     * removed or replaced after being included silently drops out of scope —
     * no cleanup ordering to get wrong.
     *
     * @param int $tarsuszeroid The activity instance id.
     * @return string[] Namespaced resource ids, possibly empty.
     */
    private static function allowed_resource_ids(int $tarsuszeroid): array {
        global $DB;

        $included = $DB->get_fieldset_select(
            'tarsuszero_inclusions',
            'resourceid',
            'tarsuszeroid = :tid',
            ['tid' => $tarsuszeroid]
        );

        $repo = new repository();
        $allowed = [];
        foreach ($included as $resourceid) {
            $row = $repo->get($resourceid);
            if ($row !== null && $row->status === status::INDEXED) {
                $allowed[] = $resourceid;
            }
        }

        return $allowed;
    }

    /**
     * Shape sources for the client, filling titles in from local material.
     *
     * The brain returns bare source ids; the human-readable name lives on
     * our side of the fence.
     *
     * @param array $sources Sources as returned by the client.
     * @return array<int, array{title: string, resourceid: string}>
     */
    private static function present_sources(array $sources): array {
        $resolver = new resolver();

        $formatted = [];
        foreach ($sources as $src) {
            $resourceid = (string) ($src['resourceid'] ?? '');
            $title = (string) ($src['title'] ?? '');
            if ($title === '' && $resourceid !== '') {
                $material = $resolver->resolve_by_resource_id($resourceid);
                $title = $material !== null ? $material->title : $resourceid;
            }
            $formatted[] = ['title' => $title, 'resourceid' => $resourceid];
        }

        return $formatted;
    }

    /**
     * Declares return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status'  => new external_value(PARAM_ALPHA, 'Status (success, noscope, blocked, error).'),
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