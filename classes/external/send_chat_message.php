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
use mod_tarsuszero\local\conversation;
use mod_tarsuszero\local\scope;
use moodle_exception;

/**
 * External function to send a user message, query the Tarsus Brain and return the answer.
 *
 * Checks capabilities, pseudonymizes the user, invokes Tarsus with rate-limit and
 * other guardrails, and registers the credit cost. The conversation itself lives
 * in the brain; the history endpoint reads it back from there.
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
            'replytowidget' => new external_value(PARAM_ALPHANUMEXT,
                'The id of the widget (e.g. a slide of a learning unit) the question is about; empty for none.',
                VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Send student question to Tarsus using guardrails and pseudonymization.
     *
     * @param int    $cmid          The course module.
     * @param string $query         The query text.
     * @param string $replytowidget The widget the question is about ("Ask about this"); empty for none.
     * @return array Resulting answer, status and optional sources.
     * @throws moodle_exception
     */
    public static function execute(int $cmid, string $query, string $replytowidget = ''): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid'  => $cmid,
            'query' => $query,
            'replytowidget' => $replytowidget,
        ]);

        $querytext = trim($params['query']);
        if ($querytext === '') {
            throw new moodle_exception('empty_query', 'mod_tarsuszero');
        }

        $cm = get_coursemodule_from_id('tarsuszero', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/tarsuszero:view', $context);

        // The teacher may have switched the chat off for this activity.
        $activity = $DB->get_record('tarsuszero', ['id' => $cm->instance], '*', MUST_EXIST);
        if (!\mod_tarsuszero\local\didactics::mode_enabled($activity, 'ask')) {
            return [
                'status'  => 'disabled',
                'answer'  => '',
                'sources' => [],
                'error'   => get_string('chat_modedisabled', 'mod_tarsuszero'),
            ];
        }

        // The answer may only be grounded in what the teacher included in
        // this activity — and of that, only what is actually live in the
        // brain right now. An empty scope must never reach the API: the
        // brain reads an empty restriction list as "search everything",
        // which would cross course boundaries on the shared brain.
        $allowed = scope::allowed_resource_ids((int) $cm->instance);
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

        $chatparams = conversation::params($cm, $allowed) + ['query' => $querytext];
        if (trim($params['replytowidget']) !== '') {
            // The learner asked about one slide: the brain grounds the answer there.
            $chatparams['replytowidget'] = trim($params['replytowidget']);
        }

        try {
            // Outbound API request through the guardrail pipeline. The brain
            // keeps the dialogue memory per (context, user), so only the new
            // question travels.
            $response = $pipeline->execute(function() use ($client, $chatparams) {
                return $client->generate_widgets($chatparams);
            }, $estimatedcredits);

            $answer  = (string) ($response['answer'] ?? '');
            $credits = (int) ($response['credits'] ?? 6);

            // Informational widgets (welcome and whatever Tarsus adds) are
            // relayed exactly as often as Tarsus sends them — when they
            // appear is its decision, not ours.
            $messages = array_values(array_filter((array) ($response['messages'] ?? []),
                static fn($message): bool => is_array($message) && trim((string) ($message['text'] ?? '')) !== ''));

            // Record local context-level usage.
            $usage = new usage_meter();
            $usage->record((int) $coursecontext->id, (int) $USER->id, 'chat_ask', $credits);

            // The conversation itself is not stored here: the brain keeps
            // every turn (that is its dialogue memory), and the history
            // endpoint reads it back from there.

            $render = static fn(string $text): string => format_text($text, FORMAT_MARKDOWN, ['context' => $context]);

            return [
                'status'   => 'success',
                // The brain answers in Markdown (the response says so
                // explicitly); render it through Moodle's own converter so
                // the output is real HTML and has passed the XSS cleaner.
                'answer'   => $render($answer),
                'messages' => array_map(static fn(array $message): array => [
                    'title' => (string) ($message['title'] ?? ''),
                    'text' => $render((string) $message['text']),
                ], $messages),
                'sources'  => [],
                'error'    => '',
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
     * Declares return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status'  => new external_value(PARAM_ALPHA, 'Status (success, noscope, disabled, blocked, error).'),
            'answer'  => new external_value(PARAM_RAW, 'The grounded answer.'),
            'messages' => new external_multiple_structure(
                new external_single_structure([
                    'title' => new external_value(PARAM_TEXT, 'The widget\'s title (the welcome carries the activity name).'),
                    'text' => new external_value(PARAM_RAW, 'Rendered informational message from the assistant (e.g. welcome).'),
                ]),
                'Informational widgets sent alongside the answer, shown before it.',
                VALUE_OPTIONAL
            ),
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