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
 * Lists all tarsus instances in a course.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = get_course($id);
require_course_login($course);

$context = context_course::instance($course->id);

$PAGE->set_url('/mod/tarsus/index.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_tarsus'));

$instances = get_all_instances_in_course('tarsus', $course);

if (empty($instances)) {
    echo $OUTPUT->notification(get_string('nomaterials', 'mod_tarsus'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [get_string('tarsusname', 'mod_tarsus')];
$table->data = [];

foreach ($instances as $instance) {
    $url = new moodle_url('/mod/tarsus/view.php', ['id' => $instance->coursemodule]);
    $name = format_string($instance->name);
    $link = $instance->visible
        ? html_writer::link($url, $name)
        : html_writer::link($url, $name, ['class' => 'dimmed']);
    $table->data[] = [$link];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
