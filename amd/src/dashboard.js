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
 * Per-resource index actions for the teacher dashboard.
 *
 * Wires the Index / Re-index / Remove / Retry buttons to AJAX calls and polls
 * the brain status while any resource is mid-flight so badges and buttons stay
 * live without a page reload.
 *
 * @module     mod_tarsuszero/dashboard
 * @copyright  2026 lern.link GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';

const SELECTORS = {
    RESOURCE_ROW: '[data-region="tarsus-resource"]',
    ACTION_BUTTON: '[data-action]',
    BULK_BUTTON: '[data-bulk]',
    BULK_SPINNER: '[data-region="tarsus-bulk-spinner"]',
    STATUS_BADGE: '[data-region="status-badge"]',
    STATUS_SPINNER: '[data-region="status-spinner"]',
    INCLUDE_CELL: '[data-region="tarsus-include-cell"]',
    INCLUDE_TOGGLE: '[data-region="tarsus-include-toggle"]',
};

const BADGE_CLASSES = [
    'badge-light', 'badge-warning', 'badge-secondary', 'badge-success', 'badge-danger',
];

const POLL_INTERVAL = 4000;

let cmid = 0;
let pollTimer = null;

/**
 * Apply a presentational payload to a single resource row.
 *
 * @param {Element} row The resource row element.
 * @param {Object} data The presentational payload from the web service.
 */
const updateRow = (row, data) => {
    const badge = row.querySelector(SELECTORS.STATUS_BADGE);
    if (badge) {
        badge.textContent = data.statuslabel;
        BADGE_CLASSES.forEach((cls) => badge.classList.remove(cls));
        badge.classList.add(data.badgeclass);
    }

    const spinner = row.querySelector(SELECTORS.STATUS_SPINNER);
    if (spinner) {
        spinner.hidden = !data.inflight;
    }

    const map = {
        index: data.canindex,
        reindex: data.canreindex,
        remove: data.canremove,
        retry: data.canretry,
    };
    row.querySelectorAll(SELECTORS.ACTION_BUTTON).forEach((button) => {
        const action = button.getAttribute('data-action');
        button.hidden = !map[action];
        button.disabled = false;
    });
};

/**
 * Whether any visible row is still mid-flight.
 *
 * @param {Element} root The dashboard root element.
 * @return {boolean}
 */
const hasInflight = (root) => {
    return Array.from(root.querySelectorAll(SELECTORS.STATUS_SPINNER))
        .some((spinner) => !spinner.hidden);
};

/**
 * Poll the brain status and refresh every row, stopping when all are settled.
 *
 * @param {Element} root The dashboard root element.
 */
const poll = (root) => {
    Ajax.call([{
        methodname: 'mod_tarsuszero_get_index_status',
        args: {cmid: cmid},
    }])[0].then((response) => {
        response.resources.forEach((data) => {
            const row = root.querySelector(
                SELECTORS.RESOURCE_ROW + '[data-resourceid="' + CSS.escape(data.resourceid) + '"]'
            );
            if (row) {
                updateRow(row, data);
            }
        });
        if (!hasInflight(root)) {
            stopPolling();
        }
        return response;
    }).catch(Notification.exception);
};

/**
 * Start the polling loop if it is not already running.
 *
 * @param {Element} root The dashboard root element.
 */
const startPolling = (root) => {
    if (pollTimer === null) {
        pollTimer = window.setInterval(() => poll(root), POLL_INTERVAL);
    }
};

/**
 * Stop the polling loop.
 */
const stopPolling = () => {
    if (pollTimer !== null) {
        window.clearInterval(pollTimer);
        pollTimer = null;
    }
};

/**
 * Send a single resource action to the server and refresh the row.
 *
 * @param {Element} root The dashboard root element.
 * @param {Element} row The resource row element.
 * @param {string} resourceid The namespaced resource id.
 * @param {string} action The action verb.
 */
const sendAction = (root, row, resourceid, action) => {
    row.querySelectorAll(SELECTORS.ACTION_BUTTON).forEach((button) => {
        button.disabled = true;
    });

    Ajax.call([{
        methodname: 'mod_tarsuszero_set_resource_action',
        args: {cmid: cmid, resourceid: resourceid, action: action},
    }])[0].then((data) => {
        updateRow(row, data);
        if (data.inflight) {
            startPolling(root);
        }
        return data;
    }).catch((error) => {
        row.querySelectorAll(SELECTORS.ACTION_BUTTON).forEach((button) => {
            button.disabled = false;
        });
        Notification.exception(error);
    });
};

/**
 * Toggle the inclusion state of a resource and persist it via AJAX.
 *
 * The checkbox state is applied optimistically by the browser on change;
 * we only revert it if the server call fails.
 *
 * @param {Element} row The resource row element.
 * @param {Element} checkbox The inclusion toggle checkbox.
 */
const sendInclusion = (row, checkbox) => {
    const resourceid = row.getAttribute('data-resourceid');
    const included = checkbox.checked;
    checkbox.disabled = true;

    Ajax.call([{
        methodname: 'mod_tarsuszero_set_resource_inclusion',
        args: {cmid: cmid, resourceid: resourceid, included: included},
    }])[0].then(() => {
        checkbox.disabled = false;
        return null;
    }).catch((error) => {
        // Revert optimistic update on failure.
        checkbox.checked = !included;
        checkbox.disabled = false;
        Notification.exception(error);
    });
};

/**
 * The resource ids of every row rendered on the current page.
 *
 * @param {Element} root The dashboard root element.
 * @return {string[]}
 */
const pageResourceIds = (root) => {
    return Array.from(root.querySelectorAll(SELECTORS.RESOURCE_ROW))
        .map((row) => row.getAttribute('data-resourceid'));
};

/**
 * Toggle the busy state of the bulk toolbar.
 *
 * @param {Element} root The dashboard root element.
 * @param {boolean} busy Whether a bulk call is in flight.
 */
const setBulkBusy = (root, busy) => {
    root.querySelectorAll(SELECTORS.BULK_BUTTON).forEach((button) => {
        button.disabled = busy;
    });
    const spinner = root.querySelector(SELECTORS.BULK_SPINNER);
    if (spinner) {
        spinner.hidden = !busy;
    }
};

/**
 * Apply a list of presentational payloads to their matching rows.
 *
 * @param {Element} root The dashboard root element.
 * @param {Object[]} resources The presentational payloads.
 */
const updateRows = (root, resources) => {
    resources.forEach((data) => {
        const row = root.querySelector(
            SELECTORS.RESOURCE_ROW + '[data-resourceid="' + CSS.escape(data.resourceid) + '"]'
        );
        if (row) {
            updateRow(row, data);
        }
    });
};

/**
 * Queue every indexable row on the current page in a single request.
 *
 * core/ajax batches the calls, so one page costs one round trip.
 *
 * @param {Element} root The dashboard root element.
 */
const bulkIndex = (root) => {
    const calls = Array.from(root.querySelectorAll(SELECTORS.RESOURCE_ROW))
        .filter((row) => {
            const button = row.querySelector('[data-action="index"]');
            return button && !button.hidden;
        })
        .map((row) => ({
            methodname: 'mod_tarsuszero_set_resource_action',
            args: {cmid: cmid, resourceid: row.getAttribute('data-resourceid'), action: 'index'},
        }));

    if (!calls.length) {
        return;
    }

    setBulkBusy(root, true);
    Promise.all(Ajax.call(calls)).then((results) => {
        updateRows(root, results);
        setBulkBusy(root, false);
        startPolling(root);
        return null;
    }).catch((error) => {
        setBulkBusy(root, false);
        Notification.exception(error);
    });
};

/**
 * Reconcile the current page against the brain in one batch call.
 *
 * @param {Element} root The dashboard root element.
 * @param {boolean} silent Whether to swallow failures instead of alerting.
 */
const bulkSync = (root, silent) => {
    const resourceids = pageResourceIds(root);
    if (!resourceids.length) {
        return;
    }

    setBulkBusy(root, true);
    Ajax.call([{
        methodname: 'mod_tarsuszero_sync_page_status',
        args: {cmid: cmid, resourceids: resourceids},
    }])[0].then((response) => {
        updateRows(root, response.resources);
        setBulkBusy(root, false);
        return null;
    }).catch((error) => {
        setBulkBusy(root, false);
        if (silent) {
            // A guardrail or a brain outage must not greet the teacher with a modal.
            window.console.debug('tarsus: page status sync failed', error);
            return;
        }
        Notification.exception(error);
    });
};

/**
 * Handle a click on an action button via event delegation.
 *
 * @param {Element} root The dashboard root element.
 * @param {Event} event The click event.
 */
const handleClick = (root, event) => {
    const bulk = event.target.closest(SELECTORS.BULK_BUTTON);
    if (bulk && !bulk.disabled) {
        if (bulk.getAttribute('data-bulk') === 'index') {
            bulkIndex(root);
        } else {
            bulkSync(root, false);
        }
        return;
    }

    const button = event.target.closest(SELECTORS.ACTION_BUTTON);
    if (!button || button.disabled) {
        return;
    }
    const row = button.closest(SELECTORS.RESOURCE_ROW);
    if (!row) {
        return;
    }
    const resourceid = row.getAttribute('data-resourceid');
    const action = button.getAttribute('data-action');

    if (action === 'remove') {
        Promise.all([
            getString('confirmremove_title', 'mod_tarsuszero'),
            getString('confirmremove_body', 'mod_tarsuszero'),
            getString('action_remove', 'mod_tarsuszero'),
        ]).then(([title, body, yes]) => {
            Notification.saveCancelPromise(title, body, yes).then(() => {
                sendAction(root, row, resourceid, action);
                return null;
            }).catch(() => null);
            return null;
        }).catch(Notification.exception);
        return;
    }

    sendAction(root, row, resourceid, action);
};

/**
 * Initialise the dashboard.
 *
 * @param {number} id The course module id.
 * @param {string} rootSelector The dashboard root element selector.
 */
export const init = (id, rootSelector) => {
    cmid = id;
    const root = document.querySelector(rootSelector);
    if (!root) {
        return;
    }
    root.addEventListener('click', (event) => handleClick(root, event));
    root.addEventListener('change', (event) => {
        const toggle = event.target.closest(SELECTORS.INCLUDE_TOGGLE);
        if (!toggle || toggle.disabled) {
            return;
        }
        const row = toggle.closest(SELECTORS.RESOURCE_ROW);
        if (row) {
            sendInclusion(row, toggle);
        }
    });
    if (hasInflight(root)) {
        startPolling(root);
    }

    // The brain answers a whole page in one call, so the listed statuses are
    // read live on open rather than waiting for the next cron run.
    bulkSync(root, true);
};
