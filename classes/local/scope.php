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

namespace mod_tarsus\local;

use local_tarsus\index\repository;
use local_tarsus\index\status;
use local_tarsus\material\resource_id;

/**
 * The set of resources allowed to ground an activity's conversation.
 *
 * The teacher's inclusion set, filtered down to rows the brain actually holds
 * right now. Filtering at read time means a resource that was removed or
 * replaced after being included silently drops out of scope — no cleanup
 * ordering to get wrong. Both asking and reading the conversation need this:
 * the t0 surface requires the scope on every call.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scope {
    /**
     * The brain-side conversation context id of an activity.
     *
     * Site-scoped, so two Moodle sites sharing a brain cannot share a
     * conversation. Every t0 call about this activity uses this id.
     *
     * @param int $tarsusid The activity instance id.
     * @return string The conversation context id.
     */
    public static function conversation_context_id(int $tarsusid): string {
        return resource_id::site_token() . ':tarsus:' . $tarsusid;
    }

    /**
     * The resource ids allowed to ground this activity's answers.
     *
     * @param int $tarsusid The activity instance id.
     * @return string[] Namespaced resource ids, possibly empty.
     */
    public static function allowed_resource_ids(int $tarsusid): array {
        global $DB;

        $included = $DB->get_fieldset_select(
            'tarsus_inclusions',
            'resourceid',
            'tarsusid = :tid',
            ['tid' => $tarsusid]
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
