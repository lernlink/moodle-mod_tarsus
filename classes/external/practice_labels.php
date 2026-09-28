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

use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_tarsus\client\client_interface;

/**
 * Display labels and shared structures for the practice external functions.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class practice_labels {

    /**
     * Localise a taxonomy level as reported by the brain (e.g. 'WISSEN').
     *
     * @param string $level The brain's taxonomy level value.
     * @return string The display label; '' when unknown.
     */
    public static function taxonomy(string $level): string {
        return match (strtoupper($level)) {
            'WISSEN' => get_string('taxonomy_knowledge', 'mod_tarsuszero'),
            'VERSTEHEN' => get_string('taxonomy_understanding', 'mod_tarsuszero'),
            'ANWENDEN' => get_string('taxonomy_application', 'mod_tarsuszero'),
            default => '',
        };
    }

    /**
     * Localise a difficulty grade (1..3).
     *
     * @param int $grade The difficulty grade.
     * @return string The display label; '' when unknown.
     */
    public static function difficulty(int $grade): string {
        return match ($grade) {
            1 => get_string('difficulty_easy', 'mod_tarsuszero'),
            2 => get_string('difficulty_medium', 'mod_tarsuszero'),
            3 => get_string('difficulty_hard', 'mod_tarsuszero'),
            default => '',
        };
    }

    /**
     * Localise a choice value: true/false widgets answer with booleans.
     *
     * @param string $value The raw choice value.
     * @param string $widgettype The question widget type.
     * @return string The display label.
     */
    public static function choice(string $value, string $widgettype): string {
        if ($widgettype === 'trueFalseQuestionWidget') {
            if (strcasecmp($value, 'true') === 0) {
                return get_string('choice_true', 'mod_tarsuszero');
            }
            if (strcasecmp($value, 'false') === 0) {
                return get_string('choice_false', 'mod_tarsuszero');
            }
        }
        return $value;
    }

    /**
     * Shape per-variable answers (rule of three) for the wire.
     *
     * @param array    $answers The client's list of {variable, answer, iscorrect, clarification}.
     * @param callable $render  Renders Markdown clarifications to HTML.
     * @return array<int, array{variable: string, answer: string, iscorrect: bool, clarification: string}>
     */
    public static function answers(array $answers, callable $render): array {
        $out = [];
        foreach ($answers as $entry) {
            $out[] = [
                'variable' => (string) ($entry['variable'] ?? ''),
                'answer' => (string) ($entry['answer'] ?? ''),
                'iscorrect' => !empty($entry['iscorrect']),
                'clarification' => $render((string) ($entry['clarification'] ?? '')),
            ];
        }
        return $out;
    }

    /**
     * The wire structure of per-variable answers.
     *
     * @param string $description What the list holds.
     * @return external_multiple_structure
     */
    public static function answers_structure(string $description): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'variable' => new external_value(PARAM_RAW, 'The asked quantity.'),
                'answer' => new external_value(PARAM_RAW, 'The answer text.'),
                'iscorrect' => new external_value(PARAM_BOOL, 'Whether this answer was judged correct.'),
                'clarification' => new external_value(PARAM_RAW, 'Rendered clarification HTML, may be empty.'),
            ]),
            $description,
            VALUE_OPTIONAL
        );
    }

    /**
     * Shape the progress figures for the wire.
     *
     * @param array $progress The client's progress array.
     * @return array{earnedxp: float, totalxp: float, practiceprogress: float}
     */
    public static function progress(array $progress): array {
        return [
            'earnedxp' => (float) ($progress['earnedxp'] ?? 0),
            'totalxp' => (float) ($progress['totalxp'] ?? 0),
            'practiceprogress' => (float) ($progress['practiceprogress'] ?? 0),
        ];
    }

    /**
     * The wire structure of the progress figures.
     *
     * @return external_single_structure
     */
    public static function progress_structure(): external_single_structure {
        return new external_single_structure([
            'earnedxp' => new external_value(PARAM_FLOAT, 'Experience points earned so far.'),
            'totalxp' => new external_value(PARAM_FLOAT, 'Experience points available in this activity.'),
            'practiceprogress' => new external_value(PARAM_FLOAT, 'Practice progress in percent.'),
        ], 'Experience and progress figures.', VALUE_OPTIONAL);
    }

    /**
     * Whether a widget type is one the practice mode supports.
     *
     * @param string $widgettype The question widget type.
     * @return bool True when supported.
     */
    public static function is_supported_type(string $widgettype): bool {
        return in_array($widgettype, client_interface::QUESTION_WIDGET_TYPES, true);
    }
}
