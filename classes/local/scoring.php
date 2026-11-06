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

use completion_info;

/**
 * Turns the brain's figures into a Moodle grade and a completion state.
 *
 * The brain reports, with every answer, the learner's experience points
 * (Test knowledge) and learning units (Build knowledge) alongside their
 * totals, plus three progress percentages. The latest figures are kept per
 * learner and activity: the gradebook grade is the share of what could be
 * earned in the modes the teacher switched on (0-100), and the completion
 * rule compares the matching progress figure with the teacher's threshold.
 * The rules follow Tarsus's own specification.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scoring {
    /**
     * Store the figures the brain just reported and push grade and completion.
     *
     * @param \stdClass $activity The tarsus record.
     * @param int       $userid   The learner.
     * @param array     $progress The client's progress figures (earnedxp, totalxp,
     *                            earnedunits, totalunits, practiceprogress, learnprogress, progress).
     * @return void
     */
    public static function record(\stdClass $activity, int $userid, array $progress): void {
        global $DB;

        if ($progress === []) {
            return;
        }
        $row = (object) [
            'tarsusid' => (int) $activity->id,
            'userid' => $userid,
            'earnedxp' => round((float) ($progress['earnedxp'] ?? 0), 2),
            'totalxp' => round((float) ($progress['totalxp'] ?? 0), 2),
            'earnedunits' => round((float) ($progress['earnedunits'] ?? 0), 2),
            'totalunits' => round((float) ($progress['totalunits'] ?? 0), 2),
            'practiceprogress' => round((float) ($progress['practiceprogress'] ?? 0), 2),
            'learnprogress' => round((float) ($progress['learnprogress'] ?? 0), 2),
            'progress' => round((float) ($progress['progress'] ?? 0), 2),
            'timemodified' => time(),
        ];
        $existing = $DB->get_record('tarsus_progress', ['tarsusid' => $activity->id, 'userid' => $userid]);
        if ($existing) {
            $row->id = $existing->id;
            $DB->update_record('tarsus_progress', $row);
        } else {
            $DB->insert_record('tarsus_progress', $row);
        }

        self::push($activity, $userid);
    }

    /**
     * Forget a learner's figures (the conversation was reset) and withdraw the grade.
     *
     * @param \stdClass $activity The tarsus record.
     * @param int       $userid   The learner.
     * @return void
     */
    public static function clear(\stdClass $activity, int $userid): void {
        global $DB;

        $DB->delete_records('tarsus_progress', ['tarsusid' => $activity->id, 'userid' => $userid]);
        self::push($activity, $userid);
    }

    /**
     * The stored figures of a learner, if any.
     *
     * @param int $instanceid The tarsus id.
     * @param int $userid     The learner.
     * @return \stdClass|null
     */
    public static function row(int $instanceid, int $userid): ?\stdClass {
        global $DB;

        $row = $DB->get_record('tarsus_progress', ['tarsusid' => $instanceid, 'userid' => $userid]);
        return $row ?: null;
    }

    /**
     * The gradebook grade (0-100) for a learner's figures.
     *
     * Test knowledge alone: earned / total experience points. Build
     * knowledge alone: earned / total learning units. Both: their mean.
     * Neither (chat only): no grade.
     *
     * @param \stdClass      $activity The tarsus record.
     * @param \stdClass|null $row      The stored figures.
     * @return float|null The grade, or null when the activity carries none.
     */
    public static function grade(\stdClass $activity, ?\stdClass $row): ?float {
        $test = didactics::mode_enabled($activity, 'test');
        $build = didactics::mode_enabled($activity, 'build');
        if (!$test && !$build) {
            return null;
        }
        if ($row === null) {
            return 0.0;
        }
        $parts = [];
        if ($test) {
            $parts[] = self::share((float) $row->earnedxp, (float) $row->totalxp);
        }
        if ($build) {
            $parts[] = self::share((float) $row->earnedunits, (float) $row->totalunits);
        }
        return round(array_sum($parts) / count($parts), 2);
    }

    /**
     * The progress percentage (0-100) the completion rule looks at.
     *
     * Test knowledge alone: practiceProgress. Build knowledge alone:
     * learnProgress. Both: the brain's overall progress (their mean).
     *
     * @param \stdClass      $activity The tarsus record.
     * @param \stdClass|null $row      The stored figures.
     * @return float
     */
    public static function progress(\stdClass $activity, ?\stdClass $row): float {
        if ($row === null) {
            return 0.0;
        }
        $test = didactics::mode_enabled($activity, 'test');
        $build = didactics::mode_enabled($activity, 'build');
        if ($test && $build) {
            $value = (float) $row->progress;
            if ($value <= 0) {
                $value = ((float) $row->practiceprogress + (float) $row->learnprogress) / 2;
            }
        } else if ($build) {
            $value = (float) $row->learnprogress;
        } else if ($test) {
            $value = (float) $row->practiceprogress;
        } else {
            $value = 0.0;
        }
        return round(max(0.0, min(100.0, $value)), 2);
    }

    /**
     * A share as a percentage, 0 when nothing can be earned.
     *
     * @param float $earned
     * @param float $total
     * @return float
     */
    private static function share(float $earned, float $total): float {
        if ($total <= 0) {
            return 0.0;
        }
        return max(0.0, min(100.0, $earned / $total * 100));
    }

    /**
     * Send the learner's grade to the gradebook and refresh completion.
     *
     * @param \stdClass $activity The tarsus record.
     * @param int       $userid   The learner.
     * @return void
     */
    private static function push(\stdClass $activity, int $userid): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/tarsus/lib.php');

        tarsus_update_grades($activity, $userid);

        $cm = get_coursemodule_from_instance('tarsus', $activity->id, $activity->course);
        if ($cm && !empty($activity->completionprogress)) {
            $course = get_course($cm->course);
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm)) {
                $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
            }
        }
    }
}
