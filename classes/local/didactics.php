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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_tarsus\local;

/**
 * Didactic practice settings: taxonomy levels and question types.
 *
 * The Test knowledge mode is steered by two teacher settings stored on the
 * activity record: the enabled Bloom taxonomy levels (1 Wissen, 2 Verstehen,
 * 3 Anwenden) and a map of difficulty grade (1 Einfach, 2 Mittel, 3 Schwer)
 * to the question widget types the brain may generate in that grade. The
 * difficulty has no API parameter of its own — it is expressed entirely
 * through the question_types map, and the brain requires every grade to
 * carry at least one type (verified live: an empty grade list is rejected).
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class didactics {
    /** @var string Single choice question widget type. */
    public const TYPE_SINGLECHOICE = 'singleChoiceQuestionWidget';

    /** @var string True/false question widget type. */
    public const TYPE_TRUEFALSE = 'trueFalseQuestionWidget';

    /** @var string Open question widget type. */
    public const TYPE_OPEN = 'openQuestionWidget';

    /** @var string Rule-of-three (Dreisatz) question widget type. */
    public const TYPE_CROSSMULTIPLICATION = 'crossMultiplicationQuestionWidget';

    /** @var string Spreadsheet calculation (Tabellenkalkulation) question widget type. */
    public const TYPE_TRADECOSTING = 'tradeCostingQuestionWidget';

    /** @var string[] Every question widget type the plugin supports. */
    public const TYPES = [
        self::TYPE_SINGLECHOICE,
        self::TYPE_TRUEFALSE,
        self::TYPE_OPEN,
        self::TYPE_CROSSMULTIPLICATION,
        self::TYPE_TRADECOSTING,
    ];

    /** @var int[] The difficulty grades (1 Einfach, 2 Mittel, 3 Schwer). */
    public const GRADES = [1, 2, 3];

    /** @var int[] The Bloom taxonomy levels (1 Wissen, 2 Verstehen, 3 Anwenden). */
    public const TAXONOMY_LEVELS = [1, 2, 3];

    /** @var string Operator taxonomy allowing any task operator. */
    public const OPERATORS_STANDARD = 'standard';

    /** @var string Operator taxonomy limited to the four IHK operators (nennen, beschreiben, erklären, erläutern). */
    public const OPERATORS_IHK = 'ihk';

    /** @var string[] The operator taxonomies the brain knows. */
    public const OPERATORS = [self::OPERATORS_STANDARD, self::OPERATORS_IHK];

    /** @var float[] The learning pace steps offered; 1 is the recommended pace. */
    public const LEARN_TEMPOS = [0.1, 0.25, 0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0];

    /** @var float The recommended learning pace. */
    public const LEARN_TEMPO_DEFAULT = 1.0;

    /** @var string[] The interaction modes a teacher can switch, in switcher order. */
    public const MODES = ['build', 'test', 'ask'];

    /** @var string The micro-learning didactic concept (the brain's learning_method). */
    public const METHOD_MICROLEARNING = 'micro-learning';

    /** @var string[] The didactic concepts the brain knows for Build knowledge. */
    public const LEARNING_METHODS = [self::METHOD_MICROLEARNING];

    /** @var string The trainer shapes the output: text answers and slide stacks (the brain's default). */
    public const INTERACTION_TRAINERDRIVEN = 'trainer-driven';

    /** @var string The agent shapes the output: interactive web cards (genericWebWidget). */
    public const INTERACTION_AIDRIVEN = 'ai-driven';

    /** @var string[] The interaction types the brain knows (interaction_type). */
    public const INTERACTION_TYPES = [self::INTERACTION_TRAINERDRIVEN, self::INTERACTION_AIDRIVEN];

    /**
     * Normalise a stored didactic concept.
     *
     * @param string|null $value The stored value.
     * @return string A known concept; micro-learning when unknown.
     */
    public static function learning_method(?string $value): string {
        $value = strtolower(trim((string) $value));
        return in_array($value, self::LEARNING_METHODS, true) ? $value : self::METHOD_MICROLEARNING;
    }

    /**
     * Normalise a stored interaction type.
     *
     * @param string|null $value The stored value.
     * @return string A known type; trainer-driven when unknown (the brain's own default).
     */
    public static function interaction_type(?string $value): string {
        $value = strtolower(trim((string) $value));
        return in_array($value, self::INTERACTION_TYPES, true) ? $value : self::INTERACTION_TRAINERDRIVEN;
    }

    /**
     * Whether an interaction mode is switched on for an activity.
     *
     * Every mode defaults to on when a legacy row carries no column.
     *
     * @param \stdClass $activity The tarsus record.
     * @param string    $mode     One of {@see MODES}.
     * @return bool True when learners may use the mode.
     */
    public static function mode_enabled(\stdClass $activity, string $mode): bool {
        $column = $mode . 'enabled';
        if (!property_exists($activity, $column) || $activity->{$column} === null) {
            return true;
        }
        return !empty($activity->{$column});
    }

    /**
     * Normalise a stored operator taxonomy.
     *
     * @param string|null $value The stored value.
     * @return string A known taxonomy; 'standard' when unknown.
     */
    public static function operators(?string $value): string {
        $value = strtolower(trim((string) $value));
        return in_array($value, self::OPERATORS, true) ? $value : self::OPERATORS_STANDARD;
    }

    /**
     * Normalise a stored learning pace into the brain's 0.1-2 range.
     *
     * @param mixed $value The stored value.
     * @return float The pace; the recommended one when unset or invalid.
     */
    public static function learn_tempo($value): float {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return self::LEARN_TEMPO_DEFAULT;
        }
        return max(0.1, min(2.0, round((float) $value, 2)));
    }

    /**
     * The language string key of a question widget type's display label.
     *
     * @param string $type The question widget type.
     * @return string The string key in mod_tarsus.
     */
    public static function type_label_key(string $type): string {
        return match ($type) {
            self::TYPE_SINGLECHOICE => 'qtype_singlechoice',
            self::TYPE_TRUEFALSE => 'qtype_truefalse',
            self::TYPE_OPEN => 'qtype_open',
            self::TYPE_CROSSMULTIPLICATION => 'qtype_crossmultiplication',
            self::TYPE_TRADECOSTING => 'qtype_tradecosting',
            default => 'qtype_unknown',
        };
    }

    /**
     * The default taxonomy levels: all of them.
     *
     * @return int[] Taxonomy levels.
     */
    public static function default_taxonomy_levels(): array {
        return self::TAXONOMY_LEVELS;
    }

    /**
     * The default question types per difficulty grade.
     *
     * Mirrors the Tarsus reference configuration: closed formats in the
     * easy grade, everything in the middle, open questions only when hard.
     *
     * @return array<int, string[]> Grade => question widget types.
     */
    public static function default_question_types(): array {
        return [
            1 => [self::TYPE_SINGLECHOICE, self::TYPE_TRUEFALSE],
            2 => [self::TYPE_SINGLECHOICE, self::TYPE_TRUEFALSE, self::TYPE_OPEN],
            3 => [self::TYPE_OPEN],
        ];
    }

    /**
     * Encode taxonomy levels for storage.
     *
     * @param int[] $levels Taxonomy levels (subset of 1..3).
     * @return string CSV, e.g. '1,3'.
     */
    public static function encode_taxonomy_levels(array $levels): string {
        $levels = array_values(array_intersect(array_map('intval', $levels), self::TAXONOMY_LEVELS));
        sort($levels);
        return implode(',', $levels);
    }

    /**
     * Decode stored taxonomy levels.
     *
     * Unknown values are dropped; an empty or invalid value falls back to
     * the defaults, so legacy rows behave sensibly.
     *
     * @param string|null $encoded The stored CSV.
     * @return int[] Taxonomy levels, at least one.
     */
    public static function decode_taxonomy_levels(?string $encoded): array {
        $levels = array_values(array_intersect(
            array_map('intval', explode(',', (string) $encoded)),
            self::TAXONOMY_LEVELS
        ));
        sort($levels);
        return $levels === [] ? self::default_taxonomy_levels() : $levels;
    }

    /**
     * Encode the question types map for storage.
     *
     * @param array $map Grade => list of question widget types.
     * @return string JSON with string keys, e.g. {"1":["singleChoiceQuestionWidget"]}.
     */
    public static function encode_question_types(array $map): string {
        $clean = [];
        foreach (self::GRADES as $grade) {
            $types = array_values(array_intersect($map[$grade] ?? [], self::TYPES));
            if ($types !== []) {
                $clean[(string) $grade] = $types;
            }
        }
        return json_encode($clean);
    }

    /**
     * Decode the stored question types map.
     *
     * Unknown grades and types are dropped. The brain requires every grade
     * to carry at least one type, so a grade that ends up empty — a legacy
     * row, an invalid value — takes its default; the result is always a
     * complete, sendable map.
     *
     * @param string|null $encoded The stored JSON.
     * @return array<int, string[]> Grade => question widget types, every grade present.
     */
    public static function decode_question_types(?string $encoded): array {
        $decoded = json_decode((string) $encoded, true);
        $map = [];
        if (is_array($decoded)) {
            foreach ($decoded as $grade => $types) {
                $grade = (int) $grade;
                if (!in_array($grade, self::GRADES, true) || !is_array($types)) {
                    continue;
                }
                $types = array_values(array_intersect($types, self::TYPES));
                if ($types !== []) {
                    $map[$grade] = $types;
                }
            }
        }
        foreach (self::default_question_types() as $grade => $types) {
            $map[$grade] = $map[$grade] ?? $types;
        }
        ksort($map);
        return $map;
    }
}
