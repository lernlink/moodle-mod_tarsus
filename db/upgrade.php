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
 * Upgrade steps for mod_tarsus.
 *
 * The module was developed as mod_tarsuszero and renamed before its first
 * release. Every install of mod_tarsus starts from db/install.xml, so the
 * schema history of the old name is not replayed here; it stays in the git
 * log. Steps for later schema changes go below, each closed with
 * upgrade_mod_savepoint().
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade mod_tarsus.
 *
 * @param int $oldversion The previously installed version.
 * @return bool
 */
function xmldb_tarsus_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026110600) {
        // The interaction type: trainer-driven keeps the text cards,
        // ai-driven lets the brain send interactive web cards.
        $table = new xmldb_table('tarsus');
        $field = new xmldb_field(
            'interactiontype',
            XMLDB_TYPE_CHAR,
            '20',
            null,
            XMLDB_NOTNULL,
            null,
            'trainer-driven',
            'learningmethod'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026110600, 'tarsus');
    }

    return true;
}
