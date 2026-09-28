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
 * External function to evaluate a learner's practice answer (Test knowledge).
 *
 * The evaluation is the moment the correctness information may reach the
 * browser: the brain judges the answer, and the response carries the verdict,
 * the clarifications and the updated experience figures.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submit_practice_answer extends external_api {

    /**
     * Declares parameters for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'The course module id of the tarsuszero activity.'),
            'widgetid' => new external_value(PARAM_RAW, 'The question widget id from get_practice_widget.'),
            'widgettype' => new external_value(PARAM_ALPHANUMEXT, 'The question widget type.'),
            'answer' => new external_value(PARAM_RAW, 'The learner\'s answer (single-answer question types).'),
            'answers' => new external_multiple_structure(
                new external_single_structure([
                    'variable' => new external_value(PARAM_RAW, 'The asked quantity.'),
                    'answer' => new external_value(PARAM_RAW, 'The learner\'s answer for it.'),
                ]),
                'Per-variable answers of a rule-of-three question.',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Evaluate the learner's answer to one practice question.
     *
     * @param int    $cmid       The course module id.
     * @param string $widgetid   The question widget id.
     * @param string $widgettype The question widget type.
     * @param string $answer     The learner's answer (single-answer types).
     * @param array  $answers    Per-variable answers (rule of three).
     * @return array Status, verdict, feedback, choices and progress.
     * @throws moodle_exception
     */
    public static function execute(int $cmid, string $widgetid, string $widgettype, string $answer,
            array $answers = []): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'widgetid' => $widgetid,
            'widgettype' => $widgettype,
            'answer' => $answer,
            'answers' => $answers,
        ]);

        if (trim($params['widgetid']) === '' || !practice_labels::is_supported_type($params['widgettype'])) {
            throw new moodle_exception('invalidparameter', 'debug');
        }

        // A rule-of-three question is answered per variable; every asked
        // quantity needs an answer. Other types take the single answer.
        $answertext = trim($params['answer']);
        $useranswers = [];
        if ($params['widgettype'] === \mod_tarsuszero\local\didactics::TYPE_CROSSMULTIPLICATION) {
            foreach ($params['answers'] as $entry) {
                $variable = trim((string) $entry['variable']);
                $value = trim((string) $entry['answer']);
                if ($variable === '' || $value === '') {
                    throw new moodle_exception('empty_query', 'mod_tarsuszero');
                }
                $useranswers[] = ['variable' => $variable, 'answer' => $value];
            }
            if ($useranswers === []) {
                throw new moodle_exception('empty_query', 'mod_tarsuszero');
            }
        } else if ($answertext === '') {
            throw new moodle_exception('empty_query', 'mod_tarsuszero');
        }

        $cm = get_coursemodule_from_id('tarsuszero', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/tarsuszero:view', $context);

        $allowed = scope::allowed_resource_ids((int) $cm->instance);
        if ($allowed === []) {
            return self::result('noscope', get_string('chat_noscope', 'mod_tarsuszero'));
        }

        $client = client_factory::get_client();
        $pipeline = new pipeline();
        $coursecontext = \context_course::instance($cm->course);

        $callparams = conversation::params($cm, $allowed) + [
            'widgetid' => $params['widgetid'],
            'widgettype' => $params['widgettype'],
            'useranswer' => $answertext,
            'useranswers' => $useranswers,
        ];

        try {
            $response = $pipeline->execute(function() use ($client, $callparams) {
                return $client->evaluate_practice_answer($callparams);
            }, 6);

            (new usage_meter())->record(
                (int) $coursecontext->id, (int) $USER->id, 'practice_eval', (int) ($response['credits'] ?? 6));

            $render = static fn(string $text): string => format_text($text, FORMAT_MARKDOWN, ['context' => $context]);

            $choices = [];
            foreach (($response['choices'] ?? []) as $choice) {
                $choices[] = [
                    'value' => (string) ($choice['text'] ?? ''),
                    'label' => practice_labels::choice((string) ($choice['text'] ?? ''), $params['widgettype']),
                    'iscorrect' => !empty($choice['iscorrect']),
                    'clarification' => $render((string) ($choice['clarification'] ?? '')),
                ];
            }

            return self::result('success', '', [
                'hasverdict' => $response['correct'] !== null,
                'correct' => (bool) ($response['correct'] ?? false),
                'feedback' => $render((string) ($response['feedback'] ?? '')),
                'answer' => $render(practice_labels::choice((string) ($response['answer'] ?? ''), $params['widgettype'])),
                'choices' => $choices,
                'answers' => practice_labels::answers($response['answers'] ?? [], $render),
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
     * @param array  $extra  Additional keys.
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
            'status' => new external_value(PARAM_ALPHA, 'Status (success, noscope, blocked, error).'),
            'error' => new external_value(PARAM_RAW, 'Error message if failed or blocked.'),
            'hasverdict' => new external_value(PARAM_BOOL, 'Whether the brain judged the answer.', VALUE_OPTIONAL),
            'correct' => new external_value(PARAM_BOOL, 'Whether the answer was correct.', VALUE_OPTIONAL),
            'feedback' => new external_value(PARAM_RAW, 'Rendered feedback HTML.', VALUE_OPTIONAL),
            'answer' => new external_value(PARAM_RAW, 'Rendered model answer HTML, may be empty.', VALUE_OPTIONAL),
            'choices' => new external_multiple_structure(
                new external_single_structure([
                    'value' => new external_value(PARAM_RAW, 'The choice value.'),
                    'label' => new external_value(PARAM_RAW, 'The display label.'),
                    'iscorrect' => new external_value(PARAM_BOOL, 'Whether this choice is the correct one.'),
                    'clarification' => new external_value(PARAM_RAW, 'Rendered clarification HTML.'),
                ]),
                'The choices with their verdicts; empty for open questions.',
                VALUE_OPTIONAL
            ),
            'answers' => practice_labels::answers_structure('The model per-variable answers with verdicts (rule of three).'),
            'progress' => practice_labels::progress_structure(),
        ]);
    }
}
