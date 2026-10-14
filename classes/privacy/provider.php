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

namespace mod_tarsuszero\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_tarsuszero.
 *
 * The activity keeps, per learner, the latest figures the brain reported
 * (experience points, learning units, progress): they feed the gradebook
 * grade and the completion rule. The conversation itself, indexation and
 * usage data live in the brain and in local_tarsus.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('tarsuszero_progress', [
            'userid' => 'privacy:metadata:tarsuszero_progress:userid',
            'earnedxp' => 'privacy:metadata:tarsuszero_progress:earnedxp',
            'totalxp' => 'privacy:metadata:tarsuszero_progress:totalxp',
            'earnedunits' => 'privacy:metadata:tarsuszero_progress:earnedunits',
            'totalunits' => 'privacy:metadata:tarsuszero_progress:totalunits',
            'practiceprogress' => 'privacy:metadata:tarsuszero_progress:practiceprogress',
            'learnprogress' => 'privacy:metadata:tarsuszero_progress:learnprogress',
            'progress' => 'privacy:metadata:tarsuszero_progress:progress',
            'timemodified' => 'privacy:metadata:tarsuszero_progress:timemodified',
        ], 'privacy:metadata:tarsuszero_progress');
        $collection->add_subsystem_link('core_grades', [], 'privacy:metadata:core_grades');

        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {tarsuszero_progress} p
                  JOIN {tarsuszero} t ON t.id = p.tarsuszeroid
                  JOIN {course_modules} cm ON cm.instance = t.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE p.userid = :userid";
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, ['modname' => 'tarsuszero', 'contextlevel' => CONTEXT_MODULE, 'userid' => $userid]);

        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $sql = "SELECT p.userid
                  FROM {tarsuszero_progress} p
                  JOIN {tarsuszero} t ON t.id = p.tarsuszeroid
                  JOIN {course_modules} cm ON cm.instance = t.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, ['modname' => 'tarsuszero', 'cmid' => $context->instanceid]);
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('tarsuszero', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $row = $DB->get_record('tarsuszero_progress', ['tarsuszeroid' => $cm->instance, 'userid' => $userid]);
            if (!$row) {
                continue;
            }
            writer::with_context($context)->export_data([get_string('privacy:path:progress', 'mod_tarsuszero')], (object) [
                'earnedxp' => (float) $row->earnedxp,
                'totalxp' => (float) $row->totalxp,
                'earnedunits' => (float) $row->earnedunits,
                'totalunits' => (float) $row->totalunits,
                'practiceprogress' => (float) $row->practiceprogress,
                'learnprogress' => (float) $row->learnprogress,
                'progress' => (float) $row->progress,
                'timemodified' => transform::datetime($row->timemodified),
            ]);
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('tarsuszero', $context->instanceid);
        if ($cm) {
            $DB->delete_records('tarsuszero_progress', ['tarsuszeroid' => $cm->instance]);
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('tarsuszero', $context->instanceid);
            if ($cm) {
                $DB->delete_records('tarsuszero_progress', ['tarsuszeroid' => $cm->instance, 'userid' => $userid]);
            }
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('tarsuszero', $context->instanceid);
        if (!$cm || $userlist->get_userids() === []) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['tarsuszeroid'] = $cm->instance;
        $DB->delete_records_select('tarsuszero_progress', "tarsuszeroid = :tarsuszeroid AND userid $insql", $params);
    }
}
