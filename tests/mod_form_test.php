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

namespace mod_tarsus;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/tarsus/mod_form.php');

/**
 * Tests for the activity settings form.
 *
 * @package    mod_tarsus
 * @covers     \mod_tarsus_mod_form
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mod_form_test extends \advanced_testcase {
    /**
     * Set up tests.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Build the edit form for an existing activity, as modedit.php does.
     *
     * @param \stdClass $course The course.
     * @param int       $cmid   The course module id.
     * @return \mod_tarsus_mod_form The form, loaded with the stored data.
     */
    private function edit_form(\stdClass $course, int $cmid): \mod_tarsus_mod_form {
        global $PAGE;

        // The generator's course object predates the module: reload it so
        // modinfo is rebuilt, and let the page publish it as $COURSE — the
        // core form reads the global.
        $course = get_course($course->id);
        $PAGE->set_url('/course/modedit.php', ['update' => $cmid]);
        $PAGE->set_course($course);
        [$cm, $context, $module, $data, $cw] = get_moduleinfo_data(get_coursemodule_from_id('tarsus', $cmid), $course);

        $form = new \mod_tarsus_mod_form($data, $cw->section, $cm, $course);
        $form->set_data($data);

        return $form;
    }

    /**
     * Read one element's current value out of a form.
     *
     * @param \moodleform $form    The form.
     * @param string      $element The element name.
     * @return mixed The element value.
     */
    private function element_value(\moodleform $form, string $element) {
        $property = new \ReflectionProperty(\moodleform::class, '_form');
        $property->setAccessible(true);
        return $property->getValue($form)->getElementValue($element);
    }

    /**
     * The edit form shows exactly the stored chip selections — not padded
     * with the defaults a new activity would get (QuickForm merges array
     * defaults with stored values index by index).
     */
    public function test_edit_form_shows_stored_selections_unpadded(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsus', ['course' => $course->id]);
        $DB->set_field(
            'tarsus',
            'questiontypes',
            '{"1":["tradeCostingQuestionWidget"],"2":["tradeCostingQuestionWidget"],"3":["tradeCostingQuestionWidget"]}',
            ['id' => $tz->id]
        );
        $DB->set_field('tarsus', 'taxonomylevels', '2', ['id' => $tz->id]);

        $form = $this->edit_form($course, (int) $tz->cmid);

        foreach ([1, 2, 3] as $grade) {
            $this->assertSame(
                ['tradeCostingQuestionWidget'],
                array_values((array) $this->element_value($form, 'qtypes' . $grade)),
                "Grade $grade shows only the stored type"
            );
        }
        $this->assertSame(['2'], array_map('strval', array_values((array) $this->element_value($form, 'taxonomylevels'))));
    }

    /**
     * A new activity still starts from the Tarsus reference defaults.
     */
    public function test_new_form_starts_from_defaults(): void {
        global $PAGE;

        $course = get_course($this->getDataGenerator()->create_course()->id);
        $PAGE->set_url('/course/modedit.php', ['add' => 'tarsus']);
        $PAGE->set_course($course);
        [$module, $context, $cw, $cm, $data] = prepare_new_moduleinfo_data($course, 'tarsus', 0);

        $form = new \mod_tarsus_mod_form($data, 0, null, $course);
        $form->set_data($data);

        $defaults = \mod_tarsus\local\didactics::default_question_types();
        foreach ([1, 2, 3] as $grade) {
            $this->assertSame($defaults[$grade], array_values((array) $this->element_value($form, 'qtypes' . $grade)));
        }
        $this->assertSame([1, 2, 3], array_map('intval', array_values((array) $this->element_value($form, 'taxonomylevels'))));
    }
}
