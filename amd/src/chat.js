/**
 * Tarsus Zero Learner RAG Chat UI companion module.
 *
 * Handles chat loading, submit actions, AJAX queries to Moodle External APIs
 * and rendering responses.
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
        CHAT_BOX: '.tarsus-chat-box',
        CHAT_FLOW: '.tarsus-chat-flow',
        FORM: '.tarsus-chat-form',
        INPUT: '.tarsus-chat-input',
        SUBMIT: '.tarsus-chat-submit',
        ALERT: '.tarsus-chat-alert',
        SPINNER: '.tarsus-chat-spinner'
    };

    /**
     * Chat state parameters.
     * @private
     */
    var state = {
        cmid: null,
        container: null
    };

    /**
     * Render and append a message to the chat flow.
     *
     * @param {string} sender 'learner' or 'brain'
     * @param {string} text Message content
     * @param {Array} sources Optional sources list
     */
    function appendMessage(sender, text, sources) {
        var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
        if (!flow) {
            return;
        }

        var data = {
            islearner: (sender === 'learner'),
            isbrain: (sender === 'brain'),
            text: text,
            hassources: (sources && sources.length > 0),
            sources: sources || []
        };

        Templates.render('mod_tarsuszero/chat_message', data)
            .then(function(html, js) {
                Templates.appendNodeContents(flow, html, js);
                flow.scrollTop = flow.scrollHeight;
            })
            .catch(Notification.exception);
    }

    /**
     * Query conversation history and load into flow.
     */
    function loadHistory() {
        var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
        if (!flow) {
            return;
        }

        // Show a temporary spinner or indicator if wanted
        Ajax.call([{
            methodname: 'mod_tarsuszero_get_conversation_history',
            args: { cmid: state.cmid }
        }])[0].then(function(history) {
            flow.innerHTML = ''; // clear initial placeholder info if any on success
            if (history.length === 0) {
                // Add a welcome message if no history exists yet.
                Str.get_string('chat_welcome', 'mod_tarsuszero').then(function(welcome) {
                    appendMessage('brain', welcome);
                });
                return;
            }

            history.forEach(function(msg) {
                // Informational assistant turns (e.g. the welcome widget)
                // are stored with an empty query: no learner bubble then.
                if (msg.query !== '') {
                    appendMessage('learner', msg.query);
                }
                appendMessage('brain', msg.answer, msg.sources);
            });
        }).catch(Notification.exception);
    }

    /**
     * Perform chat form submission event.
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

        // Lock form inputs.
        input.disabled = true;
        submit.disabled = true;
        if (spinner) {
            spinner.classList.remove('d-none');
        }
        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
        }

        // Show learner message immediately.
        appendMessage('learner', query);
        input.value = '';

        Ajax.call([{
            methodname: 'mod_tarsuszero_send_chat_message',
            args: { cmid: state.cmid, query: query }
        }])[0].then(function(result) {
            // Unlock inputs.
            input.disabled = false;
            submit.disabled = false;
            if (spinner) {
                spinner.classList.add('d-none');
            }
            input.focus();

            if (result.status === 'success') {
                // Informational widgets (e.g. the welcome) come first, then
                // the answer itself — the order Tarsus sends them in.
                (result.messages || []).forEach(function(message) {
                    appendMessage('brain', message);
                });
                appendMessage('brain', result.answer, result.sources);
            } else {
                // Return query to input.
                input.value = query;
                if (alertBox) {
                    alertBox.classList.remove('d-none');
                    alertBox.textContent = result.error || 'Unknown error occurred.';
                }
            }
        }).catch(function(ex) {
            // Re-unlock inputs in case of general exception.
            input.disabled = false;
            submit.disabled = false;
            if (spinner) {
                spinner.classList.add('d-none');
            }
            input.value = query;
            Notification.exception(ex);
        });
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

        var form = state.container.querySelector(SELECTORS.FORM);
        if (form) {
            form.addEventListener('submit', handleSubmit);
        }

        loadHistory();
    }

    return {
        init: init
    };
});