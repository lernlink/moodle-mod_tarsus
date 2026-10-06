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

namespace mod_tarsuszero\local;

use local_tarsus\index\status;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps an index status to its presentational shape (badge + available actions).
 *
 * Single source of truth shared by the teacher dashboard renderable and the
 * AJAX external functions so the template and the live updates never drift.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class status_presenter {

    /**
     * @var int Seconds after which a resource still awaiting the brain's
     *          confirmation counts as stuck and may be removed by the teacher.
     *          Normal ingestion confirms within a minute or two; a very large
     *          document can sit in the brain's queue for good.
     */
    public const STUCK_AFTER = 600;

    /** @var array<string, string> Status value => Bootstrap badge modifier. */
    private const BADGES = [
        status::NOT_INDEXED => 'badge-light',
        status::PENDING => 'badge-warning',
        status::PENDING_CONFIRMATION => 'badge-warning',
        status::PENDING_REINDEX => 'badge-warning',
        status::PENDING_DELETION => 'badge-secondary',
        status::INDEXED => 'badge-success',
        status::ERROR => 'badge-danger',
    ];

    /**
     * The Bootstrap badge modifier class for a status.
     *
     * @param string $status One of the {@see status} constants.
     * @return string A badge-* class.
     */
    public static function badge_class(string $status): string {
        return self::BADGES[$status] ?? 'badge-light';
    }

    /**
     * Whether the resource is mid-flight (a worker run is pending).
     *
     * @param string $status One of the {@see status} constants.
     * @return bool
     */
    public static function is_inflight(string $status): bool {
        // pendingreindex is not in flight: with no background queue it is a
        // stable "content changed" state that waits for the teacher to act.
        return in_array($status, [
            status::PENDING,
            status::PENDING_CONFIRMATION,
            status::PENDING_DELETION,
        ], true);
    }

    /**
     * The action keys a manager may trigger from a given status.
     *
     * @param string $status One of the {@see status} constants.
     * @param int    $age    Seconds since the row last changed state (0 when unknown).
     * @return string[] Subset of [index, reindex, remove, retry].
     */
    public static function actions_for(string $status, int $age = 0): array {
        return match ($status) {
            status::NOT_INDEXED => ['index'],
            status::INDEXED => ['reindex', 'remove'],
            status::PENDING_REINDEX => ['reindex', 'remove'],
            status::ERROR => ['retry', 'remove'],
            // The brain never answered: after a while the teacher may give
            // up on the upload and untrack it instead of waiting for ever.
            status::PENDING_CONFIRMATION => $age >= self::STUCK_AFTER ? ['remove'] : [],
            default => [],
        };
    }

    /**
     * The full presentational payload for a single bit.
     *
     * Used both to seed the mustache template and as the AJAX response shape,
     * so the front end can re-render a row from either source identically.
     *
     * @param string $resourceid   The namespaced resource id.
     * @param string $status        One of the {@see status} constants.
     * @param string $statuslabel   The human-readable status label.
     * @param int    $timemodified  When the row last changed state (0 when unknown).
     * @return array<string, mixed>
     */
    public static function present(string $resourceid, string $status, string $statuslabel, int $timemodified = 0): array {
        $age = $timemodified > 0 ? max(0, time() - $timemodified) : 0;
        $actions = self::actions_for($status, $age);
        return [
            'resourceid' => $resourceid,
            'status' => $status,
            'statuslabel' => $statuslabel,
            'badgeclass' => self::badge_class($status),
            'inflight' => self::is_inflight($status),
            'canindex' => in_array('index', $actions, true),
            'canreindex' => in_array('reindex', $actions, true),
            'canremove' => in_array('remove', $actions, true),
            'canretry' => in_array('retry', $actions, true),
            // Only content the brain holds may be put in an activity's scope.
            'caninclude' => $status === status::INDEXED,
        ];
    }
}
