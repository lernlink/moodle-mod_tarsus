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
 * Erase the current user's conversation with the assistant, after confirming.
 *
 * The conversation lives in the brain only; erasing it there removes both
 * the transcript and the assistant's memory of it, so the next question
 * starts a fresh conversation. Each user can only ever erase their own,
 * and students cannot erase at all: the conversation carries their
 * learning progress.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_tarsus\client\client_factory;
use local_tarsus\client\transport_exception;
use local_tarsus\guardrail\blocked_exception;
use local_tarsus\guardrail\pipeline;
use local_tarsus\pseudonymizer;
use mod_tarsuszero\local\scope;

$id = required_param('id', PARAM_INT); // Course module id.
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('tarsuszero', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$moduleinstance = $DB->get_record('tarsuszero', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/tarsuszero:resetchat', $context);

$pageurl = new moodle_url('/mod/tarsuszero/resetchat.php', ['id' => $cm->id]);
$viewurl = new moodle_url('/mod/tarsuszero/view.php', ['id' => $cm->id]);

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('resetchat', 'mod_tarsuszero'));
$PAGE->set_heading(format_string($course->fullname));

if ($confirm) {
    require_sesskey();

    try {
        (new pipeline())->execute(fn() => client_factory::get_client()->reset_conversation(
            scope::conversation_context_id((int) $cm->instance),
            pseudonymizer::pseudonymize((int) $USER->id)
        ));
    } catch (blocked_exception | transport_exception $e) {
        redirect($viewurl, get_string('resetchat_failed', 'mod_tarsuszero'), null,
            \core\output\notification::NOTIFY_ERROR);
    }

    // The brain forgot everything, so the grade and the figures go too.
    \mod_tarsuszero\local\scoring::clear($moduleinstance, (int) $USER->id);

    redirect($viewurl, get_string('resetchat_done', 'mod_tarsuszero'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('resetchat', 'mod_tarsuszero'));

echo $OUTPUT->confirm(
    get_string('resetchat_confirm', 'mod_tarsuszero', format_string($moduleinstance->name)),
    new single_button(
        new moodle_url($pageurl, ['confirm' => 1, 'sesskey' => sesskey()]),
        get_string('resetchat', 'mod_tarsuszero'),
        'post'
    ),
    $viewurl
);

echo $OUTPUT->footer();
