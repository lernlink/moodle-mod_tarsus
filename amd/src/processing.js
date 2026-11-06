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

/**
 * The "brain is working" indicator shown under the feed while a request is
 * in flight: a slim card with a running bar whose caption walks through
 * what the brain does for this mode, step by step, on fixed timers (the
 * brain reports no real progress). The last step holds until the answer
 * arrives; the caller removes the card then.
 *
 * The step texts and timings mirror the Tarsus KI-Trainer.
 *
 * @module     mod_tarsus/processing
 * @copyright  2026 lern.link GmbH | Vadym Nersesov <nersesov.vadim@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/templates', 'core/str'], function(Templates, Str) {

    /**
     * A step: the lang string key, the pix icon key, how long it shows (ms)
     * and the icon's turn time (s).
     *
     * @param {string} text
     * @param {string} icon
     * @param {number} duration
     * @param {number} spin
     * @return {Object}
     */
    var step = function(text, icon, duration, spin) {
        return {text: text, icon: icon, duration: duration, spin: spin};
    };

    var ASK = [
        step('proc_ask_classify', 'proc_diagram', 3200, 3.2),
        step('proc_ask_search', 'proc_atom', 4200, 2.6),
        step('proc_ask_media', 'proc_shapes', 4000, 3.4),
        step('proc_ask_select', 'proc_dot', 3800, 3.6),
        step('proc_ask_cube', 'proc_cube', 3600, 3.2),
        step('proc_ask_dqr', 'proc_gear', 3400, 4),
        step('proc_ask_compose', 'proc_sparkle', 5500, 2.4),
        step('proc_ask_verify', 'proc_notch', 4500, 3.8),
        step('proc_ask_polish', 'proc_sun', 7000, 3)
    ];

    var PRACTICE_FIRST = [
        step('proc_practice_cube_create', 'proc_cube', 3600, 3.2),
        step('proc_practice_baseline', 'proc_chart', 3800, 3.4)
    ];
    var PRACTICE_NEXT = [
        step('proc_practice_evaluate', 'proc_asterisk', 3200, 2.8),
        step('proc_practice_compare', 'proc_chart', 3800, 3.4),
        step('proc_practice_surprise', 'proc_gear', 3600, 4),
        step('proc_practice_reestimate', 'proc_atom', 3200, 2.6)
    ];
    var PRACTICE = [
        step('proc_practice_align', 'proc_cube', 4000, 3.2),
        step('proc_practice_cells', 'proc_crosshair', 3800, 3.6),
        step('proc_practice_history', 'proc_hurricane', 3800, 4.2),
        step('proc_practice_action', 'proc_target', 3600, 4),
        step('proc_practice_taxonomy', 'proc_diagram', 3600, 3.2),
        step('proc_practice_difficulty', 'proc_gear', 3400, 4),
        step('proc_practice_format', 'proc_shapes', 3400, 3.4),
        step('proc_practice_generate', 'proc_sparkle', 5000, 2.4),
        step('proc_practice_operators', 'proc_star', 4000, 3.6),
        step('proc_practice_options', 'proc_snowflake', 4500, 2.4),
        step('proc_practice_solution', 'proc_fan', 4200, 2.8),
        step('proc_practice_polish', 'proc_sun', 7000, 3)
    ];

    var LEARN_FIRST = [
        step('proc_learn_path_create', 'proc_hurricane', 3600, 4.2)
    ];
    var LEARN_NEXT = [
        step('proc_learn_progress', 'proc_chart', 3200, 3.4),
        step('proc_learn_objectives', 'proc_notch', 3800, 3.8)
    ];
    var LEARN = [
        step('proc_learn_cells', 'proc_cube', 3800, 3.2),
        step('proc_learn_next', 'proc_crosshair', 4000, 3.6),
        step('proc_learn_passages', 'proc_atom', 4500, 2.6),
        step('proc_learn_dqr', 'proc_gear', 3600, 4),
        step('proc_learn_split', 'proc_diagram', 4000, 3.2),
        step('proc_learn_examples', 'proc_seedling', 3800, 3),
        step('proc_learn_link', 'proc_nodes', 4200, 3.4),
        step('proc_learn_check', 'proc_dot', 4200, 3.6),
        step('proc_learn_path_update', 'proc_hurricane', 5000, 2.8),
        step('proc_learn_polish', 'proc_sparkle', 7000, 2.8)
    ];

    /**
     * The step sequence for a mode.
     *
     * @param {string} mode 'ask', 'test' or 'build'
     * @param {boolean} firstRun Whether this is the learner's first step in the mode
     * @return {Array}
     */
    var sequence = function(mode, firstRun) {
        if (mode === 'test') {
            return (firstRun ? PRACTICE_FIRST : PRACTICE_NEXT).concat(PRACTICE);
        }
        if (mode === 'build') {
            return (firstRun ? LEARN_FIRST : LEARN_NEXT).concat(LEARN);
        }
        return ASK;
    };

    /**
     * Show the indicator at the end of the flow and start walking the steps.
     *
     * @param {Element} flow The chat flow to append to
     * @param {string} mode 'ask', 'test' or 'build'
     * @param {boolean} firstRun Whether this is the learner's first step in the mode
     * @return {Promise} Resolves to a handle {element, stop} once the card is in the flow
     */
    var start = function(flow, mode, firstRun) {
        var steps = sequence(mode, firstRun);
        var stopped = false;
        var timer = null;
        var element = null;

        var requests = steps.map(function(entry) {
            return {key: entry.text, component: 'mod_tarsus'};
        });

        return Promise.all([
            Str.get_strings(requests),
            Promise.all(steps.map(function(entry) {
                return Templates.renderPix(entry.icon, 'mod_tarsus');
            })),
            Templates.render('mod_tarsus/processing_card', {})
        ]).then(function(results) {
            var texts = results[0];
            var icons = results[1];
            if (stopped) {
                return null;
            }
            var holder = document.createElement('div');
            holder.innerHTML = results[2];
            element = holder.firstElementChild;
            flow.appendChild(element);

            var iconSlot = element.querySelector('[data-region="icon"]');
            var textSlot = element.querySelector('[data-region="text"]');
            var show = function(index) {
                if (stopped) {
                    return;
                }
                iconSlot.innerHTML = icons[index];
                var glyph = iconSlot.querySelector('.icon');
                if (glyph) {
                    glyph.style.animationDuration = steps[index].spin + 's';
                }
                textSlot.textContent = texts[index];
                if (index < steps.length - 1) {
                    timer = window.setTimeout(function() {
                        show(index + 1);
                    }, steps[index].duration);
                }
            };
            show(0);
            return null;
        }).then(function() {
            return {
                element: element,
                /**
                 * Remove the indicator and stop the timers.
                 */
                stop: function() {
                    stopped = true;
                    if (timer) {
                        window.clearTimeout(timer);
                    }
                    if (element && element.parentNode) {
                        element.parentNode.removeChild(element);
                    }
                }
            };
        });
    };

    return {
        start: start
    };
});
