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

use local_tarsus\index\repository;
use local_tarsus\index\status;

/**
 * The set of resources allowed to ground an activity's conversation.
 *
 * The teacher's inclusion set, filtered down to rows the brain actually holds
 * right now. Filtering at read time means a resource that was removed or
 * replaced after being included silently drops out of scope — no cleanup
 * ordering to get wrong. Both asking and reading the conversation need this:
 * the t0 surface requires the scope on every call.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scope {

    /**
     * The resource ids allowed to ground this activity's answers.
     *
     * @param int $tarsuszeroid The activity instance id.
     * @return string[] Namespaced resource ids, possibly empty.
     */
    public static function allowed_resource_ids(int $tarsuszeroid): array {
        global $DB;

        $included = $DB->get_fieldset_select(
            'tarsuszero_inclusions',
            'resourceid',
            'tarsuszeroid = :tid',
            ['tid' => $tarsuszeroid]
        );

        $repo = new repository();
        $allowed = [];
        foreach ($included as $resourceid) {
            $row = $repo->get($resourceid);
            if ($row !== null && $row->status === status::INDEXED) {
                $allowed[] = $resourceid;
            }
        }

        return $allowed;
    }
}
