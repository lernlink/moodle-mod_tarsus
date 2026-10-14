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
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_GROUPS => false,
        FEATURE_GROUPINGS => false,
        FEATURE_GRADE_HAS_GRADE => true,
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

    $data->id = (int) $DB->insert_record('tarsuszero', $data);
    tarsuszero_grade_item_update($data);

    return $data->id;
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

    $updated = $DB->update_record('tarsuszero', $data);
    tarsuszero_grade_item_update($data);

    return $updated;
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

    $activity = $DB->get_record('tarsuszero', ['id' => $id], '*', MUST_EXIST);
    tarsuszero_grade_item_delete($activity);
    $DB->delete_records('tarsuszero_progress', ['tarsuszeroid' => $id]);
    $DB->delete_records('tarsuszero_inclusions', ['tarsuszeroid' => $id]);
    $DB->delete_records('tarsuszero', ['id' => $id]);

    return true;
}

/**
 * Hand the course module its name, description and completion rule.
 *
 * The custom completion rule only counts once it sits in the module's
 * customdata; without it Moodle sees no rule and completes the activity
 * straight away.
 *
 * @param \stdClass $coursemodule The course_modules row.
 * @return cached_cm_info|null
 */
function tarsuszero_get_coursemodule_info(\stdClass $coursemodule): ?cached_cm_info {
    global $DB;

    $activity = $DB->get_record('tarsuszero', ['id' => $coursemodule->instance],
        'id, name, intro, introformat, completionprogress');
    if (!$activity) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('tarsuszero', $activity, $coursemodule->id, false);
    }
    if ((int) $coursemodule->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionprogress'] = (int) $activity->completionprogress;
    }

    return $info;
}

/**
 * Create or update the activity's grade item: one value grade, 0-100.
 *
 * @param \stdClass        $activity The tarsuszero record (id, course, name).
 * @param array|object|null $grades  Grades to store alongside, as grade_update() takes them.
 * @return int One of the GRADE_UPDATE_* results.
 */
function tarsuszero_grade_item_update(\stdClass $activity, $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = [
        'itemname' => $activity->name,
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => 100,
        'grademin' => 0,
    ];
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/tarsuszero', $activity->course, 'mod', 'tarsuszero', $activity->id, 0, $grades, $params);
}

/**
 * Remove the activity's grade item.
 *
 * @param \stdClass $activity The tarsuszero record.
 * @return int One of the GRADE_UPDATE_* results.
 */
function tarsuszero_grade_item_delete(\stdClass $activity): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/tarsuszero', $activity->course, 'mod', 'tarsuszero', $activity->id, 0, null, ['deleted' => 1]);
}

/**
 * The grades to store, from the figures the brain reported.
 *
 * @param \stdClass $activity The tarsuszero record.
 * @param int       $userid   One learner, or 0 for everyone with figures.
 * @return array<int, \stdClass> userid => {userid, rawgrade, dategraded}.
 */
function tarsuszero_get_user_grades(\stdClass $activity, int $userid = 0): array {
    global $DB;

    $conditions = ['tarsuszeroid' => $activity->id];
    if ($userid) {
        $conditions['userid'] = $userid;
    }
    $grades = [];
    foreach ($DB->get_records('tarsuszero_progress', $conditions) as $row) {
        $grade = \mod_tarsuszero\local\scoring::grade($activity, $row);
        if ($grade === null) {
            continue;
        }
        $grades[$row->userid] = (object) [
            'userid' => (int) $row->userid,
            'rawgrade' => $grade,
            'dategraded' => (int) $row->timemodified,
        ];
    }

    return $grades;
}

/**
 * Push grades to the gradebook (one learner or all), as Moodle expects.
 *
 * @param \stdClass $activity   The tarsuszero record.
 * @param int       $userid     One learner, or 0 for everyone.
 * @param bool      $nullifnone Store a null grade when the learner has none.
 * @return void
 */
function tarsuszero_update_grades(\stdClass $activity, int $userid = 0, bool $nullifnone = true): void {
    $grades = tarsuszero_get_user_grades($activity, $userid);
    if ($grades !== []) {
        tarsuszero_grade_item_update($activity, $grades);
    } else if ($userid && $nullifnone) {
        tarsuszero_grade_item_update($activity, (object) ['userid' => $userid, 'rawgrade' => null]);
    } else {
        tarsuszero_grade_item_update($activity);
    }
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

/**
 * Map the plugin's icon names onto Font Awesome classes.
 *
 * The composer icons (mode switcher and send button) render as monochrome
 * Font Awesome glyphs that inherit the button's text colour.
 *
 * @return array<string, string> Icon name => Font Awesome classes.
 */
function mod_tarsuszero_get_fontawesome_icon_map(): array {
    return [
        'mod_tarsuszero:mode_build' => 'fa-regular fa-lightbulb',
        'mod_tarsuszero:mode_test' => 'fa-solid fa-dumbbell',
        'mod_tarsuszero:mode_ask' => 'fa-regular fa-hand',
        'mod_tarsuszero:send' => 'fa-regular fa-paper-plane',
        'mod_tarsuszero:jump_top' => 'fa-solid fa-arrow-up',
        'mod_tarsuszero:audio' => 'fa-solid fa-volume-high',
        'mod_tarsuszero:audio_play' => 'fa-solid fa-play',
        'mod_tarsuszero:audio_pause' => 'fa-solid fa-pause',
        'mod_tarsuszero:taxonomy_knowledge' => 'fa-solid fa-book-open',
        'mod_tarsuszero:taxonomy_understanding' => 'fa-solid fa-brain',
        'mod_tarsuszero:taxonomy_application' => 'fa-solid fa-gears',
        // The processing indicator's step icons.
        'mod_tarsuszero:proc_diagram' => 'fa-solid fa-diagram-project',
        'mod_tarsuszero:proc_atom' => 'fa-solid fa-atom',
        'mod_tarsuszero:proc_shapes' => 'fa-solid fa-shapes',
        'mod_tarsuszero:proc_dot' => 'fa-regular fa-circle-dot',
        'mod_tarsuszero:proc_cube' => 'fa-solid fa-layer-group',
        'mod_tarsuszero:proc_gear' => 'fa-solid fa-gear',
        'mod_tarsuszero:proc_sparkle' => 'fa-solid fa-wand-magic-sparkles',
        'mod_tarsuszero:proc_notch' => 'fa-solid fa-circle-notch',
        'mod_tarsuszero:proc_sun' => 'fa-regular fa-sun',
        'mod_tarsuszero:proc_chart' => 'fa-solid fa-chart-pie',
        'mod_tarsuszero:proc_asterisk' => 'fa-solid fa-asterisk',
        'mod_tarsuszero:proc_target' => 'fa-solid fa-bullseye',
        'mod_tarsuszero:proc_crosshair' => 'fa-solid fa-crosshairs',
        'mod_tarsuszero:proc_hurricane' => 'fa-solid fa-hurricane',
        'mod_tarsuszero:proc_star' => 'fa-regular fa-star',
        'mod_tarsuszero:proc_snowflake' => 'fa-regular fa-snowflake',
        'mod_tarsuszero:proc_fan' => 'fa-solid fa-fan',
        'mod_tarsuszero:proc_seedling' => 'fa-solid fa-seedling',
        'mod_tarsuszero:proc_nodes' => 'fa-solid fa-circle-nodes',
    ];
}
