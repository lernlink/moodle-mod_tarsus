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
use core_external\external_single_structure;
use core_external\external_value;
use context_module;
use local_tarsus\index\repository;
use local_tarsus\index\status;
use local_tarsus\indexer;
use local_tarsus\material\resource_id;
use mod_tarsuszero\local\status_presenter;
use moodle_exception;

/**
 * External function to trigger a per-resource index action (index, re-index, remove, retry).
 *
 * Each call is capability-checked and the target resource id is validated to
 * belong to the activity's course, preventing cross-course tampering on the
 * shared brain (§4 isolation by id).
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_resource_action extends external_api {

    /** @var string[] The accepted action verbs. */
    private const ACTIONS = ['index', 'reindex', 'remove', 'retry'];

    /**
     * Declares parameters for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'The course module id of the tarsuszero activity.'),
            'resourceid' => new external_value(PARAM_RAW, 'The namespaced resource id to act on.'),
            'action' => new external_value(PARAM_ALPHA, 'One of: index, reindex, remove, retry.'),
        ]);
    }

    /**
     * Apply the requested action to a single resource.
     *
     * @param int    $cmid       The course module id.
     * @param string $resourceid The namespaced resource id.
     * @param string $action     The action verb.
     * @return array<string, mixed> The new presentational state of the resource.
     * @throws moodle_exception
     */
    public static function execute(int $cmid, string $resourceid, string $action): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'resourceid' => $resourceid,
            'action' => $action,
        ]);

        if (!in_array($params['action'], self::ACTIONS, true)) {
            throw new moodle_exception('invalidaction', 'mod_tarsuszero');
        }

        $cm = get_coursemodule_from_id('tarsuszero', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/tarsuszero:manage', $context);

        // Isolation guard: the resource id must belong to this activity's course.
        $parts = resource_id::parse($params['resourceid']);
        if ($parts === null || (int) $parts['courseid'] !== (int) $cm->course) {
            throw new moodle_exception('invalidresource', 'mod_tarsuszero');
        }

        $indexer = new indexer();
        $queued = match ($params['action']) {
            'index' => $indexer->index_resource($params['resourceid']),
            'reindex' => $indexer->reindex_resource($params['resourceid']),
            'remove' => $indexer->remove_resource($params['resourceid']),
            'retry' => $indexer->retry_resource($params['resourceid']),
            default => false,
        };

        if (!$queued) {
            throw new moodle_exception('actionfailed', 'mod_tarsuszero');
        }

        $row = (new repository())->get($params['resourceid']);
        $newstatus = $row->status ?? status::NOT_INDEXED;

        return status_presenter::present(
            $params['resourceid'],
            $newstatus,
            status::label($newstatus)
        );
    }

    /**
     * Describes the return value of execute().
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'resourceid' => new external_value(PARAM_RAW, 'The namespaced resource id.'),
            'status' => new external_value(PARAM_ALPHA, 'The new internal status value.'),
            'statuslabel' => new external_value(PARAM_TEXT, 'The human-readable status label.'),
            'badgeclass' => new external_value(PARAM_ALPHANUMEXT, 'The Bootstrap badge modifier class.'),
            'inflight' => new external_value(PARAM_BOOL, 'Whether a worker run is pending.'),
            'canindex' => new external_value(PARAM_BOOL, 'Whether the index action is available.'),
            'canreindex' => new external_value(PARAM_BOOL, 'Whether the re-index action is available.'),
            'canremove' => new external_value(PARAM_BOOL, 'Whether the remove action is available.'),
            'canretry' => new external_value(PARAM_BOOL, 'Whether the retry action is available.'),
        ]);
    }
}
