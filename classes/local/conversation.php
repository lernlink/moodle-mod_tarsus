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

namespace mod_tarsus\local;

use local_tarsus\pseudonymizer;

/**
 * Shared t0 call identity: context, robot, user and didactic settings.
 *
 * Every t0 surface call (chat, practice) travels with the same vocabulary,
 * following the Tarsus developer's instructions: the context is the activity
 * (site-scoped id, so two sites on one brain cannot share a conversation),
 * the robot identity is a global admin setting, and the user travels as
 * pseudonym + display name + interface language.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class conversation {

    /** @var string[] Languages the Tarsus t0 surface supports. */
    private const LANGUAGES = [
        'en', 'de', 'fr', 'ar', 'zh', 'it', 'es', 'he', 'ja', 'nl', 'tr', 'uk', 'ru',
    ];

    /**
     * Build the common t0 call parameters for one activity and user.
     *
     * Carries the activity's complete didactic settings — the chat client
     * ignores the practice-only keys (taxonomylevels, questiontypes) and
     * vice versa, so both surfaces share this one builder.
     *
     * @param \stdClass $cm      The course module record.
     * @param string[]  $allowed The resource ids allowed to ground the call.
     * @return array<string, mixed> Parameters for the t0 client methods, without 'query'.
     */
    public static function params(\stdClass $cm, array $allowed): array {
        global $DB, $USER;

        $activity = $DB->get_record('tarsus', ['id' => $cm->instance], '*', MUST_EXIST);
        $name = trim(format_string($activity->name));

        $description = trim(html_to_text((string) $activity->intro, 0, false));
        if ($description === '') {
            // The brain requires a non-empty description.
            $description = $name;
        }

        $robotname = trim((string) get_config('local_tarsus', 'robot_name'));
        $robotname = $robotname !== '' ? $robotname : 'Tarsus';
        $robotid = trim((string) get_config('local_tarsus', 'robot_id'));
        $robotdescription = trim((string) get_config('local_tarsus', 'robot_description'));

        return [
            'contextid' => scope::conversation_context_id((int) $cm->instance),
            'contextname' => $name,
            'contextdescription' => $description,
            // The teacher's didactic settings for this activity.
            'conversationstyle' => (int) ($activity->conversationstyle ?? 3),
            'dqr' => (int) ($activity->dqr ?? 6),
            'industry' => trim((string) ($activity->industry ?? '')),
            // Full-text search stays off: the setting was withdrawn from the
            // activity form, semantic retrieval alone grounds the answers.
            'fulltextsearch' => false,
            'taxonomylevels' => didactics::decode_taxonomy_levels($activity->taxonomylevels ?? ''),
            'questiontypes' => didactics::decode_question_types($activity->questiontypes ?? ''),
            'operators' => didactics::operators($activity->operators ?? null),
            'gif' => !empty($activity->gifenabled),
            'learningmethod' => didactics::learning_method($activity->learningmethod ?? null),
            'anticheat' => !empty($activity->anticheat),
            'learntempo' => didactics::learn_tempo($activity->learntempo ?? null),
            'robotname' => $robotname,
            'robotid' => $robotid !== '' ? $robotid : $robotname,
            'robotdescription' => $robotdescription !== '' ? $robotdescription : $robotname,
            'userid' => pseudonymizer::pseudonymize((int) $USER->id),
            'username' => fullname($USER),
            'userlanguage' => self::language(),
            'resourceids' => $allowed,
        ];
    }

    /**
     * The user's interface language, reduced to what the t0 surface supports.
     *
     * @return string A supported two-letter code; 'de' when unsupported.
     */
    public static function language(): string {
        // Language packs may carry variants ('de_du', 'en_us'); the brain
        // wants the plain code.
        $language = substr(current_language(), 0, 2);

        return in_array($language, self::LANGUAGES, true) ? $language : 'de';
    }
}
