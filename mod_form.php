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
 * Instance settings form for mod_tarsuszero.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * The mod_tarsuszero instance configuration form.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_tarsuszero_mod_form extends moodleform_mod {

    /**
     * Define the form fields.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('tarsuszeroname', 'mod_tarsuszero'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // Didactic settings: how the assistant converses in this activity.
        // Deliberately separate from the site-wide local_tarsus settings
        // (connection, assistant identity, guardrails) — nothing overlaps.
        $mform->addElement('header', 'didactics', get_string('didactics', 'mod_tarsuszero'));

        $styles = [
            1 => get_string('style_closed', 'mod_tarsuszero'),
            2 => get_string('style_lexical', 'mod_tarsuszero'),
            3 => get_string('style_tutorial', 'mod_tarsuszero'),
            4 => get_string('style_socratic', 'mod_tarsuszero'),
            5 => get_string('style_open', 'mod_tarsuszero'),
        ];
        $mform->addElement('select', 'conversationstyle', get_string('conversationstyle', 'mod_tarsuszero'), $styles);
        $mform->setType('conversationstyle', PARAM_INT);
        $mform->setDefault('conversationstyle', 3);
        $mform->addHelpButton('conversationstyle', 'conversationstyle', 'mod_tarsuszero');

        $mform->addElement('select', 'dqr', get_string('dqr', 'mod_tarsuszero'),
            array_combine(range(3, 7), range(3, 7)));
        $mform->setType('dqr', PARAM_INT);
        $mform->setDefault('dqr', 6);
        $mform->addHelpButton('dqr', 'dqr', 'mod_tarsuszero');

        $mform->addElement('text', 'industry', get_string('industry', 'mod_tarsuszero'), ['size' => 48]);
        $mform->setType('industry', PARAM_TEXT);
        $mform->addRule('industry', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('industry', 'industry', 'mod_tarsuszero');

        $mform->addElement('advcheckbox', 'fulltextsearch', get_string('fulltextsearch', 'mod_tarsuszero'));
        $mform->setDefault('fulltextsearch', 0);
        $mform->addHelpButton('fulltextsearch', 'fulltextsearch', 'mod_tarsuszero');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
