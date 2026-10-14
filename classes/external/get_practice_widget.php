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
use mod_tarsuszero\local\scoring;
use moodle_exception;

/**
 * External function to generate the next practice question (Test knowledge).
 *
 * The brain decides which question comes next within the teacher's didactic
 * settings; the correctness information never leaves the server side of the
 * evaluation call, so this response carries the question alone.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_practice_widget extends external_api {

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
     * Generate the next practice question for the current user.
     *
     * @param int $cmid The course module id.
     * @return array Status, the question widget and the progress figures.
     * @throws moodle_exception
     */
    public static function execute(int $cmid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

        $cm = get_coursemodule_from_id('tarsuszero', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/tarsuszero:view', $context);

        // The teacher may have switched the practice off for this activity.
        $activity = $DB->get_record('tarsuszero', ['id' => $cm->instance], '*', MUST_EXIST);
        if (!\mod_tarsuszero\local\didactics::mode_enabled($activity, 'test')) {
            return self::result('disabled', get_string('chat_modedisabled', 'mod_tarsuszero'));
        }

        // Questions may only be generated from what the teacher included —
        // an empty scope must never reach the API (see send_chat_message).
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
                return $client->generate_practice_widget($callparams);
            }, 6);

            $widgets = array_filter((array) ($response['widgets'] ?? []),
                static fn($widget) => is_array($widget) && (string) ($widget['id'] ?? '') !== '');
            if ($widgets === []) {
                // The brain sent no question this time; nothing to show.
                return self::result('error', get_string('practice_nowidget', 'mod_tarsuszero'));
            }

            scoring::record($activity, (int) $USER->id, (array) ($response['progress'] ?? []));

            (new usage_meter())->record(
                (int) $coursecontext->id, (int) $USER->id, 'practice_next', (int) ($response['credits'] ?? 6));

            $out = [];
            foreach ($widgets as $widget) {
                $choices = [];
                foreach (($widget['choices'] ?? []) as $choice) {
                    $choices[] = [
                        'value' => (string) $choice,
                        'label' => practice_labels::choice((string) $choice, (string) $widget['type']),
                    ];
                }
                $out[] = [
                    'id' => (string) $widget['id'],
                    'type' => (string) $widget['type'],
                    'question' => format_text((string) $widget['question'], FORMAT_MARKDOWN, ['context' => $context]),
                    'choices' => $choices,
                    'taxonomy' => practice_labels::taxonomy((string) ($widget['taxonomylevel'] ?? '')),
                    'difficulty' => practice_labels::difficulty((int) ($widget['difficultylevel'] ?? 0)),
                    'difficultylevel' => (int) ($widget['difficultylevel'] ?? 0),
                    'variables' => array_values(array_map('strval', $widget['variables'] ?? [])),
                    'table' => practice_labels::table($widget['table'] ?? []),
                    'columns' => array_values(array_map('strval', $widget['columns'] ?? [])),
                ];
            }

            return self::result('success', '', [
                'widgets' => $out,
                'progress' => practice_labels::progress($response['progress'] ?? []),
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
     * @param array  $extra  Additional keys (widget, progress).
     * @return array The payload.
     */
    private static function result(string $status, string $error, array $extra = []): array {
        return $extra + ['status' => $status, 'error' => $error];
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
            'widgets' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_RAW, 'The widget id, needed for evaluation.'),
                    'type' => new external_value(PARAM_ALPHANUMEXT, 'The question widget type.'),
                    'question' => new external_value(PARAM_RAW, 'The rendered question HTML.'),
                    'choices' => new external_multiple_structure(
                        new external_single_structure([
                            'value' => new external_value(PARAM_RAW, 'The answer value to submit.'),
                            'label' => new external_value(PARAM_RAW, 'The display label.'),
                        ]),
                        'Answer choices; empty for open questions.'
                    ),
                    'taxonomy' => new external_value(PARAM_RAW, 'Display label of the taxonomy level.'),
                    'difficulty' => new external_value(PARAM_RAW, 'Display label of the difficulty grade.'),
                    'difficultylevel' => new external_value(PARAM_INT, 'The difficulty grade (1..3; 0 unknown).'),
                    'variables' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'An asked quantity.'),
                        'Quantities a rule-of-three question asks for; empty for other types.'
                    ),
                    'table' => practice_labels::table_structure('The costing table of a spreadsheet calculation; empty for other types.'),
                    'columns' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'A column name.'),
                        'The three column names the costing table came with, to send back with the answer.',
                        VALUE_OPTIONAL
                    ),
                ]),
                'The generated question widgets, as many as the brain sent.',
                VALUE_OPTIONAL
            ),
            'progress' => practice_labels::progress_structure(),
        ]);
    }
}
