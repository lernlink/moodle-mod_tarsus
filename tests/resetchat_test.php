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

/**
 * Tests for the chat reset access rules.
 *
 * The conversation is the assistant's memory of a learner's progress, so
 * erasing it is a teacher-only action: students must not hold the
 * mod/tarsus:resetchat capability by default.
 *
 * @package    mod_tarsus
 * @coversNothing
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class resetchat_test extends \advanced_testcase {
    /**
     * Set up tests.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Create a course with one tarsus activity and return its module context.
     *
     * @return array{0: \stdClass, 1: \context_module} Course and module context.
     */
    private function make_activity(): array {
        $course = $this->getDataGenerator()->create_course();
        $tz = $this->getDataGenerator()->create_module('tarsus', ['course' => $course->id]);
        return [$course, \context_module::instance($tz->cmid)];
    }

    /**
     * Students must not be able to erase their conversation.
     */
    public function test_student_cannot_reset_chat(): void {
        [$course, $context] = $this->make_activity();

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        // The student can use the chat...
        $this->assertTrue(has_capability('mod/tarsus:view', $context));

        // ...but not erase it: resetchat.php gates on this capability.
        $this->assertFalse(has_capability('mod/tarsus:resetchat', $context));
        $this->expectException(\required_capability_exception::class);
        require_capability('mod/tarsus:resetchat', $context);
    }

    /**
     * Teachers keep the reset entry for their own (preview) conversations.
     */
    public function test_teachers_can_reset_chat(): void {
        [$course, $context] = $this->make_activity();

        foreach (['teacher', 'editingteacher'] as $role) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $course->id, $role);
            $this->setUser($user);
            $this->assertTrue(has_capability('mod/tarsus:resetchat', $context), $role);
        }
    }
}
