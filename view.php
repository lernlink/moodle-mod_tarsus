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
 * Prints a single tarsuszero activity.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_tarsus\task\index_course_task;
use mod_tarsuszero\output\teacher_dashboard;

$id = required_param('id', PARAM_INT); // Course module id.
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('tarsuszero', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$moduleinstance = $DB->get_record('tarsuszero', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/tarsuszero:view', $context);

$canmanage = has_capability('mod/tarsuszero:manage', $context);

$viewurl = new moodle_url('/mod/tarsuszero/view.php', ['id' => $cm->id]);

// Handle the "index this course" action.
if ($action === 'indexcourse') {
    require_sesskey();
    require_capability('mod/tarsuszero:manage', $context);

    index_course_task::queue((int) $course->id);

    redirect(
        $viewurl,
        get_string('indexqueued', 'mod_tarsuszero'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$PAGE->set_url($viewurl);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($moduleinstance->name));

if (trim($moduleinstance->intro ?? '') !== '') {
    echo $OUTPUT->box(
        format_module_intro('tarsuszero', $moduleinstance, $cm->id),
        'generalbox',
        'intro'
    );
}

if ($canmanage) {
    $dashboard = new teacher_dashboard($cm->id, (int) $course->id, $canmanage);
    echo $OUTPUT->render_from_template(
        'mod_tarsuszero/teacher_dashboard',
        $dashboard->export_for_template($OUTPUT)
    );
} else {
    $chat = new \mod_tarsuszero\output\learner_chat($cm->id);
    echo $OUTPUT->render_from_template(
        'mod_tarsuszero/learner_chat',
        $chat->export_for_template($OUTPUT)
    );
}

echo $OUTPUT->footer();
