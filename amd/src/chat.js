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
define(['core/ajax', 'core/templates', 'core/notification', 'core/str', 'core_filters/events', 'mod_tarsuszero/processing'],
        function(Ajax, Templates, Notification, Str, FilterEvents, Processing) {

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
        PRACTICE_NEXT: '.tarsus-practice-next',
        PRACTICE_NEXT_SPINNER: '.tarsus-practice-next__spinner',
        PRACTICE_NEXT_ICON: '.tarsus-practice-next__icon',
        PRACTICE_LABEL: '[data-region="practice-label"]',
        PRACTICE_CARD: '[data-region="practice-card"]',
        PRACTICE_CHECK: '.tarsus-practice-check',
        PRACTICE_CHECK_SPINNER: '.tarsus-practice-check__spinner',
        PRACTICE_CHOICE: '.tarsus-practice-choice',
        PRACTICE_INPUT: '.tarsus-practice-card__input',
        PRACTICE_VARIABLE: '.tarsus-practice-variable',
        PRACTICE_VARIABLE_INPUT: '.tarsus-practice-variable__input',
        PRACTICE_TABLE_ROW: '.tarsus-practice-table__row',
        PRACTICE_TABLE_INPUT: '.tarsus-practice-table__input',
        PRACTICE_FEEDBACK: '[data-region="feedback"]',
        PRACTICE_SCORE: '[data-region="score"]',
        PRACTICE_SCORE_FILL: '[data-region="score-fill"]',
        PRACTICE_SCORE_VALUE: '[data-region="score-value"]',
        XP_BADGE: '[data-region="xp-badge"]',
        XP_EARNED: '[data-region="xp-earned"]',
        XP_TOTAL: '[data-region="xp-total"]',
        XP_UNIT: '[data-region="xp-unit"]',
        PROGRESS: '[data-region="progress"]',
        PROGRESS_FILL: '[data-region="progress-fill"]',
        FEED_OLDER: '[data-region="feed-older"]',
        LEARN_CARD: '[data-region="learn-card"]',
        LEARN_SLIDE: '[data-region="slide"]',
        LEARN_NAV: '.tarsus-learn-nav',
        LEARN_RING: '[data-region="ring"]',
        LEARN_BADGE: '[data-region="slide-badge"]',
        JUMP_TOP: '[data-action="jump-top"]',
        ENLARGE: '[data-action="enlarge"]',
        AUDIO_TOGGLE: '[data-action="audio-toggle"]',
        AUDIO_PLAYER: '[data-region="audio-player"]',
        AUDIO: '[data-region="audio"]',
        ASK_ABOUT: '[data-action="ask-about"]',
        REPLY_CHIP: '[data-region="reply-chip"]',
        REPLY_TITLE: '[data-region="reply-title"]',
        REPLY_CLEAR: '[data-action="reply-clear"]'
    };

    /** @var {number} The feed page size, matching the external function. */
    var FEED_PAGE_SIZE = 10;

    /** @var {number} Scroll distance from the top that triggers loading an older page. */
    var FEED_SCROLL_THRESHOLD = 80;

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
        // The modes the teacher switched on (ask, test, build).
        modes: [],
        // "Ask about this": {id, title} of the slide the next question is
        // about, or null.
        replyTo: null,
        practiceStarted: false,
        learnStarted: false,
        // The brain sent its farewell (success card): nothing more to
        // build, so Next locks in Build knowledge. The learnProgress figure
        // alone does not count — it reaches 100 before the course ends.
        learnComplete: false,
        feedOffset: 0,
        feedHasMore: false,
        // Slides of every learning stack on the page, by stack id: the
        // brain pages by slide and may split one stack across two pages,
        // so the older half has to merge into the card already shown.
        stacks: {},
        // True while a scroll was started by the script (jump to top, scroll
        // to the newest card): such moves must not trigger lazy loading.
        programmaticScroll: false,
        feedLoading: false,
        progress: null
    };

    /**
     * Let Moodle's text filters (MathJax above all) process freshly inserted
     * content: the brain writes formulas in TeX, which the filter only
     * renders for nodes it is told about.
     *
     * @param {Element|null} node The inserted node
     */
    function notifyFilters(node) {
        if (node) {
            FilterEvents.notifyFilterContentUpdated([node]);
        }
    }

    /**
     * Freeze an answered practice card: mark the pick, show the feedback.
     *
     * Shared by fresh evaluations and by cards restored from the brain.
     *
     * @param {Element} card The practice card element.
     * @param {Object} verdict {correct: bool, feedback: string (HTML), useranswer: string,
     *                          useranswers: [{variable, answer}], answers: [{variable, answer, iscorrect}]}
     */
    function finalizePracticeCard(card, verdict) {
        card.classList.add('tarsus-practice-card--answered');

        // Rule of three: restore the learner's figures and mark the wrong
        // quantities. Correct ones stay plain and no model answers are
        // listed — the feedback text already explains the solution.
        var verdictByVariable = {};
        (verdict.answers || []).forEach(function(entry) {
            verdictByVariable[entry.variable] = entry;
        });
        var givenByVariable = {};
        (verdict.useranswers || []).forEach(function(entry) {
            givenByVariable[entry.variable] = entry.answer;
        });
        card.querySelectorAll(SELECTORS.PRACTICE_VARIABLE_INPUT).forEach(function(input) {
            var name = input.getAttribute('data-variable');
            if (input.value === '' && givenByVariable[name] !== undefined) {
                input.value = givenByVariable[name];
            }
            input.disabled = true;
            var row = input.closest(SELECTORS.PRACTICE_VARIABLE);
            if (row && verdictByVariable[name] && !verdictByVariable[name].iscorrect) {
                row.classList.add('tarsus-practice-variable--incorrect');
            }
        });
        // Spreadsheet calculation: restore the learner's cells and mark the
        // wrong ones. Correct cells stay plain and no model figure is shown
        // — the feedback text already explains the solution.
        var userRows = {};
        (verdict.usertable || []).forEach(function(row) {
            userRows[row.position] = row;
        });
        var modelRows = {};
        (verdict.answertable || []).forEach(function(row) {
            modelRows[row.position] = row;
        });
        card.querySelectorAll(SELECTORS.PRACTICE_TABLE_ROW).forEach(function(row) {
            var position = row.getAttribute('data-position');
            row.querySelectorAll(SELECTORS.PRACTICE_TABLE_INPUT).forEach(function(input) {
                var cell = input.getAttribute('data-cell');
                if (input.value === '' && userRows[position]) {
                    input.value = userRows[position][cell] || '';
                }
                input.disabled = true;
                var model = modelRows[position];
                if (!model) {
                    return;
                }
                var mark = cell === 'rate' ? model.rateverdict : model.amountverdict;
                if (mark < 0) {
                    input.parentNode.classList.add('tarsus-practice-table__cell--incorrect');
                }
            });
        });


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

        // Partial credit (a score strictly between 0 and 100 — open
        // questions, rule of three, Tabellenkalkulation) shows as a slim
        // score bar above the feedback, which then reads amber rather than
        // green or red.
        var score = typeof verdict.score === 'number' ? verdict.score : -1;
        var partial = score > 0 && score < 100;
        var scoreBox = card.querySelector(SELECTORS.PRACTICE_SCORE);
        if (scoreBox && partial) {
            var rounded = Math.round(score);
            scoreBox.querySelector(SELECTORS.PRACTICE_SCORE_FILL).style.width = rounded + '%';
            scoreBox.querySelector(SELECTORS.PRACTICE_SCORE_VALUE).textContent = rounded + ' %';
            scoreBox.setAttribute('aria-valuenow', rounded);
            scoreBox.classList.remove('d-none');
        }

        var feedback = card.querySelector(SELECTORS.PRACTICE_FEEDBACK);
        if (feedback && verdict.feedback) {
            feedback.classList.remove('d-none');
            if (partial) {
                feedback.classList.add('tarsus-practice-card__feedback--partial');
            } else {
                feedback.classList.add(verdict.correct
                    ? 'tarsus-practice-card__feedback--correct'
                    : 'tarsus-practice-card__feedback--incorrect');
            }
            // Server-cleaned HTML from the external function.
            feedback.innerHTML = verdict.feedback;
            notifyFilters(feedback);
        }
    }

    /**
     * The template context of one practice question card.
     *
     * @param {Object} widget {id, type, question, choices, taxonomy, difficultylevel, ...}
     * @return {Object} The template context.
     */
    function practiceCardContext(widget) {
        // The difficulty renders as three bars, filled up to the grade and
        // coloured by it (green, orange, red), as in the Tarsus interface;
        // the label stays as the tooltip.
        var level = widget.difficultylevel || 0;
        var bars = level > 0 ? [1, 2, 3].map(function(bar) {
            return {filled: bar <= level};
        }) : [];

        var variables = (widget.variables || []).map(function(name) {
            return {name: name};
        });

        return {
            widgetid: widget.id,
            widgettype: widget.type,
            question: widget.question,
            haschoices: widget.choices.length > 0,
            choices: widget.choices,
            hasvariables: variables.length > 0,
            variables: variables,
            hastable: (widget.table || []).length > 0,
            table: widget.table || [],
            columnsjson: JSON.stringify(widget.columns || []),
            taxonomy: widget.taxonomy,
            difficulty: widget.difficulty,
            bars: bars,
            hasbars: bars.length > 0,
            difficultyclass: level > 0 ? 'tarsus-difficulty--' + level : '',
            hasmeta: Boolean(widget.taxonomy || bars.length)
        };
    }

    /**
     * Attach one practice question card to the flow.
     *
     * @param {Object} widget {id, type, question, choices, taxonomy, difficulty}
     * @return {Promise} Resolves to the card element.
     */
    function attachPracticeCard(widget) {
        return attachCard('mod_tarsuszero/practice_card', practiceCardContext(widget), null, true);
    }

    /**
     * Scroll the page so the end of the feed shows above the composer.
     *
     * The feed is part of the page, not an inner scroll box: the composer
     * sticks to the bottom of the viewport and would otherwise cover the
     * newest card.
     */
    function scrollToEnd() {
        var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
        var dock = state.container.querySelector(SELECTORS.FORM);
        if (!flow) {
            return;
        }
        var bottom = flow.getBoundingClientRect().bottom + window.pageYOffset;
        var visible = window.innerHeight - (dock ? dock.offsetHeight : 0) - footerOffset();
        scrollPage(Math.max(0, bottom - visible));
    }

    /**
     * Height of the site's own fixed footer, if any — Moodle 5.3's linear
     * navigation (Previous/Next activity) lives there — so the composer
     * can sit right above it instead of under it.
     *
     * @return {number} Pixels
     */
    function footerOffset() {
        // Boost marks the body while the footer is actually shown (it
        // slides away on narrow screens while scrolling down).
        var footer = document.querySelector('.stickyfooter');
        return footer && document.body.classList.contains('hasstickyfooter') ? footer.offsetHeight : 0;
    }

    /**
     * Measure the fixed navbar into a CSS variable: the progress bar and
     * the back-to-top button sit right under it. (The sticky footer's
     * height comes from Boost's own --sticky-footer-total-height.)
     */
    function measureChrome() {
        var navbar = document.querySelector('.navbar.fixed-top');
        state.container.style.setProperty('--tarsus-nav-offset', (navbar ? navbar.offsetHeight : 0) + 'px');
    }

    /**
     * Run a change that adds content above the viewport, keeping what the
     * reader sees where it is.
     *
     * @param {Function} change Inserts the content
     */
    function keepViewport(change) {
        var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
        // Content only pushes the reader when it lands above the viewport;
        // a reader looking at the teacher's table above the feed stays put.
        var above = flow ? flow.getBoundingClientRect().top < 0 : true;
        var grewBy = document.documentElement.scrollHeight;
        change();
        if (above) {
            window.scrollBy(0, document.documentElement.scrollHeight - grewBy);
        }
    }

    /**
     * Scroll the page by script, keeping the lazy loader quiet meanwhile.
     *
     * @param {number} top Target scroll position
     */
    function scrollPage(top) {
        state.programmaticScroll = true;
        window.scrollTo(0, top);
        window.setTimeout(function() {
            state.programmaticScroll = false;
            toggleJumpTop();
        }, 150);
    }

    /**
     * Show the back-to-top button once the reader has scrolled past the
     * top of the activity box.
     */
    function toggleJumpTop() {
        var button = state.container.querySelector(SELECTORS.JUMP_TOP);
        if (!button) {
            return;
        }
        var offset = parseInt(getComputedStyle(state.container).getPropertyValue('--tarsus-nav-offset'), 10) || 0;
        button.classList.toggle('d-none', state.container.getBoundingClientRect().top >= offset);
    }

    /**
     * Whether the reader is close enough to the top of the feed to want the
     * page before it.
     *
     * @return {boolean}
     */
    function nearFeedTop() {
        var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
        if (!flow) {
            return false;
        }
        var offset = parseInt(getComputedStyle(state.container).getPropertyValue('--tarsus-nav-offset'), 10) || 0;
        return flow.getBoundingClientRect().top - offset > -FEED_SCROLL_THRESHOLD;
    }

    /**
     * Render one card's markup without attaching it to the flow.
     *
     * @param {string} template Template name
     * @param {Object} context Template context
     * @return {Promise} Resolves to {card: Element, js: string}
     */
    function buildCard(template, context) {
        return Templates.render(template, context).then(function(html, js) {
            var holder = document.createElement('div');
            holder.innerHTML = html;
            return {card: holder.firstElementChild, js: js};
        });
    }

    /**
     * Build the cards of one feed page, in order.
     *
     * @param {Array} items The feed items
     * @return {Promise} Resolves to [{card, js, item}] in item order
     */
    function buildFeedCards(items) {
        return Promise.all(items.map(function(item) {
            var build;
            if (item.kind === 'message') {
                build = buildCard('mod_tarsuszero/chat_welcome_card', {title: item.title, message: item.text});
            } else if (item.kind === 'exchange') {
                build = buildCard('mod_tarsuszero/chat_card', {
                    question: item.query,
                    answer: item.answer,
                    pending: false,
                    useravatarurl: state.userAvatar,
                    robotavatarurl: state.robotAvatar,
                    robotname: state.robotName
                });
            } else if (['objectives', 'stack', 'complete', 'motivation'].indexOf(item.kind) !== -1) {
                // A stack yields its slide card and one card per check
                // question; a question already on the page (the newer
                // half of a straddling stack) is not built twice.
                var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
                build = Promise.all(learnCards(item).filter(function(card) {
                    var id = card.context.widgetid;
                    return !(id && flow && flow.querySelector('[data-widget-id="' + id + '"]'));
                }).map(function(card) {
                    return buildCard(card.template, card.context);
                }));
            } else {
                build = buildCard('mod_tarsuszero/practice_card', practiceCardContext(item));
            }
            return build.then(function(entries) {
                return (Array.isArray(entries) ? entries : [entries]).map(function(entry, index) {
                    entry.item = item;
                    // Only the stack card itself replaces the earlier half.
                    entry.replaces = index === 0 && entry.card.matches(SELECTORS.LEARN_CARD) ? (item.replaces || null) : null;
                    return entry;
                });
            });
        })).then(function(groups) {
            return groups.reduce(function(all, group) {
                return all.concat(group);
            }, []);
        });
    }

    /**
     * Settle a built card after it is in the flow: verdicts and template JS.
     *
     * @param {Object} entry {card, js, item}
     */
    function settleFeedCard(entry) {
        var item = entry.item;
        if (item.kind === 'practice' && item.answered) {
            finalizePracticeCard(entry.card, storedVerdict(item));
        }
        if (item.kind === 'stack' && entry.card.matches(SELECTORS.PRACTICE_CARD)) {
            // A check question already answered freezes with its verdict.
            var id = entry.card.getAttribute('data-widget-id');
            (item.slides || []).forEach(function(slide) {
                if (slide.kind === 'question' && slide.question && slide.question.answered && slide.question.id === id) {
                    finalizePracticeCard(entry.card, storedVerdict(slide.question));
                }
            });
        }
        if (entry.js) {
            Templates.runTemplateJS(entry.js);
        }
        notifyFilters(entry.card);
    }

    /**
     * The verdict a stored, answered question carries, in the shape
     * finalizePracticeCard() expects.
     *
     * @param {Object} item A 'practice' item or a question slide's question
     * @return {Object}
     */
    function storedVerdict(item) {
        return {
            correct: item.correct,
            score: item.score,
            feedback: item.feedback,
            useranswer: item.useranswer,
            useranswers: item.useranswers || [],
            answers: item.answers || [],
            usertable: item.usertable || [],
            answertable: item.answertable || []
        };
    }

    /**
     * Switch the practice button to "Next" once history holds practice.
     *
     * @param {Array} items The feed items just loaded
     * @return {Promise} Resolves when the label is settled
     */
    function notePracticeHistory(items) {
        items.forEach(function(item) {
            if (item.kind === 'practice') {
                state.practiceStarted = true;
            } else if (item.kind === 'objectives' || item.kind === 'stack' || item.kind === 'motivation') {
                state.learnStarted = true;
            } else if (item.kind === 'complete') {
                state.learnStarted = true;
                state.learnComplete = true;
            }
        });
        return updateNextLabel();
    }

    /**
     * Reopen the newest page of the activity feed from the brain, on page load.
     *
     * One interleaved stream across the interaction modes, as in the Tarsus
     * interface: chat cards and practice cards render in their original
     * order and stay visible whatever mode is active. Answered questions
     * return frozen with their verdicts, unanswered ones stay answerable;
     * the experience figures come along in the same call. Older pages
     * lazy-load as the learner scrolls up. Nothing is stored locally — the
     * brain is the only store.
     */
    function loadFeed() {
        var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
        if (!flow) {
            return;
        }
        var older = flow.querySelector(SELECTORS.FEED_OLDER);

        Ajax.call([{
            methodname: 'mod_tarsuszero_get_activity_feed',
            args: {cmid: state.cmid, offset: 0}
        }])[0].then(function(result) {
            if (result.status !== 'success') {
                // A quiet miss: the learner can still interact; the feed
                // just starts visually empty.
                flow.innerHTML = '';
                return null;
            }
            updateXp(result.progress);
            state.feedOffset = FEED_PAGE_SIZE;
            state.feedHasMore = Boolean(result.hasmore);

            // Every card renders in parallel and the finished page attaches
            // in one pass, already scrolled to its newest card — rendering
            // one by one would visibly flick through the whole history.
            // The loading spinner stays up until the page is ready.
            return buildFeedCards(result.items).then(function(built) {
                flow.innerHTML = '';
                if (older) {
                    flow.appendChild(older);
                }
                built.forEach(function(entry) {
                    if (!entry.card) {
                        return;
                    }
                    flow.appendChild(entry.card);
                    settleFeedCard(entry);
                });
                scrollToEnd();
                return notePracticeHistory(result.items).then(function() {
                    // A short history may leave the top in view: fetch on.
                    if (nearFeedTop()) {
                        loadOlderFeed();
                    }
                    return null;
                });
            });
        }).catch(Notification.exception);
    }

    /**
     * Join the older half of a learning stack that straddles two pages.
     *
     * When the older page ends with a stack whose id is already on the
     * page (its newer slides came first), the two halves become one item,
     * older slides first, flagged to replace the card already shown.
     *
     * @param {Element} flow The chat flow
     * @param {Array} items The older page's items, oldest first
     * @return {Array} The items to build
     */
    function mergeStraddlingStack(flow, items) {
        var last = items[items.length - 1];
        if (!last || last.kind !== 'stack' || !state.stacks[last.id]) {
            return items;
        }
        var shown = flow.querySelector(SELECTORS.LEARN_CARD + '[data-stack-id="' + last.id + '"]');
        if (!shown) {
            return items;
        }
        var merged = Object.assign({}, last, {
            slides: (last.slides || []).concat(state.stacks[last.id]),
            replaces: shown
        });
        return items.slice(0, -1).concat([merged]);
    }

    /**
     * Load the page before the oldest one shown and slot it in above,
     * keeping the reader's viewport where it is.
     */
    function loadOlderFeed() {
        var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
        if (!flow || state.feedLoading || !state.feedHasMore) {
            return;
        }
        state.feedLoading = true;
        var older = flow.querySelector(SELECTORS.FEED_OLDER);
        if (older) {
            older.classList.remove('d-none');
        }
        var offset = state.feedOffset;

        Ajax.call([{
            methodname: 'mod_tarsuszero_get_activity_feed',
            args: {cmid: state.cmid, offset: offset}
        }])[0].then(function(result) {
            if (result.status !== 'success') {
                state.feedHasMore = false;
                return null;
            }
            state.feedOffset = offset + FEED_PAGE_SIZE;
            state.feedHasMore = Boolean(result.hasmore) && result.items.length > 0;

            var items = mergeStraddlingStack(flow, result.items);
            return buildFeedCards(items).then(function(built) {
                var anchor = older && older.nextSibling ? older.nextSibling : flow.firstChild;
                keepViewport(function() {
                    built.forEach(function(entry) {
                        if (!entry.card) {
                            return;
                        }
                        if (entry.replaces) {
                            flow.replaceChild(entry.card, entry.replaces);
                        } else {
                            flow.insertBefore(entry.card, anchor);
                        }
                        settleFeedCard(entry);
                    });
                });
                return notePracticeHistory(result.items);
            });
        }).catch(function(ex) {
            state.feedHasMore = false;
            Notification.exception(ex);
        }).then(function() {
            state.feedLoading = false;
            if (older) {
                older.classList.add('d-none');
            }
            return null;
        });
    }

    /**
     * Show the experience figures once the practice mode reports them.
     *
     * @param {Object|undefined} progress {earnedxp, totalxp, practiceprogress}
     */
    function updateXp(progress) {
        if (!progress) {
            return;
        }
        state.progress = progress;
        renderBadge();
        renderProgressBar();
    }

    /**
     * Show the tally of the active mode beside the switcher: experience
     * points for Test knowledge, learning units for Build knowledge — both
     * straight from the brain's activity figures. The chat has no tally,
     * and a total still at 0 hides the badge too.
     */
    function renderBadge() {
        var badge = state.container.querySelector(SELECTORS.XP_BADGE);
        if (!badge) {
            return;
        }
        var progress = state.progress;
        var build = state.mode === 'build';
        var earned = progress ? (build ? progress.earnedunits : progress.earnedxp) : 0;
        var total = progress ? (build ? progress.totalunits : progress.totalxp) : 0;
        if (state.mode === 'ask' || !(total > 0)) {
            badge.classList.add('d-none');
            return;
        }
        badge.classList.remove('d-none');
        badge.querySelector(SELECTORS.XP_EARNED).textContent = Math.round(earned * 100) / 100;
        badge.querySelector(SELECTORS.XP_TOTAL).textContent = Math.round(total * 100) / 100;
        var unit = badge.querySelector(SELECTORS.XP_UNIT);
        if (unit) {
            Str.get_string(build ? 'learning_units' : 'xp', 'mod_tarsuszero').then(function(text) {
                unit.textContent = text;
                return null;
            }).catch(Notification.exception);
        }
    }

    /**
     * Fill the bar across the top of the chat with the figure of the active
     * mode: the practice progress for Test knowledge, the learn progress
     * for Build knowledge. The chat has no progress of its own, so the bar
     * stays hidden in Ask a question.
     */
    function renderProgressBar() {
        var bar = state.container.querySelector(SELECTORS.PROGRESS);
        var fill = state.container.querySelector(SELECTORS.PROGRESS_FILL);
        if (!bar || !fill) {
            return;
        }
        if (state.mode === 'ask' || !state.progress) {
            bar.classList.add('d-none');
            return;
        }
        var figure = state.mode === 'build' ? state.progress.learnprogress : state.progress.practiceprogress;
        if (typeof figure !== 'number') {
            bar.classList.add('d-none');
            return;
        }
        var percent = Math.max(0, Math.min(100, Math.round(figure)));
        fill.style.width = percent + '%';
        bar.setAttribute('aria-valuenow', percent);
        bar.setAttribute('title', percent + ' %');
        bar.classList.remove('d-none');
    }

    /**
     * The template context of one learn item (objectives or stack).
     *
     * @param {Object} item The learn item from the external function
     * @return {Array} [{template, context}], the stack's slide card first, then one card per check question
     */
    function learnCards(item) {
        if (item.kind === 'complete') {
            return [{template: 'mod_tarsuszero/learn_complete_card', context: {title: item.title, text: item.text}}];
        }
        if (item.kind === 'motivation') {
            return [{template: 'mod_tarsuszero/learn_motivation_card', context: {title: item.title, text: item.text}}];
        }
        if (item.kind === 'message') {
            return [{template: 'mod_tarsuszero/chat_welcome_card', context: {title: item.title, message: item.text}}];
        }
        if (item.kind === 'objectives') {
            return [{
                template: 'mod_tarsuszero/learn_objectives_card',
                context: {
                    title: item.title,
                    message: item.message,
                    objectives: (item.objectives || []).map(function(objective, index) {
                        return Object.assign({number: index + 1}, objective);
                    })
                }
            }];
        }
        state.stacks[item.id] = item.slides || [];
        // The unit's check questions leave the stack: each becomes its own
        // card after the slides, with the difficulty and level on its band,
        // and the ring counts the text slides only.
        var slides = [];
        var questions = [];
        (item.slides || []).forEach(function(slide) {
            if (slide.kind === 'question' && slide.question) {
                var question = practiceCardContext(slide.question);
                question.state = 'learn';
                question.learn = true;
                questions.push({template: 'mod_tarsuszero/practice_card', context: question});
                return;
            }
            var index = slides.length;
            slides.push({
                id: slide.id || '',
                index: index,
                number: index + 1,
                first: index === 0,
                taxonomy: slide.taxonomy || '',
                taxonomyicon: slide.taxonomyicon || '',
                title: slide.title,
                text: slide.text,
                image: slide.image,
                imageposition: slide.imageposition === 'left' ? 'left' : 'right'
            });
        });
        var cards = slides.length ? [{
            template: 'mod_tarsuszero/learn_stack_card',
            context: {
                stackid: item.id,
                askenabled: state.modes.indexOf('ask') !== -1,
                total: slides.length,
                single: slides.length < 2,
                ringoffset: ringOffset(1, slides.length),
                slides: slides
            }
        }] : [];
        return cards.concat(questions);
    }

    /**
     * The stroke offset that fills the progress ring up to a slide.
     *
     * @param {number} shown Slides seen so far, including the current one
     * @param {number} total Slides in the stack
     * @return {number} Offset along the ring's circumference (r=9)
     */
    function ringOffset(shown, total) {
        var circumference = 2 * Math.PI * 9;
        var fraction = total > 0 ? shown / total : 1;
        return Math.round(circumference * (1 - fraction) * 100) / 100;
    }

    /**
     * Open a slide illustration in a lightbox: a native <dialog> on the
     * body, closed by a click anywhere, the close button or Escape.
     *
     * @param {string} src The image source (a data URI)
     * @return {Promise} Resolves once the dialog is open
     */
    function openLightbox(src) {
        var dialog = document.querySelector('.tarsus-lightbox');
        var ready = dialog ? Promise.resolve(dialog) : Str.get_string('learn_image_close', 'mod_tarsuszero')
            .then(function(closelabel) {
                dialog = document.createElement('dialog');
                dialog.className = 'tarsus-lightbox';
                var close = document.createElement('button');
                close.type = 'button';
                close.className = 'tarsus-lightbox__close';
                close.setAttribute('aria-label', closelabel);
                close.textContent = '\u00d7';
                var image = document.createElement('img');
                image.className = 'tarsus-lightbox__image';
                image.alt = '';
                dialog.appendChild(close);
                dialog.appendChild(image);
                dialog.addEventListener('click', function() {
                    dialog.close();
                });
                document.body.appendChild(dialog);
                return dialog;
            });
        return ready.then(function(box) {
            box.querySelector('.tarsus-lightbox__image').src = src;
            if (!box.open) {
                box.showModal();
            }
            return null;
        });
    }

    /**
     * Set the narration control's state and its labels.
     *
     * @param {Element} card The learn card
     * @param {string} stateName idle, loading, ready or playing
     */
    function setAudioState(card, stateName) {
        var player = card.querySelector(SELECTORS.AUDIO_PLAYER);
        if (!player) {
            return;
        }
        player.setAttribute('data-state', stateName);
        player.querySelector('[data-region="audio-speaker"]').classList.toggle('d-none', stateName !== 'idle');
        player.querySelector('[data-region="audio-play"]').classList.toggle('d-none',
            stateName === 'idle' || stateName === 'playing');
        player.querySelector('[data-region="audio-pause"]').classList.toggle('d-none', stateName !== 'playing');
        var key = stateName === 'playing' ? 'learn_audio_pause' : (stateName === 'idle' ? 'learn_audio' : 'learn_audio_play');
        var toggle = player.querySelector(SELECTORS.AUDIO_TOGGLE);
        Str.get_string(key, 'mod_tarsuszero').then(function(text) {
            toggle.setAttribute('title', text);
            toggle.setAttribute('aria-label', text);
            return null;
        }).catch(Notification.exception);
    }

    /**
     * Draw the fake waveform: bars of pseudo-random height, seeded by the
     * stack id so the same unit always looks the same.
     *
     * @param {Element} card The learn card
     */
    function drawWave(card) {
        var wave = card.querySelector('[data-region="audio-wave"]');
        if (!wave || wave.childElementCount) {
            return;
        }
        var seed = 0;
        String(card.getAttribute('data-stack-id') || '').split('').forEach(function(ch) {
            seed = (seed * 31 + ch.charCodeAt(0)) % 2147483647;
        });
        for (var i = 0; i < 36; i++) {
            seed = (seed * 48271) % 2147483647;
            var bar = document.createElement('span');
            bar.className = 'tarsus-learn-player__bar';
            bar.style.height = (25 + (seed % 75)) + '%';
            wave.appendChild(bar);
        }
        wave.classList.remove('d-none');
    }

    /**
     * Colour the waveform up to the playback position.
     *
     * @param {Element} card The learn card
     * @param {number} ratio 0..1
     */
    function paintWave(card, ratio) {
        var bars = card.querySelectorAll('.tarsus-learn-player__bar');
        var played = Math.round(ratio * bars.length);
        bars.forEach(function(bar, index) {
            bar.classList.toggle('tarsus-learn-player__bar--played', index < played);
        });
    }

    /**
     * Have the brain read a learning unit aloud, then offer playback.
     *
     * The narration is fetched once per card and kept in the page; later
     * clicks only play and pause it.
     *
     * @param {Element} card The learn card
     */
    function fetchStackAudio(card) {
        var audio = card.querySelector(SELECTORS.AUDIO);
        var player = card.querySelector(SELECTORS.AUDIO_PLAYER);
        if (!audio || !player || player.getAttribute('data-state') === 'loading') {
            return;
        }
        setAudioState(card, 'loading');

        Ajax.call([{
            methodname: 'mod_tarsuszero_get_stack_audio',
            args: {cmid: state.cmid, stackid: card.getAttribute('data-stack-id')}
        }])[0].then(function(result) {
            if (result.status !== 'success' || !result.audio) {
                setAudioState(card, 'idle');
                var alertBox = state.container.querySelector(SELECTORS.ALERT);
                if (alertBox) {
                    alertBox.classList.remove('d-none');
                    alertBox.textContent = result.error || 'Unknown error occurred.';
                }
                return null;
            }
            audio.src = result.audio;
            wireStackAudio(card);
            drawWave(card);
            setAudioState(card, 'ready');
            // The click is seconds old by now, so browsers may refuse to
            // start playback on their own (NotAllowedError): then the
            // player simply waits for the play button — no error.
            return audio.play().catch(function() {
                return null;
            });
        }).catch(function(ex) {
            setAudioState(card, 'idle');
            Notification.exception(ex);
        });
    }

    /**
     * Keep the control and the waveform in step with the audio.
     *
     * @param {Element} card The learn card
     */
    function wireStackAudio(card) {
        var audio = card.querySelector(SELECTORS.AUDIO);
        audio.addEventListener('play', function() {
            setAudioState(card, 'playing');
        });
        audio.addEventListener('pause', function() {
            setAudioState(card, 'ready');
        });
        audio.addEventListener('ended', function() {
            setAudioState(card, 'ready');
            paintWave(card, 0);
        });
        audio.addEventListener('timeupdate', function() {
            if (audio.duration > 0) {
                paintWave(card, Math.min(1, audio.currentTime / audio.duration));
            }
        });
    }

    /**
     * The narration control: fetch on first use, then play or pause.
     *
     * @param {Element} card The learn card
     */
    function toggleStackAudio(card) {
        var audio = card.querySelector(SELECTORS.AUDIO);
        if (!audio) {
            return;
        }
        if (!audio.getAttribute('src')) {
            fetchStackAudio(card);
            return;
        }
        if (audio.paused) {
            audio.play().catch(Notification.exception);
        } else {
            audio.pause();
        }
    }

    /**
     * Show the previous or next slide of a learning unit.
     *
     * @param {Element} card The learn card
     * @param {number} dir -1 or 1
     */
    function turnSlide(card, dir) {
        var slides = Array.prototype.slice.call(card.querySelectorAll(SELECTORS.LEARN_SLIDE));
        var current = slides.findIndex(function(slide) {
            return !slide.classList.contains('d-none');
        });
        var target = Math.max(0, Math.min(slides.length - 1, current + dir));
        if (target === current) {
            return;
        }
        slides[current].classList.add('d-none');
        slides[target].classList.remove('d-none');
        card.querySelectorAll(SELECTORS.LEARN_BADGE).forEach(function(badge) {
            badge.classList.toggle('d-none', parseInt(badge.getAttribute('data-index'), 10) !== target);
        });
        var ring = card.querySelector(SELECTORS.LEARN_RING);
        if (ring) {
            ring.querySelector('.tarsus-learn-ring__fill').style.strokeDashoffset = ringOffset(target + 1, slides.length);
            Str.get_string('learn_slide_of', 'mod_tarsuszero', {current: target + 1, total: slides.length})
                .then(function(text) {
                    ring.setAttribute('aria-label', text);
                    return null;
                }).catch(Notification.exception);
        }
        card.querySelectorAll(SELECTORS.LEARN_NAV).forEach(function(button) {
            var step = parseInt(button.getAttribute('data-dir'), 10);
            button.disabled = (step < 0 && target === 0) || (step > 0 && target === slides.length - 1);
        });
    }

    /**
     * Lock the Start/Next button while a step is generated: the spinner
     * takes the paper plane's place and gives it back afterwards.
     *
     * @param {boolean} busy Whether a request is in flight
     */
    function setNextBusy(busy) {
        var button = state.container.querySelector(SELECTORS.PRACTICE_NEXT);
        var spinner = state.container.querySelector(SELECTORS.PRACTICE_NEXT_SPINNER);
        var icon = state.container.querySelector(SELECTORS.PRACTICE_NEXT_ICON);
        if (button) {
            button.disabled = busy || nextLocked();
        }
        if (spinner) {
            spinner.classList.toggle('d-none', !busy);
        }
        if (icon) {
            icon.classList.toggle('d-none', busy);
        }
    }

    /**
     * Fetch and render the next learning unit (Build knowledge).
     */
    function handleLearnNext() {
        var button = state.container.querySelector(SELECTORS.PRACTICE_NEXT);
        var alertBox = state.container.querySelector(SELECTORS.ALERT);
        if (!button || button.disabled) {
            return;
        }
        setNextBusy(true);
        if (alertBox) {
            alertBox.classList.add('d-none');
        }
        var processing = startProcessing('build', !state.learnStarted);
        var unlock = function() {
            processing.stop();
            setNextBusy(false);
        };

        Ajax.call([{
            methodname: 'mod_tarsuszero_get_learn_widget',
            args: {cmid: state.cmid}
        }])[0].then(function(result) {
            unlock();
            if (result.status !== 'success' || !result.items || !result.items.length) {
                if (alertBox) {
                    alertBox.classList.remove('d-none');
                    alertBox.textContent = result.error || 'Unknown error occurred.';
                }
                return null;
            }
            updateXp(result.progress);
            if (result.complete) {
                state.learnComplete = true;
            }
            var firstcard = null;
            return result.items.reduce(function(previous, item) {
                return learnCards(item).reduce(function(chain, card) {
                    return chain.then(function() {
                        return attachCard(card.template, card.context, null, true).then(function(element) {
                            firstcard = firstcard || element;
                            return element;
                        });
                    });
                }, previous);
            }, Promise.resolve()).then(function() {
                scrollToCard(firstcard);
                state.learnStarted = true;
                return updateNextLabel();
            });
        }).catch(function(ex) {
            unlock();
            Notification.exception(ex);
        });
    }

    /**
     * Whether the Start/Next button has nothing left to fetch: Build
     * knowledge once the course is complete.
     *
     * @return {boolean}
     */
    function nextLocked() {
        return state.mode === 'build' && state.learnComplete;
    }

    /**
     * Label the Start/Next button for the active mode and lock it once
     * there is nothing more to build.
     *
     * @return {Promise} Resolves when the label is settled
     */
    function updateNextLabel() {
        var button = state.container.querySelector(SELECTORS.PRACTICE_NEXT);
        if (button && !button.querySelector(SELECTORS.PRACTICE_NEXT_SPINNER + ':not(.d-none)')) {
            button.disabled = nextLocked();
        }
        var label = state.container.querySelector(SELECTORS.PRACTICE_LABEL);
        if (!label) {
            return Promise.resolve();
        }
        var started = state.mode === 'build' ? state.learnStarted : state.practiceStarted;
        return Str.get_string(started ? 'practice_next' : 'practice_start', 'mod_tarsuszero').then(function(text) {
            label.textContent = text;
            return null;
        });
    }

    /**
     * Fetch and render the next practice question — or learning unit in
     * Build knowledge.
     */
    function handlePracticeNext() {
        if (state.mode === 'build') {
            handleLearnNext();
            return;
        }
        var button = state.container.querySelector(SELECTORS.PRACTICE_NEXT);
        var alertBox = state.container.querySelector(SELECTORS.ALERT);
        if (!button || button.disabled) {
            return;
        }

        setNextBusy(true);
        if (alertBox) {
            alertBox.classList.add('d-none');
        }

        var processing = startProcessing('test', !state.practiceStarted);
        var unlock = function() {
            processing.stop();
            setNextBusy(false);
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
            var firstcard = null;
            return result.widgets.reduce(function(previous, widget) {
                return previous.then(function() {
                    return attachPracticeCard(widget).then(function(element) {
                        firstcard = firstcard || element;
                        return element;
                    });
                });
            }, Promise.resolve()).then(function() {
                scrollToCard(firstcard);
                state.practiceStarted = true;
                return updateNextLabel();
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
        var answers = [];
        var table = [];
        var columns = [];
        try {
            columns = JSON.parse(card.getAttribute('data-columns') || '[]');
        } catch (e) {
            columns = [];
        }
        var chosen = card.querySelector('input[type="radio"]:checked');
        var textinput = card.querySelector(SELECTORS.PRACTICE_INPUT);
        var variableInputs = card.querySelectorAll(SELECTORS.PRACTICE_VARIABLE_INPUT);
        var tableRows = card.querySelectorAll(SELECTORS.PRACTICE_TABLE_ROW);
        if (tableRows.length) {
            // Spreadsheet calculation: the whole table travels, given cells
            // echoed and every asked cell filled.
            var complete = true;
            tableRows.forEach(function(row) {
                var cells = {position: row.getAttribute('data-position')};
                ['rate', 'amount'].forEach(function(cell) {
                    var input = row.querySelector('input[data-cell="' + cell + '"]');
                    var given = row.querySelector('span[data-cell="' + cell + '"]');
                    var value = input ? input.value.trim() : (given ? given.textContent : '');
                    if (input && value === '') {
                        complete = false;
                    }
                    cells[cell] = (value || '').trim();
                });
                table.push(cells);
            });
            if (!complete) {
                return;
            }
        } else if (variableInputs.length) {
            // Rule of three: every asked quantity needs a figure.
            variableInputs.forEach(function(input) {
                if (input.value.trim() !== '') {
                    answers.push({variable: input.getAttribute('data-variable'), answer: input.value.trim()});
                }
            });
            if (answers.length !== variableInputs.length) {
                return;
            }
        } else if (chosen) {
            answer = chosen.value;
        } else if (textinput) {
            answer = textinput.value.trim();
        }
        if (answer === '' && !answers.length && !table.length) {
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
                answer: answer,
                answers: answers,
                table: table,
                columns: columns,
                // A learning unit's check question is judged in learn state,
                // which earns learning units rather than experience points.
                state: card.getAttribute('data-state') || 'practice'
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
                score: result.score,
                feedback: result.feedback || result.answer || '',
                useranswer: answer,
                useranswers: answers,
                answers: result.answers || [],
                usertable: result.usertable || table,
                answertable: result.answertable || []
            });

            updateXp(result.progress);
            scrollToEnd();
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
     * The chat input and the send button belong to 'ask' alone — the
     * practice and learn modes interact through cards in the flow, so the
     * composer collapses to the switcher row plus the Start/Next button.
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
        var test = mode === 'test' || mode === 'build';
        var input = state.container.querySelector(SELECTORS.INPUT);
        var submit = state.container.querySelector(SELECTORS.SUBMIT);
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
        var chip = state.container.querySelector(SELECTORS.REPLY_CHIP);
        if (chip) {
            chip.classList.toggle('d-none', !ask || !state.replyTo);
        }
        renderBadge();
        renderProgressBar();
        updateNextLabel();
    }

    /**
     * Render a card template and attach it to the flow.
     *
     * @param {string} template Template name
     * @param {Object} context Template context
     * @param {Element|null} before Insert before this element (append when null)
     * @param {boolean} [noscroll] Append without scrolling (the caller positions the view)
     * @return {Promise} Resolves to the attached card element (or null)
     */
    function attachCard(template, context, before, noscroll) {
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
                keepViewport(function() {
                    flow.insertBefore(card, before);
                });
            } else {
                flow.appendChild(card);
                if (!noscroll) {
                    scrollToEnd();
                }
            }
            if (js) {
                Templates.runTemplateJS(js);
            }
            notifyFilters(card);
            return card;
        });
    }

    /**
     * Show the processing indicator under the feed for a request in flight.
     *
     * @param {string} mode 'ask', 'test' or 'build'
     * @param {boolean} firstRun Whether this is the learner's first step in the mode
     * @return {Object} {stop} — stop() removes the indicator; safe to call before it rendered
     */
    function startProcessing(mode, firstRun) {
        var flow = state.container.querySelector(SELECTORS.CHAT_FLOW);
        var handle = null;
        var stopped = false;
        if (flow) {
            Processing.start(flow, mode, firstRun).then(function(result) {
                handle = result;
                if (stopped) {
                    handle.stop();
                } else {
                    scrollToEnd();
                }
                return null;
            }).catch(Notification.exception);
        }
        return {
            stop: function() {
                stopped = true;
                if (handle) {
                    handle.stop();
                }
            }
        };
    }

    /**
     * Scroll the page so a card's top sits just under the pinned chrome.
     *
     * A step may bring several cards at once (two questions, the
     * objectives and a stack): the reader starts at the first of them and
     * reads down, instead of landing at the end and missing the top one.
     *
     * @param {Element|null} card The card to show
     */
    function scrollToCard(card) {
        if (!card) {
            return;
        }
        var offset = parseInt(getComputedStyle(state.container).getPropertyValue('--tarsus-nav-offset'), 10) || 0;
        var progress = state.container.querySelector(SELECTORS.PROGRESS);
        if (progress && !progress.classList.contains('d-none')) {
            offset += progress.offsetHeight;
        }
        scrollPage(Math.max(0, card.getBoundingClientRect().top + window.pageYOffset - offset - 16));
    }

    /**
     * Attach an informational card (e.g. the assistant's welcome).
     *
     * @param {Object} message {title, text}: the widget's title and server-cleaned HTML
     * @param {Element|null} before Insert before this element
     * @return {Promise} Resolves to the card element
     */
    function attachWelcome(message, before) {
        return attachCard('mod_tarsuszero/chat_welcome_card', {title: message.title, message: message.text}, before || null);
    }

    /**
     * Attach an exchange card (question and answer united).
     *
     * @param {string} question The learner's question
     * @param {string} answerHtml Server-cleaned answer HTML ('' while pending)
     * @param {boolean} pending Whether the answer is still being generated
     * @param {string} [reply] Title of the slide the question is about ('' for none)
     * @return {Promise} Resolves to the card element
     */
    function attachExchange(question, answerHtml, pending, reply) {
        return attachCard('mod_tarsuszero/chat_card', {
            question: question,
            answer: answerHtml || '',
            pending: !!pending,
            reply: reply || '',
            useravatarurl: state.userAvatar,
            robotavatarurl: state.robotAvatar,
            robotname: state.robotName
        }, null);
    }

    /**
     * "Ask about this": remember which slide the next question is about
     * and show it as a chip above the composer. Null drops the reference.
     *
     * @param {Object|null} reply {id: widget id, title: slide title}
     */
    function setReply(reply) {
        state.replyTo = reply && reply.id ? reply : null;
        var chip = state.container.querySelector(SELECTORS.REPLY_CHIP);
        if (!chip) {
            return;
        }
        var title = chip.querySelector(SELECTORS.REPLY_TITLE);
        if (title) {
            title.textContent = state.replyTo ? state.replyTo.title : '';
        }
        chip.classList.toggle('d-none', !state.replyTo || state.mode !== 'ask');
    }

    /**
     * The learner wants to ask about the slide showing in a learning unit:
     * pin it, switch to the chat and put the cursor in the box.
     *
     * @param {Element} card The stack card
     */
    function askAbout(card) {
        var slide = card.querySelector(SELECTORS.LEARN_SLIDE + ':not(.d-none)');
        if (!slide || !slide.getAttribute('data-slide-id')) {
            return;
        }
        var title = slide.getAttribute('data-slide-title') || '';
        var ready = title !== '' ? Promise.resolve(title) : Str.get_string('learn_slide_of', 'mod_tarsuszero', {
            current: parseInt(slide.getAttribute('data-index'), 10) + 1,
            total: parseInt(card.getAttribute('data-total'), 10) || 1
        });
        ready.then(function(label) {
            setMode('ask');
            setReply({id: slide.getAttribute('data-slide-id'), title: label});
            var input = state.container.querySelector(SELECTORS.INPUT);
            if (input) {
                input.focus();
            }
            return null;
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
        var reply = state.replyTo;

        var processing = null;
        var unlock = function() {
            if (processing) {
                processing.stop();
            }
            input.disabled = false;
            submit.disabled = false;
            if (spinner) {
                spinner.classList.add('d-none');
            }
            input.focus();
        };

        // The question appears at once, its answer slot thinking.
        attachExchange(query, '', true, reply ? reply.title : '').then(function(card) {
            processing = startProcessing('ask', false);
            return Ajax.call([{
                methodname: 'mod_tarsuszero_send_chat_message',
                args: {cmid: state.cmid, query: query, replytowidget: reply ? reply.id : ''}
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
                        notifyFilters(slot);
                    }
                    // The reference is for one question.
                    setReply(null);
                    scrollToEnd();
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

        // The teacher's switches decide which modes exist; open with the
        // first one (Ask a question when it is on).
        var modes = (state.container.getAttribute('data-modes') || '').split(',').filter(Boolean);
        state.modes = modes;
        var initial = modes.indexOf('ask') !== -1 ? 'ask' : (modes[0] || 'ask');
        setMode(initial);

        // Practice and learn cards render later; delegate their buttons.
        state.container.addEventListener('click', function(e) {
            var check = e.target.closest(SELECTORS.PRACTICE_CHECK);
            if (check && state.container.contains(check)) {
                var card = check.closest(SELECTORS.PRACTICE_CARD);
                if (card) {
                    handlePracticeCheck(card);
                }
                return;
            }
            var nav = e.target.closest(SELECTORS.LEARN_NAV);
            if (nav && state.container.contains(nav)) {
                var learncard = nav.closest(SELECTORS.LEARN_CARD);
                if (learncard) {
                    turnSlide(learncard, parseInt(nav.getAttribute('data-dir'), 10));
                }
                return;
            }
            var picture = e.target.closest(SELECTORS.ENLARGE);
            if (picture && state.container.contains(picture)) {
                openLightbox(picture.getAttribute('src')).catch(Notification.exception);
                return;
            }
            var toggle = e.target.closest(SELECTORS.AUDIO_TOGGLE);
            if (toggle && state.container.contains(toggle)) {
                var togglecard = toggle.closest(SELECTORS.LEARN_CARD);
                if (togglecard) {
                    toggleStackAudio(togglecard);
                }
                return;
            }
            var ask = e.target.closest(SELECTORS.ASK_ABOUT);
            if (ask && state.container.contains(ask)) {
                var askcard = ask.closest(SELECTORS.LEARN_CARD);
                if (askcard) {
                    askAbout(askcard);
                }
                return;
            }
            var clear = e.target.closest(SELECTORS.REPLY_CLEAR);
            if (clear && state.container.contains(clear)) {
                setReply(null);
            }
        });
        // Keyboard users reach the illustration by Tab; Enter or Space opens it.
        state.container.addEventListener('keydown', function(e) {
            if ((e.key === 'Enter' || e.key === ' ') && e.target.matches(SELECTORS.ENLARGE)) {
                e.preventDefault();
                openLightbox(e.target.getAttribute('src')).catch(Notification.exception);
            }
        });

        measureChrome();
        window.addEventListener('resize', measureChrome, {passive: true});

        // Scrolling the page near the top of the feed fetches the page
        // before it.
        window.addEventListener('scroll', function() {
            toggleJumpTop();
            if (!state.programmaticScroll && nearFeedTop()) {
                loadOlderFeed();
            }
        }, {passive: true});

        // Back to top: the teacher's index table and the activity menu sit
        // above a feed that may be long and still lazy-loading.
        var jump = state.container.querySelector(SELECTORS.JUMP_TOP);
        if (jump) {
            jump.addEventListener('click', function() {
                scrollPage(0);
            });
        }

        loadFeed();
    }

    return {
        init: init
    };
});
