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

namespace mod_tarsus\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use context_module;
use local_tarsus\client\client_factory;
use local_tarsus\client\transport_exception;
use local_tarsus\guardrail\blocked_exception;
use local_tarsus\guardrail\pipeline;
use local_tarsus\usage_meter;
use mod_tarsus\local\conversation;
use mod_tarsus\local\didactics;
use mod_tarsus\local\scope;
use moodle_exception;

/**
 * External function that has the brain read a learning unit aloud.
 *
 * @package    mod_tarsus
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_stack_audio extends external_api {

    /**
     * Declares parameters for execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'The course module id of the tarsus activity.'),
            'stackid' => new external_value(PARAM_ALPHANUMEXT, 'The learning unit (stack) id.'),
        ]);
    }

    /**
     * Narrate one learning unit for the current user.
     *
     * @param int    $cmid    The course module id.
     * @param string $stackid The stack id.
     * @return array Status, the audio as a data URI and the voice used.
     * @throws moodle_exception
     */
    public static function execute(int $cmid, string $stackid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid, 'stackid' => $stackid]);
        if (trim($params['stackid']) === '') {
            throw new moodle_exception('invalidparameter', 'debug');
        }

        $cm = get_coursemodule_from_id('tarsus', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/tarsus:view', $context);

        // Narration belongs to Build knowledge.
        $activity = $DB->get_record('tarsus', ['id' => $cm->instance], '*', MUST_EXIST);
        if (!didactics::mode_enabled($activity, 'build')) {
            return self::result('disabled', get_string('chat_modedisabled', 'mod_tarsus'));
        }

        $allowed = scope::allowed_resource_ids((int) $cm->instance);
        if ($allowed === []) {
            return self::result('noscope', get_string('chat_noscope', 'mod_tarsus'));
        }

        $client = client_factory::get_client();
        $pipeline = new pipeline();
        $coursecontext = \context_course::instance($cm->course);

        $callparams = conversation::params($cm, $allowed) + [
            'stackid' => $params['stackid'],
        ];
        $callparams['voice'] = \local_tarsus\voices::choose((string) $callparams['userlanguage']);

        try {
            $response = $pipeline->execute(function() use ($client, $callparams) {
                return $client->generate_stack_audio($callparams);
            }, 6);

            if (($response['audio'] ?? '') === '') {
                return self::result('error', get_string('learn_audio_unavailable', 'mod_tarsus'));
            }

            (new usage_meter())->record(
                (int) $coursecontext->id, (int) $USER->id, 'learn_audio', (int) ($response['credits'] ?? 6));

            return self::result('success', '', [
                'audio' => 'data:' . $response['mimetype'] . ';base64,' . preg_replace('/\s+/', '', $response['audio']),
                'mimetype' => (string) $response['mimetype'],
                'voice' => (string) ($response['voice'] ?? ''),
            ]);

        } catch (blocked_exception $e) {
            return self::result('blocked', get_string('guardrail_blocked_' . $e->reason, 'mod_tarsus'));
        } catch (transport_exception $e) {
            return self::result('error', get_string('transport_error', 'mod_tarsus'));
        }
    }

    /**
     * Shape one result payload.
     *
     * @param string $status The result status.
     * @param string $error  The user-facing error, if any.
     * @param array  $extra  Additional keys (audio, mimetype, voice).
     * @return array The payload.
     */
    private static function result(string $status, string $error, array $extra = []): array {
        return $extra + ['status' => $status, 'error' => $error, 'audio' => '', 'mimetype' => '', 'voice' => ''];
    }

    /**
     * Declares return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'Status (success, noscope, disabled, blocked, error).'),
            'error' => new external_value(PARAM_RAW, 'Error message if failed or blocked.'),
            'audio' => new external_value(PARAM_RAW, 'The narration as an audio data URI, empty on failure.'),
            'mimetype' => new external_value(PARAM_RAW, 'The audio MIME type.'),
            'voice' => new external_value(PARAM_TEXT, 'The voice alias used.'),
        ]);
    }
}
