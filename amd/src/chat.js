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
define(['core/ajax', 'core/templates', 'core/notification'], function(Ajax, Templates, Notification) {

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
        MODE_HINT: '.tarsus-mode-comingsoon'
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
        mode: 'ask'
    };

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
        var input = state.container.querySelector(SELECTORS.INPUT);
        var submit = state.container.querySelector(SELECTORS.SUBMIT);
        var hint = state.container.querySelector(SELECTORS.MODE_HINT);
        if (input) {
            input.classList.toggle('d-none', !ask);
            input.disabled = !ask;
        }
        if (submit) {
            submit.classList.toggle('d-none', !ask);
        }
        if (hint) {
            hint.classList.toggle('d-none', ask);
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
     * Query conversation history and load it into the flow.
     */
    function loadHistory() {
        var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
        if (!flow) {
            return;
        }

        Ajax.call([{
            methodname: 'mod_tarsuszero_get_conversation_history',
            args: {cmid: state.cmid}
        }])[0].then(function(history) {
            flow.innerHTML = '';
            // An empty conversation stays empty: the assistant's own welcome
            // arrives from the brain with the first answer. Cards render
            // asynchronously, so they are chained to keep the order.
            return history.reduce(function(previous, msg) {
                return previous.then(function() {
                    if (msg.query === '') {
                        return attachWelcome(msg.answer, null);
                    }
                    return attachExchange(msg.query, msg.answer, false);
                });
            }, Promise.resolve());
        }).catch(Notification.exception);
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

        loadHistory();
    }

    return {
        init: init
    };
});
