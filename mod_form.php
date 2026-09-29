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

        // AI settings, grouped as in the Tarsus interface: general, then one
        // block per interaction mode with its own switch. Deliberately
        // separate from the site-wide local_tarsus settings (connection,
        // assistant identity, guardrails) — nothing overlaps. Array defaults
        // belong to a new activity only: QuickForm merges defaults with the
        // stored value index by index, so a stored selection would otherwise
        // reappear padded with default entries.
        $isnew = empty($this->_instance);

        // --- General -------------------------------------------------------
        $mform->addElement('header', 'settings_general', get_string('settings_general', 'mod_tarsuszero'));
        $mform->setExpanded('settings_general');

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
        $mform->addHelpButton('taxonomylevels', 'taxonomylevels', 'mod_tarsuszero');
        if ($isnew) {
            $mform->setDefault('taxonomylevels', didactics::default_taxonomy_levels());
        }

        $mform->addElement('text', 'industry', get_string('industry', 'mod_tarsuszero'), ['size' => 48]);
        $mform->setType('industry', PARAM_TEXT);
        $mform->addRule('industry', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('industry', 'industry', 'mod_tarsuszero');

        $mform->addElement('advcheckbox', 'anticheat', get_string('anticheat', 'mod_tarsuszero'));
        $mform->setDefault('anticheat', 0);
        $mform->addHelpButton('anticheat', 'anticheat', 'mod_tarsuszero');

        // --- Ask a question ------------------------------------------------
        $mform->addElement('header', 'settings_ask', get_string('settings_ask', 'mod_tarsuszero'));
        $mform->setExpanded('settings_ask');

        $mform->addElement('advcheckbox', 'askenabled', get_string('askenabled', 'mod_tarsuszero'));
        $mform->setDefault('askenabled', 1);
        $mform->addHelpButton('askenabled', 'askenabled', 'mod_tarsuszero');

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

        // --- Test knowledge ------------------------------------------------
        $mform->addElement('header', 'settings_test', get_string('settings_test', 'mod_tarsuszero'));
        $mform->setExpanded('settings_test');

        $mform->addElement('advcheckbox', 'testenabled', get_string('testenabled', 'mod_tarsuszero'));
        $mform->setDefault('testenabled', 1);
        $mform->addHelpButton('testenabled', 'testenabled', 'mod_tarsuszero');

        $mform->addElement('select', 'operators', get_string('operators', 'mod_tarsuszero'), [
            didactics::OPERATORS_STANDARD => get_string('operators_standard', 'mod_tarsuszero'),
            didactics::OPERATORS_IHK => get_string('operators_ihk', 'mod_tarsuszero'),
        ]);
        $mform->setType('operators', PARAM_ALPHA);
        $mform->setDefault('operators', didactics::OPERATORS_STANDARD);
        $mform->addHelpButton('operators', 'operators', 'mod_tarsuszero');

        // Question types per difficulty grade. The brain requires every grade
        // to carry at least one type; difficulty has no parameter of its own.
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
            if ($isnew) {
                $mform->setDefault($name, $defaultmap[$grade] ?? []);
            }
            $mform->addHelpButton($name, 'questiontypes', 'mod_tarsuszero');
        }

        $tempos = [];
        foreach (didactics::LEARN_TEMPOS as $tempo) {
            $label = 'x' . rtrim(rtrim(number_format($tempo, 2, '.', ''), '0'), '.');
            $tempos[(string) $tempo] = $tempo === didactics::LEARN_TEMPO_DEFAULT
                ? get_string('learntempo_recommended', 'mod_tarsuszero', $label) : $label;
        }
        $mform->addElement('select', 'learntempo', get_string('learntempo', 'mod_tarsuszero'), $tempos);
        $mform->setType('learntempo', PARAM_FLOAT);
        $mform->setDefault('learntempo', (string) didactics::LEARN_TEMPO_DEFAULT);
        $mform->addHelpButton('learntempo', 'learntempo', 'mod_tarsuszero');

        // --- Build knowledge (not available yet) ---------------------------
        $mform->addElement('header', 'settings_build', get_string('settings_build', 'mod_tarsuszero'));

        $mform->addElement('advcheckbox', 'buildenabled', get_string('buildenabled', 'mod_tarsuszero'));
        $mform->setDefault('buildenabled', 0);
        $mform->addHelpButton('buildenabled', 'buildenabled', 'mod_tarsuszero');

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

            // The pace is stored as a decimal (1.00); the select keys are the
            // plain floats (1).
            $defaultvalues['learntempo'] = (string) didactics::learn_tempo($defaultvalues['learntempo'] ?? null);
            $defaultvalues['operators'] = didactics::operators($defaultvalues['operators'] ?? null);
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

        // A learner needs at least one working mode.
        if (empty($data['askenabled']) && empty($data['testenabled'])) {
            $errors['askenabled'] = get_string('error_modesrequired', 'mod_tarsuszero');
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
