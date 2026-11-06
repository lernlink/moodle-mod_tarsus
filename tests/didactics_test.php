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

namespace mod_tarsus;

use mod_tarsus\local\didactics;

/**
 * Tests for the practice didactic settings codec.
 *
 * @package    mod_tarsus
 * @covers     \mod_tarsus\local\didactics
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class didactics_test extends \advanced_testcase {
    /**
     * Taxonomy levels survive an encode/decode round trip, normalised.
     */
    public function test_taxonomy_levels_round_trip(): void {
        $this->assertSame('1,3', didactics::encode_taxonomy_levels([3, 1]));
        $this->assertSame([1, 3], didactics::decode_taxonomy_levels('1,3'));
    }

    /**
     * Unknown taxonomy values are dropped; nothing left means defaults.
     */
    public function test_taxonomy_levels_fall_back_to_defaults(): void {
        $this->assertSame([1, 2, 3], didactics::decode_taxonomy_levels(''));
        $this->assertSame([1, 2, 3], didactics::decode_taxonomy_levels(null));
        $this->assertSame([1, 2, 3], didactics::decode_taxonomy_levels('9,0'));
        $this->assertSame([2], didactics::decode_taxonomy_levels('2,9'));
        $this->assertSame('', didactics::encode_taxonomy_levels([9]));
    }

    /**
     * The question types map survives an encode/decode round trip; a grade
     * left empty is not stored and comes back as its default, because the
     * brain requires every grade to carry at least one type.
     */
    public function test_question_types_round_trip(): void {
        $map = [
            1 => [didactics::TYPE_SINGLECHOICE],
            2 => [],
            3 => [didactics::TYPE_OPEN, didactics::TYPE_TRUEFALSE],
        ];

        $encoded = didactics::encode_question_types($map);
        $this->assertJson($encoded);
        $this->assertStringNotContainsString('"2"', $encoded);

        $this->assertSame([
            1 => [didactics::TYPE_SINGLECHOICE],
            2 => didactics::default_question_types()[2],
            3 => [didactics::TYPE_OPEN, didactics::TYPE_TRUEFALSE],
        ], didactics::decode_question_types($encoded));
    }

    /**
     * Unknown grades and widget types are dropped; nothing left means
     * defaults — the behaviour legacy rows with an empty column rely on.
     */
    public function test_question_types_fall_back_to_defaults(): void {
        $defaults = didactics::default_question_types();

        $this->assertSame($defaults, didactics::decode_question_types(''));
        $this->assertSame($defaults, didactics::decode_question_types(null));
        $this->assertSame($defaults, didactics::decode_question_types('not json'));
        $this->assertSame($defaults, didactics::decode_question_types('{"7":["bogusWidget"]}'));

        $this->assertSame(
            [1 => $defaults[1], 2 => [didactics::TYPE_TRUEFALSE], 3 => $defaults[3]],
            didactics::decode_question_types('{"2":["trueFalseQuestionWidget","bogusWidget"],"7":["openQuestionWidget"]}')
        );
    }

    /**
     * Operators and learning pace normalise to what the brain accepts.
     */
    public function test_operators_and_learn_tempo_normalise(): void {
        $this->assertSame('standard', didactics::operators(null));
        $this->assertSame('standard', didactics::operators('bogus'));
        $this->assertSame('ihk', didactics::operators(' IHK '));

        $this->assertSame(1.0, didactics::learn_tempo(null));
        $this->assertSame(1.0, didactics::learn_tempo('abc'));
        $this->assertSame(0.5, didactics::learn_tempo('0.50'));
        $this->assertSame(0.1, didactics::learn_tempo(0));
        $this->assertSame(2.0, didactics::learn_tempo(9));
    }

    /**
     * The defaults mirror the Tarsus reference configuration.
     */
    public function test_defaults_match_the_tarsus_reference(): void {
        $this->assertSame([
            1 => [didactics::TYPE_SINGLECHOICE, didactics::TYPE_TRUEFALSE],
            2 => [didactics::TYPE_SINGLECHOICE, didactics::TYPE_TRUEFALSE, didactics::TYPE_OPEN],
            3 => [didactics::TYPE_OPEN],
        ], didactics::default_question_types());
        $this->assertSame([1, 2, 3], didactics::default_taxonomy_levels());
    }

    /**
     * A new activity persists the defaults through the form pipeline
     * (generator path uses the same add_instance).
     */
    public function test_new_activity_defaults_are_persistable(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsus', ['course' => $course->id]);

        $record = $DB->get_record('tarsus', ['id' => $tz->id], '*', MUST_EXIST);

        // The generator bypasses the form, so the columns hold their DB
        // defaults — and decoding them yields the plugin defaults.
        $this->assertSame([1, 2, 3], didactics::decode_taxonomy_levels($record->taxonomylevels));
        $this->assertSame(didactics::default_question_types(), didactics::decode_question_types($record->questiontypes));
    }
}
