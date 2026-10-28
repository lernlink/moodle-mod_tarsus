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

namespace mod_tarsus\output;

use local_tarsus\index\course_report;
use mod_tarsus\local\status_presenter;
use renderable;
use renderer_base;
use templatable;

/**
 * The teacher reconciliation dashboard: per-activity brain status + index action.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teacher_dashboard implements renderable, templatable {

    /** @var int Number of course materials shown per page. */
    private const PER_PAGE = 10;

    /**
     * Constructor.
     *
     * @param int  $cmid      The course module id (action target).
     * @param int  $courseid  The course id to report on.
     * @param bool $canmanage Whether the viewer may trigger indexing.
     * @param int  $page      Zero-based page of the reconciliation list.
     */
    public function __construct(
        private int $cmid,
        private int $courseid,
        private bool $canmanage,
        private int $page = 0
    ) {
    }

    /**
     * Export the dashboard for the mustache template.
     *
     * @param renderer_base $output The renderer.
     * @return array<string, mixed>
     */
    public function export_for_template(renderer_base $output): array {
        global $DB;

        $report = (new course_report())->for_course($this->courseid);

        // Load the set of included resource ids for this activity instance.
        $tarsusid = (int) get_coursemodule_from_id('tarsus', $this->cmid, 0, false, MUST_EXIST)->instance;
        $includedids = $DB->get_fieldset_select(
            'tarsus_inclusions',
            'resourceid',
            'tarsusid = :tid',
            ['tid' => $tarsusid]
        );
        $includedset = array_flip($includedids);

        // Flatten every material bit into a single list so the reconciliation
        // list can be paged uniformly regardless of the owning activity.
        $resources = [];
        foreach ($report as $activity) {
            foreach ($activity['bits'] as $bit) {
                $controls = status_presenter::present(
                    $bit['resourceid'],
                    $bit['status'],
                    $bit['statuslabel'],
                    (int) ($bit['timemodified'] ?? 0)
                );
                $resources[] = $controls + [
                    'name' => $activity['name'],
                    'modname' => $activity['modname'],
                    'title' => $bit['title'],
                    'type' => $bit['type'],
                    'url' => $activity['url'],
                    'hasurl' => !empty($activity['url']),
                    // caninclude comes from the presenter; the stored choice
                    // only counts while the resource is actually indexed.
                    'isincluded' => $controls['caninclude'] && isset($includedset[$bit['resourceid']]),
                ];
            }
        }

        $total = count($resources);
        $pagecount = (int) ceil($total / self::PER_PAGE);
        $page = max(0, $this->page);
        if ($pagecount > 0 && $page >= $pagecount) {
            $page = $pagecount - 1;
        }

        $pageresources = array_slice($resources, $page * self::PER_PAGE, self::PER_PAGE);

        $baseurl = new \moodle_url('/mod/tarsus/view.php', ['id' => $this->cmid]);
        $pagingbar = $output->render(new \paging_bar($total, $page, self::PER_PAGE, $baseurl));

        return [
            'cmid' => $this->cmid,
            'canmanage' => $this->canmanage,
            'hasactivities' => $total > 0,
            'resources' => $pageresources,
            'hasmultiplepages' => $pagecount > 1,
            'pagingbar' => $pagingbar,
        ];
    }
}
