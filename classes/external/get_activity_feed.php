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

namespace mod_tarsus\external;

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
use mod_tarsus\local\conversation;
use mod_tarsus\local\scope;
use mod_tarsus\local\scoring;
use moodle_exception;

/**
 * External function to reopen the whole activity feed from the brain.
 *
 * The brain is the only store of the interaction history — one interleaved
 * feed across the interaction modes, as in the Tarsus interface: chat
 * exchanges, informational messages and practice questions in their original
 * order, with the experience figures alongside. Nothing is stored locally.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_activity_feed extends external_api {

    /** @var int How many widgets one call reads back (the page size Tarsus recommends). */
    public const PAGE_SIZE = 10;

    /**
     * Declares parameters for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'The course module id of the tarsus activity.'),
            'offset' => new external_value(PARAM_INT,
                'How many widgets to skip, counted from the newest: 0 for the newest page.', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Read one page of the activity feed, and the experience figures, from the brain.
     *
     * The newest page comes first; older pages follow at offset multiples of
     * {@see PAGE_SIZE} as the learner scrolls up.
     *
     * @param int $cmid   The course module id.
     * @param int $offset Widgets to skip, counted from the newest.
     * @return array Status, the ordered feed items, whether an older page may
     *               follow, and the progress figures.
     * @throws moodle_exception
     */
    public static function execute(int $cmid, int $offset = 0): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid, 'offset' => $offset]);

        $cm = get_coursemodule_from_id('tarsus', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/tarsus:view', $context);

        // No scope means no feed to read — and no call to the brain.
        $activity = $DB->get_record('tarsus', ['id' => $cm->instance], '*', MUST_EXIST);
        $allowed = scope::allowed_resource_ids((int) $cm->instance);
        if ($allowed === []) {
            return ['status' => 'success', 'error' => '', 'items' => [], 'hasmore' => false];
        }

        $client = client_factory::get_client();
        $pipeline = new pipeline();

        $callparams = conversation::params($cm, $allowed) + [
            'offset' => max(0, $params['offset']),
            'pagesize' => self::PAGE_SIZE,
        ];

        try {
            $response = $pipeline->execute(function() use ($client, $callparams) {
                return $client->retrieve_feed($callparams);
            });

            scoring::record($activity, (int) $USER->id, (array) ($response['progress'] ?? []));

            $render = static fn(string $text): string => format_text($text, FORMAT_MARKDOWN, ['context' => $context]);

            $items = [];
            foreach (($response['items'] ?? []) as $item) {
                $kind = (string) ($item['kind'] ?? '');
                if ($kind === 'message') {
                    $items[] = [
                        'kind' => 'message',
                        'title' => (string) ($item['title'] ?? ''),
                        'text' => $render((string) $item['text']),
                    ];
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
                if (in_array($kind, ['objectives', 'stack', 'complete', 'motivation'], true)) {
                    $items[] = practice_labels::learn_item($item, $render);
                    continue;
                }
                if ($kind !== 'practice') {
                    continue;
                }
                $items[] = practice_labels::practice_item($item, $render);
            }

            return [
                'status' => 'success',
                'error' => '',
                'items' => $items,
                // A full page means an older one may follow.
                'hasmore' => (int) ($response['rawcount'] ?? 0) >= self::PAGE_SIZE,
                'progress' => practice_labels::progress($response['progress'] ?? []),
            ];

        } catch (blocked_exception $e) {
            return [
                'status' => 'blocked',
                'error' => get_string('guardrail_blocked_' . $e->reason, 'mod_tarsus'),
                'items' => [],
                'hasmore' => false,
            ];
        } catch (transport_exception $e) {
            return [
                'status' => 'error',
                'error' => get_string('transport_error', 'mod_tarsus'),
                'items' => [],
                'hasmore' => false,
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
                    'kind' => new external_value(PARAM_ALPHA,
                        'Item kind: message, exchange, practice, objectives, stack, motivation or complete.'),
                    'title' => new external_value(PARAM_TEXT, 'Title (objectives).', VALUE_OPTIONAL),
                    'message' => new external_value(PARAM_RAW, 'Rendered message HTML (objectives).', VALUE_OPTIONAL),
                    'objectives' => practice_labels::objectives_structure(),
                    'slides' => practice_labels::slides_structure(),
                    'text' => new external_value(PARAM_RAW, 'Rendered message HTML (message).', VALUE_OPTIONAL),
                    'query' => new external_value(PARAM_RAW, 'The learner\'s question (exchange).', VALUE_OPTIONAL),
                    'answer' => new external_value(PARAM_RAW, 'Rendered answer HTML (exchange).', VALUE_OPTIONAL),
                ] + practice_labels::practice_structure()),
                'The activity feed page, oldest first, all interaction modes interleaved.'
            ),
            'hasmore' => new external_value(PARAM_BOOL, 'Whether an older page may follow.', VALUE_OPTIONAL),
            'progress' => practice_labels::progress_structure(),
        ]);
    }
}
