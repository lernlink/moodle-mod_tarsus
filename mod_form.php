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

use mod_tarsuszero\local\didactics;

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

        // DQR levels as named on the Tarsus site; the wire value stays 3..7.
        $dqrlevels = [
            3 => get_string('dqr_level3', 'mod_tarsuszero'),
            4 => get_string('dqr_level4', 'mod_tarsuszero'),
            5 => get_string('dqr_level5', 'mod_tarsuszero'),
            6 => get_string('dqr_level6', 'mod_tarsuszero'),
            7 => get_string('dqr_level7', 'mod_tarsuszero'),
        ];
        $mform->addElement('select', 'dqr', get_string('dqr', 'mod_tarsuszero'), $dqrlevels);
        $mform->setType('dqr', PARAM_INT);
        $mform->setDefault('dqr', 6);
        $mform->addHelpButton('dqr', 'dqr', 'mod_tarsuszero');

        $mform->addElement('text', 'industry', get_string('industry', 'mod_tarsuszero'), ['size' => 48]);
        $mform->setType('industry', PARAM_TEXT);
        $mform->addRule('industry', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('industry', 'industry', 'mod_tarsuszero');

        // Practice (Test knowledge) settings: which Bloom taxonomy levels
        // the questions may target, and which question types the brain may
        // generate per difficulty grade. The difficulty grades have no API
        // parameter of their own — a grade takes part exactly when it has
        // question types enabled. Both are chip selectors, as in the Tarsus
        // interface; the stored values are packed in data_postprocessing().
        $taxonomies = [
            1 => get_string('taxonomy_knowledge', 'mod_tarsuszero'),
            2 => get_string('taxonomy_understanding', 'mod_tarsuszero'),
            3 => get_string('taxonomy_application', 'mod_tarsuszero'),
        ];
        $mform->addElement('autocomplete', 'taxonomylevels', get_string('taxonomylevels', 'mod_tarsuszero'),
            $taxonomies, [
                'multiple' => true,
                'placeholder' => get_string('taxonomylevels_placeholder', 'mod_tarsuszero'),
                'noselectionstring' => get_string('taxonomylevels_none', 'mod_tarsuszero'),
            ]);
        $mform->setType('taxonomylevels', PARAM_INT);
        $mform->setDefault('taxonomylevels', didactics::default_taxonomy_levels());
        $mform->addHelpButton('taxonomylevels', 'taxonomylevels', 'mod_tarsuszero');

        $qtypes = [];
        foreach (didactics::TYPES as $type) {
            $qtypes[$type] = get_string(didactics::type_label_key($type), 'mod_tarsuszero');
        }
        $grades = [
            1 => get_string('difficulty_easy', 'mod_tarsuszero'),
            2 => get_string('difficulty_medium', 'mod_tarsuszero'),
            3 => get_string('difficulty_hard', 'mod_tarsuszero'),
        ];
        $defaultmap = didactics::default_question_types();
        foreach ($grades as $grade => $gradelabel) {
            $name = self::qtypes_field($grade);
            $mform->addElement('autocomplete', $name,
                get_string('questiontypes_grade', 'mod_tarsuszero', $gradelabel), $qtypes, [
                    'multiple' => true,
                    'placeholder' => get_string('questiontypes_placeholder', 'mod_tarsuszero'),
                    'noselectionstring' => get_string('questiontypes_none', 'mod_tarsuszero'),
                ]);
            $mform->setType($name, PARAM_ALPHANUMEXT);
            $mform->setDefault($name, $defaultmap[$grade] ?? []);
            $mform->addHelpButton($name, 'questiontypes', 'mod_tarsuszero');
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * The form field name of one difficulty grade's question-type selector.
     *
     * @param int $grade The difficulty grade (1..3).
     * @return string The field name.
     */
    private static function qtypes_field(int $grade): string {
        return 'qtypes' . $grade;
    }

    /**
     * Unpack the stored practice settings into the chip selectors.
     *
     * @param array $defaultvalues The default values (passed by reference).
     * @return void
     */
    public function data_preprocessing(&$defaultvalues): void {
        parent::data_preprocessing($defaultvalues);

        if (!empty($defaultvalues['id'])) {
            $defaultvalues['taxonomylevels'] =
                didactics::decode_taxonomy_levels($defaultvalues['taxonomylevels'] ?? '');

            $map = didactics::decode_question_types($defaultvalues['questiontypes'] ?? '');
            foreach (didactics::GRADES as $grade) {
                $defaultvalues[self::qtypes_field($grade)] = $map[$grade] ?? [];
            }
        }
    }

    /**
     * Pack the chip selectors back into the stored practice settings.
     *
     * @param \stdClass $data The submitted form data (modified in place).
     * @return void
     */
    public function data_postprocessing($data): void {
        parent::data_postprocessing($data);

        $data->taxonomylevels = didactics::encode_taxonomy_levels((array) ($data->taxonomylevels ?? []));

        $map = [];
        foreach (didactics::GRADES as $grade) {
            $map[$grade] = (array) ($data->{self::qtypes_field($grade)} ?? []);
        }
        $data->questiontypes = didactics::encode_question_types($map);
    }

    /**
     * Require at least one taxonomy level and one question type per grade.
     *
     * @param array $data  The submitted data.
     * @param array $files The submitted files.
     * @return array Field name => error message.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (didactics::encode_taxonomy_levels((array) ($data['taxonomylevels'] ?? [])) === '') {
            $errors['taxonomylevels'] = get_string('error_taxonomyrequired', 'mod_tarsuszero');
        }

        // The brain rejects a grade without question types (verified live),
        // so every grade needs at least one.
        foreach (didactics::GRADES as $grade) {
            $field = self::qtypes_field($grade);
            if (array_intersect((array) ($data[$field] ?? []), didactics::TYPES) === []) {
                $errors[$field] = get_string('error_questiontypesrequired', 'mod_tarsuszero');
            }
        }

        return $errors;
    }
}
