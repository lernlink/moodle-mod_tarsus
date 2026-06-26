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

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Renderable for the learner chat container.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learner_chat implements renderable, templatable {

    /**
     * Constructor.
     *
     * @param int $cmid The course module id.
     */
    public function __construct(protected int $cmid) {
    }

    /**
     * Export template variables.
     *
     * @param renderer_base $output The renderer context.
     * @return array Exported variables.
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'cmid'        => $this->cmid,
            'containerid' => 'tarsus-chat-box-' . $this->cmid,
        ];
    }
}