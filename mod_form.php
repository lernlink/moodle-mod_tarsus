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

        $mform->addElement('advcheckbox', 'fulltextsearch', get_string('fulltextsearch', 'mod_tarsuszero'));
        $mform->setDefault('fulltextsearch', 0);
        $mform->addHelpButton('fulltextsearch', 'fulltextsearch', 'mod_tarsuszero');

        // Practice (Test knowledge) settings: which Bloom taxonomy levels
        // the questions may target, and which question types the brain may
        // generate per difficulty grade. The difficulty grades have no API
        // parameter of their own — a grade takes part exactly when it has
        // question types enabled.
        $taxonomies = [
            1 => get_string('taxonomy_knowledge', 'mod_tarsuszero'),
            2 => get_string('taxonomy_understanding', 'mod_tarsuszero'),
            3 => get_string('taxonomy_application', 'mod_tarsuszero'),
        ];
        $group = [];
        foreach ($taxonomies as $level => $label) {
            $group[] = $mform->createElement('advcheckbox', 'taxonomylevel' . $level, '', $label);
        }
        $mform->addGroup($group, 'taxonomygroup', get_string('taxonomylevels', 'mod_tarsuszero'), ' ', false);
        $mform->addHelpButton('taxonomygroup', 'taxonomylevels', 'mod_tarsuszero');
        foreach (array_keys($taxonomies) as $level) {
            $mform->setDefault('taxonomylevel' . $level,
                (int) in_array($level, didactics::default_taxonomy_levels(), true));
        }

        $qtypes = [
            didactics::TYPE_SINGLECHOICE => get_string('qtype_singlechoice', 'mod_tarsuszero'),
            didactics::TYPE_TRUEFALSE => get_string('qtype_truefalse', 'mod_tarsuszero'),
            didactics::TYPE_OPEN => get_string('qtype_open', 'mod_tarsuszero'),
        ];
        $grades = [
            1 => get_string('difficulty_easy', 'mod_tarsuszero'),
            2 => get_string('difficulty_medium', 'mod_tarsuszero'),
            3 => get_string('difficulty_hard', 'mod_tarsuszero'),
        ];
        $defaultmap = didactics::default_question_types();
        foreach ($grades as $grade => $gradelabel) {
            $group = [];
            foreach ($qtypes as $type => $typelabel) {
                $group[] = $mform->createElement('advcheckbox', self::qtype_field($grade, $type), '', $typelabel);
            }
            $mform->addGroup($group, 'qtypesgroup' . $grade,
                get_string('questiontypes_grade', 'mod_tarsuszero', $gradelabel), ' ', false);
            $mform->addHelpButton('qtypesgroup' . $grade, 'questiontypes', 'mod_tarsuszero');
            foreach (array_keys($qtypes) as $type) {
                $mform->setDefault(self::qtype_field($grade, $type),
                    (int) in_array($type, $defaultmap[$grade] ?? [], true));
            }
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * The form field name of one question-type checkbox.
     *
     * @param int    $grade The difficulty grade (1..3).
     * @param string $type  The question widget type.
     * @return string The field name.
     */
    private static function qtype_field(int $grade, string $type): string {
        return 'qtype' . $grade . '_' . strtolower($type);
    }

    /**
     * Unpack the stored practice settings into the checkbox fields.
     *
     * @param array $defaultvalues The default values (passed by reference).
     * @return void
     */
    public function data_preprocessing(&$defaultvalues): void {
        parent::data_preprocessing($defaultvalues);

        if (!empty($defaultvalues['id'])) {
            $levels = didactics::decode_taxonomy_levels($defaultvalues['taxonomylevels'] ?? '');
            foreach (didactics::TAXONOMY_LEVELS as $level) {
                $defaultvalues['taxonomylevel' . $level] = (int) in_array($level, $levels, true);
            }

            $map = didactics::decode_question_types($defaultvalues['questiontypes'] ?? '');
            foreach (didactics::GRADES as $grade) {
                foreach (didactics::TYPES as $type) {
                    $defaultvalues[self::qtype_field($grade, $type)] =
                        (int) in_array($type, $map[$grade] ?? [], true);
                }
            }
        }
    }

    /**
     * Pack the checkbox fields back into the stored practice settings.
     *
     * @param \stdClass $data The submitted form data (modified in place).
     * @return void
     */
    public function data_postprocessing($data): void {
        parent::data_postprocessing($data);

        $levels = [];
        foreach (didactics::TAXONOMY_LEVELS as $level) {
            if (!empty($data->{'taxonomylevel' . $level})) {
                $levels[] = $level;
            }
        }
        $data->taxonomylevels = didactics::encode_taxonomy_levels($levels);

        $map = [];
        foreach (didactics::GRADES as $grade) {
            foreach (didactics::TYPES as $type) {
                if (!empty($data->{self::qtype_field($grade, $type)})) {
                    $map[$grade][] = $type;
                }
            }
        }
        $data->questiontypes = didactics::encode_question_types($map);
    }

    /**
     * Require at least one taxonomy level and one question type.
     *
     * @param array $data  The submitted data.
     * @param array $files The submitted files.
     * @return array Field name => error message.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $haslevel = false;
        foreach (didactics::TAXONOMY_LEVELS as $level) {
            $haslevel = $haslevel || !empty($data['taxonomylevel' . $level]);
        }
        if (!$haslevel) {
            $errors['taxonomygroup'] = get_string('error_taxonomyrequired', 'mod_tarsuszero');
        }

        $hastype = false;
        foreach (didactics::GRADES as $grade) {
            foreach (didactics::TYPES as $type) {
                $hastype = $hastype || !empty($data[self::qtype_field($grade, $type)]);
            }
        }
        if (!$hastype) {
            $errors['qtypesgroup1'] = get_string('error_questiontypesrequired', 'mod_tarsuszero');
        }

        return $errors;
    }
}
