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

namespace mod_tarsuszero\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use context_module;
use local_tarsus\client\client_interface;
use local_tarsus\guardrail\blocked_exception;
use local_tarsus\index\repository;
use local_tarsus\index\status;
use local_tarsus\index\status_sync;
use local_tarsus\material\resource_id;
use mod_tarsuszero\local\status_presenter;
use moodle_exception;

/**
 * External function reconciling one page of the material list against the brain.
 *
 * The brain reports up to {@see client_interface::BATCH_LIMIT} resources per
 * call, which is exactly one page of the teacher dashboard — so a page refresh
 * costs a single API call regardless of how large the course is.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_page_status extends external_api {

    /**
     * Declares parameters for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'The course module id of the tarsuszero activity.'),
            'resourceids' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'A namespaced resource id shown on the current page.')
            ),
        ]);
    }

    /**
     * Ask the brain about the given resources and return their refreshed state.
     *
     * @param int      $cmid        The course module id.
     * @param string[] $resourceids The resource ids shown on the current page.
     * @return array<string, mixed> The refreshed presentational rows.
     * @throws moodle_exception
     */
    public static function execute(int $cmid, array $resourceids): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'resourceids' => $resourceids,
        ]);

        $cm = get_coursemodule_from_id('tarsuszero', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/tarsuszero:manage', $context);

        if (count($params['resourceids']) > client_interface::BATCH_LIMIT) {
            throw new moodle_exception('toomanyresources', 'mod_tarsuszero', '', client_interface::BATCH_LIMIT);
        }

        // Isolation guard: every id must belong to this activity's course (§4).
        foreach ($params['resourceids'] as $resourceid) {
            $parts = resource_id::parse($resourceid);
            if ($parts === null || (int) $parts['courseid'] !== (int) $cm->course) {
                throw new moodle_exception('invalidresource', 'mod_tarsuszero');
            }
        }

        try {
            (new status_sync())->refresh($params['resourceids']);
        } catch (blocked_exception $e) {
            throw new moodle_exception('blocked_' . $e->reason, 'local_tarsus');
        }

        return ['resources' => self::present($params['resourceids'])];
    }

    /**
     * Build the presentational rows for the given resources from the local map.
     *
     * @param string[] $resourceids The resource ids to present.
     * @return array<int, array<string, mixed>>
     */
    private static function present(array $resourceids): array {
        $repo = new repository();

        $rows = [];
        foreach ($resourceids as $resourceid) {
            $row = $repo->get($resourceid);
            $current = $row->status ?? status::NOT_INDEXED;
            $rows[] = status_presenter::present($resourceid, $current, status::label($current));
        }

        return $rows;
    }

    /**
     * Describes the return value of execute().
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'resources' => new external_multiple_structure(
                new external_single_structure([
                    'resourceid' => new external_value(PARAM_RAW, 'The namespaced resource id.'),
                    'status' => new external_value(PARAM_ALPHA, 'The internal status value.'),
                    'statuslabel' => new external_value(PARAM_TEXT, 'The human-readable status label.'),
                    'badgeclass' => new external_value(PARAM_ALPHANUMEXT, 'The Bootstrap badge modifier class.'),
                    'inflight' => new external_value(PARAM_BOOL, 'Whether a worker run is pending.'),
                    'canindex' => new external_value(PARAM_BOOL, 'Whether the index action is available.'),
                    'canreindex' => new external_value(PARAM_BOOL, 'Whether the re-index action is available.'),
                    'canremove' => new external_value(PARAM_BOOL, 'Whether the remove action is available.'),
                    'canretry' => new external_value(PARAM_BOOL, 'Whether the retry action is available.'),
                ])
            ),
        ]);
    }
}
