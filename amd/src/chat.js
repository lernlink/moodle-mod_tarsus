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
 * Tarsus learner chat companion module.
 *
 * The conversation renders as centred cards: an exchange card unites the
 * learner's question (with their avatar) and the assistant's answer (with
 * its avatar) around a thin divider, and informational messages from the
 * assistant — the welcome above all — appear as gradient-bordered cards.
 *
 * @module     mod_tarsuszero/chat
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/templates', 'core/notification', 'core/str'], function(Ajax, Templates, Notification, Str) {

    /**
     * DOM selectors.
     * @private
     */
    var SELECTORS = {
        CHAT_FLOW: '.tarsus-chat-flow',
        FORM: '.tarsus-chat-form',
        INPUT: '.tarsus-chat-input',
        SUBMIT: '.tarsus-chat-submit',
        ALERT: '.tarsus-chat-alert',
        SPINNER: '.tarsus-chat-spinner',
        CARD_ANSWER: '[data-region="card-answer"]',
        MODE_BTN: '.tarsus-mode-switch__btn',
        MODE_HINT: '.tarsus-mode-comingsoon',
        PRACTICE_NEXT: '.tarsus-practice-next',
        PRACTICE_NEXT_SPINNER: '.tarsus-practice-next__spinner',
        PRACTICE_LABEL: '[data-region="practice-label"]',
        PRACTICE_CARD: '[data-region="practice-card"]',
        PRACTICE_CHECK: '.tarsus-practice-check',
        PRACTICE_CHECK_SPINNER: '.tarsus-practice-check__spinner',
        PRACTICE_CHOICE: '.tarsus-practice-choice',
        PRACTICE_INPUT: '.tarsus-practice-card__input',
        PRACTICE_FEEDBACK: '[data-region="feedback"]',
        XP_BADGE: '[data-region="xp-badge"]',
        XP_EARNED: '[data-region="xp-earned"]',
        XP_TOTAL: '[data-region="xp-total"]'
    };

    /**
     * CSS classes.
     * @private
     */
    var CLASSES = {
        MODE_ACTIVE: 'tarsus-mode-switch__btn--active'
    };

    /**
     * Chat state parameters.
     * @private
     */
    var state = {
        cmid: null,
        container: null,
        userAvatar: '',
        robotAvatar: '',
        robotName: '',
        mode: 'ask',
        practiceStarted: false
    };

    /**
     * Freeze an answered practice card: mark the pick, show the feedback.
     *
     * Shared by fresh evaluations and by cards restored from the brain.
     *
     * @param {Element} card The practice card element.
     * @param {Object} verdict {correct: bool, feedback: string (HTML), useranswer: string}
     */
    function finalizePracticeCard(card, verdict) {
        card.classList.add('tarsus-practice-card--answered');

        var chosen = card.querySelector('input[type="radio"]:checked');
        if (!chosen && verdict.useranswer) {
            // Restored card: re-select what the learner answered back then.
            card.querySelectorAll('input[type="radio"]').forEach(function(input) {
                if (input.value === verdict.useranswer) {
                    input.checked = true;
                    chosen = input;
                }
            });
        }
        card.querySelectorAll('input').forEach(function(input) {
            input.disabled = true;
        });

        var textinput = card.querySelector(SELECTORS.PRACTICE_INPUT);
        if (textinput) {
            if (verdict.useranswer && textinput.value === '') {
                textinput.value = verdict.useranswer;
            }
            textinput.disabled = true;
        }

        var check = card.querySelector(SELECTORS.PRACTICE_CHECK);
        if (check) {
            check.classList.add('d-none');
        }

        if (chosen) {
            var choicebox = chosen.closest(SELECTORS.PRACTICE_CHOICE);
            if (choicebox) {
                choicebox.classList.add(verdict.correct
                    ? 'tarsus-practice-choice--correct'
                    : 'tarsus-practice-choice--incorrect');
            }
        }

        var feedback = card.querySelector(SELECTORS.PRACTICE_FEEDBACK);
        if (feedback && verdict.feedback) {
            feedback.classList.remove('d-none');
            feedback.classList.add(verdict.correct
                ? 'tarsus-practice-card__feedback--correct'
                : 'tarsus-practice-card__feedback--incorrect');
            // Server-cleaned HTML from the external function.
            feedback.innerHTML = verdict.feedback;
        }
    }

    /**
     * Attach one practice question card to the flow.
     *
     * @param {Object} widget {id, type, question, choices, taxonomy, difficulty}
     * @return {Promise} Resolves to the card element.
     */
    function attachPracticeCard(widget) {
        // The difficulty renders as three bars, filled up to the grade,
        // as in the Tarsus interface; the label stays as the tooltip.
        var level = widget.difficultylevel || 0;
        var bars = level > 0 ? [1, 2, 3].map(function(bar) {
            return {filled: bar <= level};
        }) : [];

        return attachCard('mod_tarsuszero/practice_card', {
            widgetid: widget.id,
            widgettype: widget.type,
            question: widget.question,
            haschoices: widget.choices.length > 0,
            choices: widget.choices,
            taxonomy: widget.taxonomy,
            difficulty: widget.difficulty,
            bars: bars,
            hasbars: bars.length > 0,
            hasmeta: Boolean(widget.taxonomy || bars.length)
        }, null);
    }

    /**
     * Reopen the whole activity feed from the brain, on page load.
     *
     * One interleaved stream across the interaction modes, as in the Tarsus
     * interface: chat cards and practice cards render in their original
     * order and stay visible whatever mode is active. Answered questions
     * return frozen with their verdicts, unanswered ones stay answerable;
     * the experience figures come along in the same call. Nothing is
     * stored locally — the brain is the only store.
     */
    function loadFeed() {
        var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
        if (!flow) {
            return;
        }

        Ajax.call([{
            methodname: 'mod_tarsuszero_get_activity_feed',
            args: {cmid: state.cmid}
        }])[0].then(function(result) {
            flow.innerHTML = '';
            if (result.status !== 'success') {
                // A quiet miss: the learner can still interact; the feed
                // just starts visually empty.
                return null;
            }
            updateXp(result.progress);

            var haspractice = false;
            return result.items.reduce(function(previous, item) {
                return previous.then(function() {
                    if (item.kind === 'message') {
                        return attachWelcome(item.text, null);
                    }
                    if (item.kind === 'exchange') {
                        return attachExchange(item.query, item.answer, false);
                    }
                    haspractice = true;
                    return attachPracticeCard(item).then(function(card) {
                        if (card && item.answered) {
                            finalizePracticeCard(card, {
                                correct: item.correct,
                                feedback: item.feedback,
                                useranswer: item.useranswer
                            });
                        }
                        return null;
                    });
                });
            }, Promise.resolve()).then(function() {
                if (haspractice && !state.practiceStarted) {
                    state.practiceStarted = true;
                    var label = state.container.querySelector(SELECTORS.PRACTICE_LABEL);
                    return Str.get_string('practice_next', 'mod_tarsuszero').then(function(next) {
                        if (label) {
                            label.textContent = next;
                        }
                        return null;
                    });
                }
                return null;
            });
        }).catch(Notification.exception);
    }

    /**
     * Show the experience figures once the practice mode reports them.
     *
     * @param {Object|undefined} progress {earnedxp, totalxp, practiceprogress}
     */
    function updateXp(progress) {
        if (!progress || !(progress.totalxp > 0)) {
            return;
        }
        var badge = state.container.querySelector(SELECTORS.XP_BADGE);
        if (!badge) {
            return;
        }
        badge.classList.remove('d-none');
        badge.querySelector(SELECTORS.XP_EARNED).textContent = Math.round(progress.earnedxp * 100) / 100;
        badge.querySelector(SELECTORS.XP_TOTAL).textContent = Math.round(progress.totalxp * 100) / 100;
    }

    /**
     * Fetch and render the next practice question.
     */
    function handlePracticeNext() {
        var button = state.container.querySelector(SELECTORS.PRACTICE_NEXT);
        var spinner = state.container.querySelector(SELECTORS.PRACTICE_NEXT_SPINNER);
        var alertBox = state.container.querySelector(SELECTORS.ALERT);
        if (!button || button.disabled) {
            return;
        }

        button.disabled = true;
        if (spinner) {
            spinner.classList.remove('d-none');
        }
        if (alertBox) {
            alertBox.classList.add('d-none');
        }

        var unlock = function() {
            button.disabled = false;
            if (spinner) {
                spinner.classList.add('d-none');
            }
        };

        Ajax.call([{
            methodname: 'mod_tarsuszero_get_practice_widget',
            args: {cmid: state.cmid}
        }])[0].then(function(result) {
            unlock();

            if (result.status !== 'success' || !result.widgets || !result.widgets.length) {
                if (alertBox) {
                    alertBox.classList.remove('d-none');
                    alertBox.textContent = result.error || 'Unknown error occurred.';
                }
                return null;
            }

            updateXp(result.progress);

            // As many cards as the brain generated for this step.
            return result.widgets.reduce(function(previous, widget) {
                return previous.then(function() {
                    return attachPracticeCard(widget);
                });
            }, Promise.resolve()).then(function() {
                if (!state.practiceStarted) {
                    state.practiceStarted = true;
                    var label = state.container.querySelector(SELECTORS.PRACTICE_LABEL);
                    return Str.get_string('practice_next', 'mod_tarsuszero').then(function(next) {
                        if (label) {
                            label.textContent = next;
                        }
                        return null;
                    });
                }
                return null;
            });
        }).catch(function(ex) {
            unlock();
            Notification.exception(ex);
        });
    }

    /**
     * Evaluate the answer of one practice card.
     *
     * @param {Element} card The practice card element.
     */
    function handlePracticeCheck(card) {
        var check = card.querySelector(SELECTORS.PRACTICE_CHECK);
        var spinner = card.querySelector(SELECTORS.PRACTICE_CHECK_SPINNER);
        if (!check || check.disabled) {
            return;
        }

        var answer = '';
        var chosen = card.querySelector('input[type="radio"]:checked');
        var textinput = card.querySelector(SELECTORS.PRACTICE_INPUT);
        if (chosen) {
            answer = chosen.value;
        } else if (textinput) {
            answer = textinput.value.trim();
        }
        if (answer === '') {
            return;
        }

        check.disabled = true;
        if (spinner) {
            spinner.classList.remove('d-none');
        }

        Ajax.call([{
            methodname: 'mod_tarsuszero_submit_practice_answer',
            args: {
                cmid: state.cmid,
                widgetid: card.getAttribute('data-widget-id'),
                widgettype: card.getAttribute('data-widget-type'),
                answer: answer
            }
        }])[0].then(function(result) {
            if (spinner) {
                spinner.classList.add('d-none');
            }

            if (result.status !== 'success') {
                check.disabled = false;
                var alertBox = state.container.querySelector(SELECTORS.ALERT);
                if (alertBox) {
                    alertBox.classList.remove('d-none');
                    alertBox.textContent = result.error || 'Unknown error occurred.';
                }
                return null;
            }

            // The card is answered: freeze it, mark the learner's pick and
            // show the feedback, as in the Tarsus interface.
            finalizePracticeCard(card, {
                correct: result.correct,
                feedback: result.feedback || result.answer || '',
                useranswer: answer
            });

            updateXp(result.progress);
            var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
            if (flow) {
                flow.scrollTop = flow.scrollHeight;
            }
            return null;
        }).catch(function(ex) {
            check.disabled = false;
            if (spinner) {
                spinner.classList.add('d-none');
            }
            Notification.exception(ex);
        });
    }

    /**
     * Switch the interaction mode.
     *
     * Only 'ask' (the chat) is functional so far; the other modes will
     * steer the assistant's behaviour later. The chat input and the send
     * button belong to 'ask' alone — the other modes will interact
     * through cards in the flow, so the composer collapses to the
     * switcher row with a coming-soon hint.
     *
     * @param {string} mode One of 'build', 'test', 'ask'
     */
    function setMode(mode) {
        state.mode = mode;

        state.container.querySelectorAll(SELECTORS.MODE_BTN).forEach(function(btn) {
            var active = btn.getAttribute('data-mode') === mode;
            btn.classList.toggle(CLASSES.MODE_ACTIVE, active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        var ask = mode === 'ask';
        var test = mode === 'test';
        var input = state.container.querySelector(SELECTORS.INPUT);
        var submit = state.container.querySelector(SELECTORS.SUBMIT);
        var hint = state.container.querySelector(SELECTORS.MODE_HINT);
        var practice = state.container.querySelector(SELECTORS.PRACTICE_NEXT);
        if (input) {
            input.classList.toggle('d-none', !ask);
            input.disabled = !ask;
        }
        if (submit) {
            submit.classList.toggle('d-none', !ask);
        }
        if (practice) {
            practice.classList.toggle('d-none', !test);
        }
        if (hint) {
            hint.classList.toggle('d-none', ask || test);
        }
    }

    /**
     * Render a card template and attach it to the flow.
     *
     * @param {string} template Template name
     * @param {Object} context Template context
     * @param {Element|null} before Insert before this element (append when null)
     * @return {Promise} Resolves to the attached card element (or null)
     */
    function attachCard(template, context, before) {
        var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
        if (!flow) {
            return Promise.resolve(null);
        }

        return Templates.render(template, context).then(function(html, js) {
            var holder = document.createElement('div');
            holder.innerHTML = html;
            var card = holder.firstElementChild;
            if (before && before.parentNode === flow) {
                // Inserting above (older history, welcome cards): keep the
                // reader's viewport where it is instead of yanking it.
                var grewBy = flow.scrollHeight;
                flow.insertBefore(card, before);
                flow.scrollTop += flow.scrollHeight - grewBy;
            } else {
                flow.appendChild(card);
                flow.scrollTop = flow.scrollHeight;
            }
            if (js) {
                Templates.runTemplateJS(js);
            }
            return card;
        });
    }

    /**
     * Attach an informational card (e.g. the assistant's welcome).
     *
     * @param {string} messageHtml Server-cleaned message HTML
     * @param {Element|null} before Insert before this element
     * @return {Promise} Resolves to the card element
     */
    function attachWelcome(messageHtml, before) {
        return attachCard('mod_tarsuszero/chat_welcome_card', {message: messageHtml}, before || null);
    }

    /**
     * Attach an exchange card (question and answer united).
     *
     * @param {string} question The learner's question
     * @param {string} answerHtml Server-cleaned answer HTML ('' while pending)
     * @param {boolean} pending Whether the answer is still being generated
     * @return {Promise} Resolves to the card element
     */
    function attachExchange(question, answerHtml, pending) {
        return attachCard('mod_tarsuszero/chat_card', {
            question: question,
            answer: answerHtml || '',
            pending: !!pending,
            useravatarurl: state.userAvatar,
            robotavatarurl: state.robotAvatar,
            robotname: state.robotName
        }, null);
    }

    /**
     * Perform chat form submission.
     *
     * @param {Event} e The submit event.
     */
    function handleSubmit(e) {
        e.preventDefault();

        var input = state.container.querySelector(SELECTORS.INPUT);
        var submit = state.container.querySelector(SELECTORS.SUBMIT);
        var spinner = state.container.querySelector(SELECTORS.SPINNER);
        var alertBox = state.container.querySelector(SELECTORS.ALERT);

        if (!input || !submit) {
            return;
        }

        var query = input.value.trim();
        if (query === '') {
            return;
        }

        input.disabled = true;
        submit.disabled = true;
        if (spinner) {
            spinner.classList.remove('d-none');
        }
        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
        }
        input.value = '';

        var unlock = function() {
            input.disabled = false;
            submit.disabled = false;
            if (spinner) {
                spinner.classList.add('d-none');
            }
            input.focus();
        };

        // The question appears at once, its answer slot thinking.
        attachExchange(query, '', true).then(function(card) {
            return Ajax.call([{
                methodname: 'mod_tarsuszero_send_chat_message',
                args: {cmid: state.cmid, query: query}
            }])[0].then(function(result) {
                unlock();

                if (result.status !== 'success') {
                    if (card) {
                        card.remove();
                    }
                    input.value = query;
                    if (alertBox) {
                        alertBox.classList.remove('d-none');
                        alertBox.textContent = result.error || 'Unknown error occurred.';
                    }
                    return null;
                }

                // Informational cards (e.g. the welcome) slot in above the
                // exchange, in the order Tarsus sent them.
                return (result.messages || []).reduce(function(previous, message) {
                    return previous.then(function() {
                        return attachWelcome(message, card);
                    });
                }, Promise.resolve()).then(function() {
                    var slot = card ? card.querySelector(SELECTORS.CARD_ANSWER) : null;
                    if (slot) {
                        // Server-cleaned HTML from the external function.
                        slot.innerHTML = result.answer;
                    }
                    var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
                    if (flow) {
                        flow.scrollTop = flow.scrollHeight;
                    }
                    return null;
                });
            }).catch(function(ex) {
                unlock();
                if (card) {
                    card.remove();
                }
                input.value = query;
                Notification.exception(ex);
            });
        }).catch(Notification.exception);
    }

    /**
     * Initialize the chat UI and register key event handlers.
     *
     * @param {int} cmid Course module ID
     * @param {string} containerSelector CSS selector enclosing the chat UI
     */
    function init(cmid, containerSelector) {
        state.cmid = parseInt(cmid, 10);
        state.container = document.querySelector(containerSelector);

        if (!state.container) {
            return;
        }

        state.userAvatar = state.container.getAttribute('data-user-avatar') || '';
        state.robotAvatar = state.container.getAttribute('data-robot-avatar') || '';
        state.robotName = state.container.getAttribute('data-robot-name') || '';

        var form = state.container.querySelector(SELECTORS.FORM);
        if (form) {
            form.addEventListener('submit', handleSubmit);
        }

        state.container.querySelectorAll(SELECTORS.MODE_BTN).forEach(function(btn) {
            btn.addEventListener('click', function() {
                setMode(btn.getAttribute('data-mode'));
            });
        });

        var practice = state.container.querySelector(SELECTORS.PRACTICE_NEXT);
        if (practice) {
            practice.addEventListener('click', handlePracticeNext);
        }

        // Practice cards render later; delegate their check buttons.
        state.container.addEventListener('click', function(e) {
            var check = e.target.closest(SELECTORS.PRACTICE_CHECK);
            if (check && state.container.contains(check)) {
                var card = check.closest(SELECTORS.PRACTICE_CARD);
                if (card) {
                    handlePracticeCheck(card);
                }
            }
        });

        loadFeed();
    }

    return {
        init: init
    };
});
