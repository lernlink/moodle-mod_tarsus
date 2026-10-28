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

namespace mod_tarsus\completion;

use core_completion\activity_custom_completion;
use mod_tarsus\local\scoring;

/**
 * Completion rule: the learner's progress reached the teacher's threshold.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    #[\Override]
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);
        $activity = $DB->get_record('tarsus', ['id' => $this->cm->instance], '*', MUST_EXIST);
        if (empty($activity->completionprogress)) {
            return COMPLETION_INCOMPLETE;
        }
        $progress = scoring::progress($activity, scoring::row((int) $activity->id, $this->userid));
        return $progress >= (float) $activity->completionprogress ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    #[\Override]
    public static function get_defined_custom_rules(): array {
        return ['completionprogress'];
    }

    #[\Override]
    public function get_custom_rule_descriptions(): array {
        return [
            'completionprogress' => get_string('completiondetail:progress', 'mod_tarsus',
                (int) ($this->cm->customdata['customcompletionrules']['completionprogress'] ?? 0)),
        ];
    }

    #[\Override]
    public function get_sort_order(): array {
        return ['completionview', 'completionprogress'];
    }
}
