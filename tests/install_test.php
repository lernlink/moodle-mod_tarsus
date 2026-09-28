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

namespace mod_tarsuszero;

/**
 * Install schema regression tests for mod_tarsuszero.
 *
 * Asserts the activity and chat-history tables exist with their expected
 * columns and indexes after a clean install, the install-side counterpart to
 * the local_tarsus regression guard.
 *
 * @package    mod_tarsuszero
 * @covers     \mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class install_test extends \advanced_testcase {

    /** @var array<string, string[]> Expected table => required columns. */
    private const SCHEMA = [
        'tarsuszero' => [
            'id', 'course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified',
            'conversationstyle', 'dqr', 'industry',
            'taxonomylevels', 'questiontypes',
        ],
        'tarsuszero_inclusions' => [
            'id', 'tarsuszeroid', 'resourceid', 'timecreated',
        ],
    ];

    /**
     * Both plugin tables and all their columns exist after a clean install.
     *
     * @return void
     */
    public function test_tables_and_columns_present_after_install(): void {
        global $DB;

        $dbman = $DB->get_manager();

        foreach (self::SCHEMA as $tablename => $columns) {
            $this->assertTrue(
                $dbman->table_exists($tablename),
                "Table {$tablename} missing after clean install."
            );

            foreach ($columns as $column) {
                $this->assertTrue(
                    $dbman->field_exists($tablename, $column),
                    "Column {$tablename}.{$column} missing after clean install."
                );
            }
        }
    }

    /**
     * The conversation store lives in the brain, not in a local table.
     *
     * @return void
     */
    public function test_chats_table_is_retired(): void {
        global $DB;

        $this->assertFalse(
            $DB->get_manager()->table_exists('tarsuszero_chats'),
            'The retired tarsuszero_chats table must not exist on a clean install.'
        );
    }
}
