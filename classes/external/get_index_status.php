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
use local_tarsus\index\course_report;
use mod_tarsuszero\local\status_presenter;
use moodle_exception;

/**
 * External function returning the live index status of every bit in the course.
 *
 * Polled by the dashboard while any resource is mid-flight so the badges and
 * action buttons update once the worker has run, without a page reload.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_index_status extends external_api {

    /**
     * Declares parameters for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'The course module id of the tarsuszero activity.'),
        ]);
    }

    /**
     * Return the presentational state of every indexable bit in the course.
     *
     * @param int $cmid The course module id.
     * @return array<string, mixed> A list of per-resource presentational rows.
     * @throws moodle_exception
     */
    public static function execute(int $cmid): array {
        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

        $cm = get_coursemodule_from_id('tarsuszero', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/tarsuszero:manage', $context);

        $resources = [];
        foreach ((new course_report())->for_course($cm->course) as $activity) {
            foreach ($activity['bits'] as $bit) {
                $resources[] = status_presenter::present(
                    $bit['resourceid'],
                    $bit['status'],
                    $bit['statuslabel']
                );
            }
        }

        return ['resources' => $resources];
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
