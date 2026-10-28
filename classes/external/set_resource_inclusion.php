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

namespace mod_tarsus\external;

defined('MOODLE_INTERNAL') || die();

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_tarsus\index\repository;
use local_tarsus\index\status;
use local_tarsus\material\resource_id;
use moodle_exception;

/**
 * External function to include or exclude a resource from a tarsus activity's scope.
 *
 * Only indexed resources may be included. The inclusion set is scoped per
 * activity instance so different tarsus activities can cover different
 * subsets of the course's indexed content.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_resource_inclusion extends external_api {

    /**
     * Declares parameters for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'The course module id of the tarsus activity.'),
            'resourceid' => new external_value(PARAM_RAW, 'The namespaced resource id.'),
            'included' => new external_value(PARAM_BOOL, 'True to include, false to exclude.'),
        ]);
    }

    /**
     * Add or remove a resource from this activity's inclusion set.
     *
     * @param int    $cmid       The course module id.
     * @param string $resourceid The namespaced resource id.
     * @param bool   $included   Whether the resource should be included.
     * @return array{resourceid: string, included: bool}
     * @throws moodle_exception
     */
    public static function execute(int $cmid, string $resourceid, bool $included): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'resourceid' => $resourceid,
            'included' => $included,
        ]);

        $cm = get_coursemodule_from_id('tarsus', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/tarsus:manage', $context);

        // Isolation guard: the resource must belong to this activity's course.
        $parts = resource_id::parse($params['resourceid']);
        if ($parts === null || (int) $parts['courseid'] !== (int) $cm->course) {
            throw new moodle_exception('invalidresource', 'mod_tarsus');
        }

        $tarsusid = (int) $cm->instance;

        if ($params['included']) {
            // Only content the brain actually holds may be put in scope:
            // including anything else would promise material the chat can
            // never ground an answer in.
            $row = (new repository())->get($params['resourceid']);
            if ($row === null || $row->status !== status::INDEXED) {
                throw new moodle_exception('includenotindexed', 'mod_tarsus');
            }

            // Insert only if not already present (idempotent).
            $exists = $DB->record_exists('tarsus_inclusions', [
                'tarsusid' => $tarsusid,
                'resourceid' => $params['resourceid'],
            ]);
            if (!$exists) {
                $DB->insert_record('tarsus_inclusions', (object) [
                    'tarsusid' => $tarsusid,
                    'resourceid' => $params['resourceid'],
                    'timecreated' => time(),
                ]);
            }
        } else {
            $DB->delete_records('tarsus_inclusions', [
                'tarsusid' => $tarsusid,
                'resourceid' => $params['resourceid'],
            ]);
        }

        return [
            'resourceid' => $params['resourceid'],
            'included' => $params['included'],
        ];
    }

    /**
     * Describes the return value of execute().
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'resourceid' => new external_value(PARAM_RAW, 'The namespaced resource id.'),
            'included' => new external_value(PARAM_BOOL, 'The new inclusion state.'),
        ]);
    }
}
