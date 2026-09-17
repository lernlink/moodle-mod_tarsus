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

/**
 * Library of interface functions for mod_tarsuszero.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Declare which optional module features this plugin supports.
 *
 * @param string $feature One of the FEATURE_* constants.
 * @return bool|null True/false for known features, null otherwise.
 */
function tarsuszero_supports(string $feature): ?bool {
    return match ($feature) {
        FEATURE_MOD_INTRO => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_GROUPS => false,
        FEATURE_GROUPINGS => false,
        FEATURE_GRADE_HAS_GRADE => false,
        default => null,
    };
}

/**
 * Add a new tarsuszero instance.
 *
 * @param \stdClass $data The form data.
 * @param \mod_tarsuszero_mod_form|null $mform The form instance.
 * @return int The id of the new instance.
 */
function tarsuszero_add_instance(\stdClass $data, ?\mod_tarsuszero_mod_form $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->intro = $data->intro ?? '';
    $data->introformat = $data->introformat ?? FORMAT_HTML;

    return (int) $DB->insert_record('tarsuszero', $data);
}

/**
 * Update an existing tarsuszero instance.
 *
 * @param \stdClass $data The form data (carries instance id in ->instance).
 * @param \mod_tarsuszero_mod_form|null $mform The form instance.
 * @return bool Always true on success.
 */
function tarsuszero_update_instance(\stdClass $data, ?\mod_tarsuszero_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    return $DB->update_record('tarsuszero', $data);
}

/**
 * Delete a tarsuszero instance.
 *
 * @param int $id The instance id.
 * @return bool True on success, false when the instance is missing.
 */
function tarsuszero_delete_instance(int $id): bool {
    global $DB;

    if (!$DB->record_exists('tarsuszero', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('tarsuszero', ['id' => $id]);

    return true;
}

/**
 * Add the chat-reset entry to the activity's settings navigation.
 *
 * Deliberately kept out of the chat itself: erasing the conversation is a
 * deliberate act, reached through the activity menu and confirmed on its
 * own page.
 *
 * @param settings_navigation $settingsnav The settings navigation.
 * @param navigation_node|null $node The activity's navigation node.
 * @return void
 */
function tarsuszero_extend_settings_navigation(settings_navigation $settingsnav, ?navigation_node $node): void {
    $cm = $settingsnav->get_page()->cm;
    if (!$node || !$cm || !has_capability('mod/tarsuszero:resetchat', $cm->context)) {
        return;
    }

    $node->add(
        get_string('resetchat', 'mod_tarsuszero'),
        new moodle_url('/mod/tarsuszero/resetchat.php', ['id' => $cm->id]),
        navigation_node::TYPE_SETTING,
        null,
        'tarsuszeroresetchat',
        new pix_icon('i/reload', '')
    );
}
