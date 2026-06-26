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

use core_privacy\local\metadata\null_provider;

/**
 * Privacy provider for mod_tarsuszero.
 *
 * The activity itself stores no personal data; all indexation and usage data
 * lives in (and is declared by) the local_tarsus plugin.
 *
 * @package    mod_tarsuszero
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements null_provider {

    /**
     * Reason why this plugin stores no personal data.
     *
     * @return string The lang string identifier.
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
