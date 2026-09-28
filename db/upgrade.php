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
 * Upgrade steps for mod_tarsuszero.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade mod_tarsuszero.
 *
 * @param int $oldversion The previously installed version.
 * @return bool
 */
function xmldb_tarsuszero_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026062605) {
        // Add tarsuszero_inclusions table that tracks which indexed resources a
        // teacher has explicitly included in a specific activity instance.
        $table = new xmldb_table('tarsuszero_inclusions');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('tarsuszeroid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('resourceid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('tarsuszeroid', XMLDB_KEY_FOREIGN, ['tarsuszeroid'], 'tarsuszero', ['id']);
        $table->add_index('tarsuszeroid_resourceid', XMLDB_INDEX_UNIQUE, ['tarsuszeroid', 'resourceid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026062605, 'tarsuszero');
    }

    if ($oldversion < 2026073105) {
        // The conversation now lives only in the brain, which keeps every
        // turn anyway as its dialogue memory; the local copy could drift
        // from it and is retired. The stored exchanges are display history,
        // reproducible from the brain — nothing is lost that the plugin
        // could still show.
        $table = new xmldb_table('tarsuszero_chats');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_mod_savepoint(true, 2026073105, 'tarsuszero');
    }

    if ($oldversion < 2026073115) {
        // Per-activity didactic settings: how the assistant converses in this
        // activity, as opposed to the site-wide connection and identity
        // settings in local_tarsus.
        $table = new xmldb_table('tarsuszero');
        $fields = [
            new xmldb_field('conversationstyle', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '3', 'timemodified'),
            new xmldb_field('dqr', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '6', 'conversationstyle'),
            new xmldb_field('industry', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '', 'dqr'),
            new xmldb_field('fulltextsearch', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'industry'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026073115, 'tarsuszero');
    }

    if ($oldversion < 2026091800) {
        // Practice (Test knowledge) settings: the enabled Bloom taxonomy
        // levels and the question types per difficulty grade. An empty
        // questiontypes value means the plugin defaults, so existing
        // activities keep working without a data migration.
        $table = new xmldb_table('tarsuszero');
        $fields = [
            new xmldb_field('taxonomylevels', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, '1,2,3', 'fulltextsearch'),
            new xmldb_field('questiontypes', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, '', 'taxonomylevels'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026091800, 'tarsuszero');
    }

    if ($oldversion < 2026092903) {
        // The full-text search setting was withdrawn from the activity form;
        // the brain is always asked with full_text_search off, so the column
        // carries nothing worth keeping.
        $table = new xmldb_table('tarsuszero');
        $field = new xmldb_field('fulltextsearch');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026092903, 'tarsuszero');
    }

    if ($oldversion < 2026092905) {
        // Practice settings from the Tarsus developer's answers: the operator
        // taxonomy (standard / ihk), anti-cheat and the learning pace.
        $table = new xmldb_table('tarsuszero');
        $fields = [
            new xmldb_field('operators', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'standard', 'questiontypes'),
            new xmldb_field('anticheat', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'operators'),
            new xmldb_field('learntempo', XMLDB_TYPE_NUMBER, '4, 2', null, XMLDB_NOTNULL, null, '1', 'anticheat'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026092905, 'tarsuszero');
    }

    return true;
}
