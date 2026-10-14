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
use mod_tarsuszero\local\didactics;
use mod_tarsuszero\local\scope;
use moodle_exception;

/**
 * External function to generate the next learning unit (Build knowledge).
 *
 * The brain decides what comes next: the learning objectives first, then
 * one micro-learning stack of slides per call.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_learn_widget extends external_api {

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
     * Generate the next learning unit for the current user.
     *
     * @param int $cmid The course module id.
     * @return array Status, the learn items and the progress figures.
     * @throws moodle_exception
     */
    public static function execute(int $cmid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

        $cm = get_coursemodule_from_id('tarsuszero', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/tarsuszero:view', $context);

        // The teacher may have switched the mode off for this activity.
        $activity = $DB->get_record('tarsuszero', ['id' => $cm->instance], '*', MUST_EXIST);
        if (!didactics::mode_enabled($activity, 'build')) {
            return self::result('disabled', get_string('chat_modedisabled', 'mod_tarsuszero'));
        }

        // Units may only be built from what the teacher included — an
        // empty scope must never reach the API (see send_chat_message).
        $allowed = scope::allowed_resource_ids((int) $cm->instance);
        if ($allowed === []) {
            return self::result('noscope', get_string('chat_noscope', 'mod_tarsuszero'));
        }

        $client = client_factory::get_client();
        $pipeline = new pipeline();
        $coursecontext = \context_course::instance($cm->course);

        $callparams = conversation::params($cm, $allowed);

        try {
            $response = $pipeline->execute(function() use ($client, $callparams) {
                return $client->generate_learn_widget($callparams);
            }, 6);

            $items = array_values(array_filter((array) ($response['items'] ?? []), 'is_array'));
            if ($items === []) {
                return self::result('error', get_string('learn_nowidget', 'mod_tarsuszero'));
            }

            (new usage_meter())->record(
                (int) $coursecontext->id, (int) $USER->id, 'learn_next', (int) ($response['credits'] ?? 6));

            $render = static fn(string $text): string => format_text($text, FORMAT_MARKDOWN, ['context' => $context]);
            // Only the brain's own farewell ends the course: learnProgress
            // reaches 100 while objectives are still open (verified live),
            // so the figure alone must not lock the Next button.
            $done = in_array('complete', array_column($items, 'kind'), true);

            return self::result('success', '', [
                'items' => array_map(static fn(array $item): array => practice_labels::learn_item($item, $render), $items),
                'progress' => practice_labels::progress($response['progress'] ?? []),
                'complete' => $done,
            ]);

        } catch (blocked_exception $e) {
            return self::result('blocked', get_string('guardrail_blocked_' . $e->reason, 'mod_tarsuszero'));
        } catch (transport_exception $e) {
            return self::result('error', get_string('transport_error', 'mod_tarsuszero'));
        }
    }

    /**
     * Shape one result payload.
     *
     * @param string $status The result status.
     * @param string $error  The user-facing error, if any.
     * @param array  $extra  Additional keys (items, progress).
     * @return array The payload.
     */
    private static function result(string $status, string $error, array $extra = []): array {
        return $extra + ['status' => $status, 'error' => $error, 'items' => [], 'complete' => false];
    }

    /**
     * Declares return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status (success, noscope, disabled, blocked, error).'),
            'error' => new external_value(PARAM_RAW, 'Error message if failed or blocked.'),
            'items' => new external_multiple_structure(
                new external_single_structure([
                    'kind' => new external_value(PARAM_ALPHA, 'Item kind: objectives, stack, motivation, complete or message.'),
                    'id' => new external_value(PARAM_RAW, 'The widget or stack id.'),
                    'text' => new external_value(PARAM_RAW, 'Rendered HTML (complete, message).', VALUE_OPTIONAL),
                    'title' => new external_value(PARAM_TEXT, 'Title (objectives).', VALUE_OPTIONAL),
                    'message' => new external_value(PARAM_RAW, 'Rendered message HTML (objectives).', VALUE_OPTIONAL),
                    'objectives' => practice_labels::objectives_structure(),
                    'slides' => practice_labels::slides_structure(),
                ]),
                'The learn items, as many as the brain sent.'
            ),
            'progress' => practice_labels::progress_structure(),
            'complete' => new external_value(PARAM_BOOL, 'Whether the brain sent its farewell: nothing more to generate.'),
        ]);
    }
}
