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

namespace mod_tarsus\output;

use local_tarsus\config;
use mod_tarsus\local\didactics;
use renderable;
use templatable;
use renderer_base;

/**
 * Renderable for the learner chat container.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learner_chat implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param int $cmid The course module id.
     */
    public function __construct(
        /** @var int The course module id */
        protected int $cmid
    ) {
    }

    /**
     * Export template variables.
     *
     * The two avatars ride along as data attributes: the chat renders its
     * cards client-side, and every card carries the learner's picture next
     * to the question and the assistant's next to the answer.
     *
     * @param renderer_base $output The renderer context.
     * @return array Exported variables.
     */
    public function export_for_template(renderer_base $output): array {
        global $DB, $USER, $PAGE;

        if (!empty($USER->id)) {
            $userpicture = new \user_picture($USER);
            $userpicture->size = 64;
            $useravatar = $userpicture->get_url($PAGE)->out(false);
        } else {
            // Not logged in (unit tests): the core anonymous face.
            $useravatar = $output->image_url('u/f2')->out(false);
        }

        $robotavatar = config::robot_avatar_url();
        if ($robotavatar === '') {
            // No avatar uploaded in the admin settings: the bundled robot.
            $robotavatar = $output->image_url('robot', 'mod_tarsus')->out(false);
        }

        $robotname = trim((string) get_config('local_tarsus', 'robot_name'));

        // The teacher's mode switches decide which modes the switcher offers;
        // the first enabled one opens by default.
        $cm = get_coursemodule_from_id('tarsus', $this->cmid, 0, false, MUST_EXIST);
        $activity = $DB->get_record('tarsus', ['id' => $cm->instance], '*', MUST_EXIST);
        $modes = array_values(array_filter(
            didactics::MODES,
            static fn(string $mode): bool => didactics::mode_enabled($activity, $mode)
        ));

        return [
            'cmid'          => $this->cmid,
            'containerid'   => 'tarsus-chat-box-' . $this->cmid,
            'useravatarurl' => $useravatar,
            'robotavatarurl' => $robotavatar,
            'robotname'     => $robotname !== '' ? $robotname : 'Tarsus',
            'askenabled'    => in_array('ask', $modes, true),
            'testenabled'   => in_array('test', $modes, true),
            'buildenabled'  => in_array('build', $modes, true),
            'modes'         => implode(',', $modes),
        ];
    }
}
