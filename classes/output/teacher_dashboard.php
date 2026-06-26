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

namespace mod_tarsuszero\output;

use local_tarsus\index\course_report;
use mod_tarsuszero\local\status_presenter;
use renderable;
use renderer_base;
use templatable;

/**
 * The teacher reconciliation dashboard: per-activity brain status + index action.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teacher_dashboard implements renderable, templatable {

    /**
     * Constructor.
     *
     * @param int  $cmid      The course module id (action target).
     * @param int  $courseid  The course id to report on.
     * @param bool $canmanage Whether the viewer may trigger indexing.
     */
    public function __construct(
        private int $cmid,
        private int $courseid,
        private bool $canmanage
    ) {
    }

    /**
     * Export the dashboard for the mustache template.
     *
     * @param renderer_base $output The renderer.
     * @return array<string, mixed>
     */
    public function export_for_template(renderer_base $output): array {
        $report = (new course_report())->for_course($this->courseid);

        $activities = [];
        foreach ($report as $activity) {
            $bits = [];
            foreach ($activity['bits'] as $bit) {
                $controls = status_presenter::present(
                    $bit['resourceid'],
                    $bit['status'],
                    $bit['statuslabel']
                );
                $bits[] = $controls + [
                    'title' => $bit['title'],
                    'type' => $bit['type'],
                    'url' => $activity['url'],
                    'hasurl' => !empty($activity['url']),
                ];
            }
            $activities[] = [
                'name' => $activity['name'],
                'modname' => $activity['modname'],
                'bits' => $bits,
            ];
        }

        $indexurl = new \moodle_url('/mod/tarsuszero/view.php', [
            'id' => $this->cmid,
            'action' => 'indexcourse',
            'sesskey' => sesskey(),
        ]);

        return [
            'cmid' => $this->cmid,
            'canmanage' => $this->canmanage,
            'indexurl' => $indexurl->out(false),
            'hasactivities' => !empty($activities),
            'activities' => $activities,
        ];
    }
}
