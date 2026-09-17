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

namespace mod_tarsuszero\local;

/**
 * Didactic practice settings: taxonomy levels and question types.
 *
 * The Test knowledge mode is steered by two teacher settings stored on the
 * activity record: the enabled Bloom taxonomy levels (1 Wissen, 2 Verstehen,
 * 3 Anwenden) and a map of difficulty grade (1 Einfach, 2 Mittel, 3 Schwer)
 * to the question widget types the brain may generate in that grade. The
 * difficulty has no API parameter of its own — it is expressed entirely
 * through which grades of the question_types map carry entries.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class didactics {

    /** @var string Single choice question widget type. */
    public const TYPE_SINGLECHOICE = 'singleChoiceQuestionWidget';

    /** @var string True/false question widget type. */
    public const TYPE_TRUEFALSE = 'trueFalseQuestionWidget';

    /** @var string Open question widget type. */
    public const TYPE_OPEN = 'openQuestionWidget';

    /** @var string[] Every question widget type the plugin supports. */
    public const TYPES = [self::TYPE_SINGLECHOICE, self::TYPE_TRUEFALSE, self::TYPE_OPEN];

    /** @var int[] The difficulty grades (1 Einfach, 2 Mittel, 3 Schwer). */
    public const GRADES = [1, 2, 3];

    /** @var int[] The Bloom taxonomy levels (1 Wissen, 2 Verstehen, 3 Anwenden). */
    public const TAXONOMY_LEVELS = [1, 2, 3];

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
     * @param array<int, string[]> $map Grade => question widget types.
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
     * Unknown grades and types are dropped; an empty or invalid value falls
     * back to the defaults, so legacy rows behave sensibly.
     *
     * @param string|null $encoded The stored JSON.
     * @return array<int, string[]> Grade => question widget types, at least one entry.
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
        ksort($map);
        return $map === [] ? self::default_question_types() : $map;
    }
}
