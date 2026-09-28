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
use mod_tarsuszero\local\conversation;
use mod_tarsuszero\local\scope;
use moodle_exception;

/**
 * External function to reopen the whole activity feed from the brain.
 *
 * The brain is the only store of the interaction history — one interleaved
 * feed across the interaction modes, as in the Tarsus interface: chat
 * exchanges, informational messages and practice questions in their original
 * order, with the experience figures alongside. Nothing is stored locally.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_activity_feed extends external_api {

    /** @var int How many widgets one call reads back. */
    private const PAGE_SIZE = 50;

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
     * Read the activity feed and the experience figures back from the brain.
     *
     * @param int $cmid The course module id.
     * @return array Status, the ordered feed items and the progress figures.
     * @throws moodle_exception
     */
    public static function execute(int $cmid): array {
        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

        $cm = get_coursemodule_from_id('tarsuszero', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/tarsuszero:view', $context);

        // No scope means no feed to read — and no call to the brain.
        $allowed = scope::allowed_resource_ids((int) $cm->instance);
        if ($allowed === []) {
            return ['status' => 'success', 'error' => '', 'items' => []];
        }

        $client = client_factory::get_client();
        $pipeline = new pipeline();

        $callparams = conversation::params($cm, $allowed) + [
            'offset' => 0,
            'pagesize' => self::PAGE_SIZE,
        ];

        try {
            $response = $pipeline->execute(function() use ($client, $callparams) {
                return $client->retrieve_feed($callparams);
            });

            $render = static fn(string $text): string => format_text($text, FORMAT_MARKDOWN, ['context' => $context]);

            $items = [];
            foreach (($response['items'] ?? []) as $item) {
                $kind = (string) ($item['kind'] ?? '');
                if ($kind === 'message') {
                    $items[] = ['kind' => 'message', 'text' => $render((string) $item['text'])];
                    continue;
                }
                if ($kind === 'exchange') {
                    $items[] = [
                        'kind' => 'exchange',
                        'query' => (string) $item['query'],
                        'answer' => $render((string) $item['answer']),
                    ];
                    continue;
                }
                if ($kind !== 'practice') {
                    continue;
                }
                $choices = [];
                foreach (($item['choices'] ?? []) as $choice) {
                    $choices[] = [
                        'value' => (string) $choice,
                        'label' => practice_labels::choice((string) $choice, (string) $item['type']),
                    ];
                }
                $items[] = [
                    'kind' => 'practice',
                    'id' => (string) $item['id'],
                    'type' => (string) $item['type'],
                    'question' => $render((string) $item['question']),
                    'choices' => $choices,
                    'taxonomy' => practice_labels::taxonomy((string) ($item['taxonomylevel'] ?? '')),
                    'difficulty' => practice_labels::difficulty((int) ($item['difficultylevel'] ?? 0)),
                    'difficultylevel' => (int) ($item['difficultylevel'] ?? 0),
                    'variables' => array_values(array_map('strval', $item['variables'] ?? [])),
                    'answered' => !empty($item['answered']),
                    'correct' => (bool) ($item['correct'] ?? false),
                    'feedback' => $render((string) ($item['feedback'] ?? '')),
                    'useranswer' => (string) ($item['useranswer'] ?? ''),
                    'useranswers' => practice_labels::answers($item['useranswers'] ?? [], $render),
                    'answers' => practice_labels::answers($item['answers'] ?? [], $render),
                ];
            }

            return [
                'status' => 'success',
                'error' => '',
                'items' => $items,
                'progress' => practice_labels::progress($response['progress'] ?? []),
            ];

        } catch (blocked_exception $e) {
            return [
                'status' => 'blocked',
                'error' => get_string('guardrail_blocked_' . $e->reason, 'mod_tarsuszero'),
                'items' => [],
            ];
        } catch (transport_exception $e) {
            return [
                'status' => 'error',
                'error' => get_string('transport_error', 'mod_tarsuszero'),
                'items' => [],
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
            'status' => new external_value(PARAM_ALPHA, 'Status (success, blocked, error).'),
            'error' => new external_value(PARAM_RAW, 'Error message if failed or blocked.'),
            'items' => new external_multiple_structure(
                new external_single_structure([
                    'kind' => new external_value(PARAM_ALPHA, 'Item kind: message, exchange or practice.'),
                    'text' => new external_value(PARAM_RAW, 'Rendered message HTML (message).', VALUE_OPTIONAL),
                    'query' => new external_value(PARAM_RAW, 'The learner\'s question (exchange).', VALUE_OPTIONAL),
                    'answer' => new external_value(PARAM_RAW, 'Rendered answer HTML (exchange).', VALUE_OPTIONAL),
                    'id' => new external_value(PARAM_RAW, 'The widget id (practice).', VALUE_OPTIONAL),
                    'type' => new external_value(PARAM_ALPHANUMEXT, 'The question widget type (practice).', VALUE_OPTIONAL),
                    'question' => new external_value(PARAM_RAW, 'Rendered question HTML (practice).', VALUE_OPTIONAL),
                    'choices' => new external_multiple_structure(
                        new external_single_structure([
                            'value' => new external_value(PARAM_RAW, 'The answer value to submit.'),
                            'label' => new external_value(PARAM_RAW, 'The display label.'),
                        ]),
                        'Answer choices; empty for open questions (practice).',
                        VALUE_OPTIONAL
                    ),
                    'taxonomy' => new external_value(PARAM_RAW, 'Display label of the taxonomy level (practice).',
                        VALUE_OPTIONAL),
                    'difficulty' => new external_value(PARAM_RAW, 'Display label of the difficulty grade (practice).',
                        VALUE_OPTIONAL),
                    'difficultylevel' => new external_value(PARAM_INT, 'The difficulty grade (1..3; 0 unknown).',
                        VALUE_OPTIONAL),
                    'answered' => new external_value(PARAM_BOOL, 'Whether the question has been answered (practice).',
                        VALUE_OPTIONAL),
                    'correct' => new external_value(PARAM_BOOL, 'Whether the answer was judged correct (practice).',
                        VALUE_OPTIONAL),
                    'feedback' => new external_value(PARAM_RAW, 'Rendered feedback HTML (practice, answered).',
                        VALUE_OPTIONAL),
                    'useranswer' => new external_value(PARAM_RAW, 'The learner\'s answer (practice, answered).',
                        VALUE_OPTIONAL),
                    'variables' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'An asked quantity.'),
                        'Quantities a rule-of-three question asks for (practice).',
                        VALUE_OPTIONAL
                    ),
                    'useranswers' => practice_labels::answers_structure('The learner\'s per-variable answers (practice, answered).'),
                    'answers' => practice_labels::answers_structure('The model per-variable answers (practice, answered).'),
                ]),
                'The activity feed, oldest first, all interaction modes interleaved.'
            ),
            'progress' => practice_labels::progress_structure(),
        ]);
    }
}
