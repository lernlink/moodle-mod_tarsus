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

/**
 * Prints a single tarsus activity.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_tarsus\output\teacher_dashboard;

$id = required_param('id', PARAM_INT); // Course module id.
$page = optional_param('page', 0, PARAM_INT); // Zero-based reconciliation page.

$cm = get_coursemodule_from_id('tarsus', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$moduleinstance = $DB->get_record('tarsus', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/tarsus:view', $context);

$canmanage = has_capability('mod/tarsus:manage', $context);

$viewurl = new moodle_url('/mod/tarsus/view.php', ['id' => $cm->id]);


$PAGE->set_url($viewurl);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
// Same centred, width-limited layout as core activities (Page, Lesson):
// in Boost 5.3 the course index then floats as a panel instead of filling
// the whole left side.
$PAGE->add_body_class('limitedwidth');

echo $OUTPUT->header();
// No extra heading here: the page header already carries the activity name,
// and every line saved above the chat is room for its bubbles.

if (trim($moduleinstance->intro ?? '') !== '') {
    echo $OUTPUT->box(
        format_module_intro('tarsus', $moduleinstance, $cm->id),
        'generalbox',
        'intro'
    );
}

if ($canmanage) {
    $dashboard = new teacher_dashboard($cm->id, (int) $course->id, $canmanage, $page);
    echo $OUTPUT->render_from_template(
        'mod_tarsus/teacher_dashboard',
        $dashboard->export_for_template($OUTPUT)
    );

    // Show the learner chat below the dashboard so managers can verify answers
    // without leaving the page.
    echo $OUTPUT->heading(get_string('previewchat', 'mod_tarsus'), 4, 'mt-4');
}

$chat = new \mod_tarsus\output\learner_chat($cm->id);
echo $OUTPUT->render_from_template(
    'mod_tarsus/learner_chat',
    $chat->export_for_template($OUTPUT)
);

echo $OUTPUT->footer();
