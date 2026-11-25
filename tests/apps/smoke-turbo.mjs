// Smoke test of the screens of Crudit under Turbo Drive (test application tests/apps/turbo): node smoke-turbo.mjs <url> [chromium|webkit|iphone|pixel]
// Real Turbo visits, not simulated events (smoke.mjs only simulates turbo:load): the layout (menu, drawer, search),
// the list (filters, selection, pager), the record (confirmation, modals), the forms answered by a redirect (303) or
// by their errors (422), the workflow, the cache and Back, the controllers disconnected and connected again without
// leaving listeners behind, accessibility of the pages.
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {createRequire} from 'node:module';
import {launch} from './browser.mjs';

const url = process.argv[2];
const project = process.argv[3] ?? process.env.PROJECT ?? 'chromium';
assert.ok(url, 'Usage: node smoke-turbo.mjs <url> [chromium|webkit|iphone|pixel]');
const base = url.replace(/\/$/, '');

const browser = await launch(project);
const errors = [];
// Defects found by this test and not fixed yet: shown as a warning, not a failure (remove the entry when it is fixed)
const known = [];
let passed = 0;
const ok = (name) => { passed++; console.log('ok  ', name); };
const axeSource = readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8');
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

try {
    const page = await browser.newPage();
    await page.setViewport({width: 1440, height: 900});
    // Listeners on the document and the window, by type: counted by the wrappers of add / removeEventListener, which
    // must be there before the page loads (the DevTools protocol only exists in Chromium).
    await page.addInitScript(() => {
        const registry = new Map();
        const ids = new WeakMap();
        let last = 0;
        const id = (listener) => ids.get(listener) ?? (ids.set(listener, ++last), last);
        const key = (type, listener, options) => `${type}|${typeof options === 'object' ? options?.capture === true : options === true}|${id(listener)}`;
        window.__listeners = registry;
        for (const target of [Document.prototype, Window.prototype]) {
            const {addEventListener, removeEventListener} = target;
            target.addEventListener = function (type, listener, options) {
                if (listener && (this === document || this === window)) {
                    registry.set(this, (registry.get(this) ?? new Map()).set(key(type, listener, options), true));
                    // A listener "once" is removed when it has run.
                    options?.once && addEventListener.call(this, type, () => registry.get(this)?.delete(key(type, listener, options)), {once: true});
                }

                return addEventListener.call(this, type, listener, options);
            };
            target.removeEventListener = function (type, listener, options) {
                listener && registry.get(this)?.delete(key(type, listener, options));

                return removeEventListener.call(this, type, listener, options);
            };
        }
    });
    page.on('pageerror', (error) => errors.push(`page error: ${error.message} (${page.url()})${error.stack ? `\n${String(error.stack).split('\n').slice(0, 4).join('\n')}` : ''}`));
    // 422: a form sent back with its errors, on purpose.
    page.on('console', (message) => message.type() === 'error' && !message.location().url?.endsWith('/favicon.ico') && !message.text().includes('status of 422') && !message.text().includes('Form responses must redirect') && errors.push(`console: ${message.text()}`));
    page.on('response', (response) => response.status() >= 400 && response.status() !== 422 && !response.url().endsWith('/favicon.ico') && errors.push(`${response.status()}: ${response.url()}`));

    // Waits for a Turbo event on the document after an action: turbo:load (a visit, Back), turbo:render (a form sent
    // back with its errors), turbo:frame-load (a frame). Then a frame, for Stimulus to connect the controllers.
    const after = async (event, action) => {
        await page.evaluate((name) => {
            window.turboEvent = new Promise((resolve) => document.addEventListener(name, () => resolve(), {once: true}));
        }, event);
        await action();
        await page.waitForFunction(() => window.turboEvent.then(() => true), {timeout: 10000});
        await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => resolve())));
    };
    // A click on a link or a button that Turbo visits.
    const visit = (selector) => after('turbo:load', () => page.click(selector));
    const go = (path) => after('turbo:load', () => page.evaluate((address) => window.Turbo.visit(address), path));
    const back = () => after('turbo:load', () => page.evaluate(() => history.back()));
    const path = () => page.evaluate(() => location.pathname + location.search);
    const text = (selector) => page.$eval(selector, (element) => element.textContent.replace(/\s+/g, ' ').trim());
    const attr = (selector, name) => page.$eval(selector, (element, attribute) => element.getAttribute(attribute), name);
    const exists = (selector) => page.evaluate((query) => document.querySelector(query) !== null, selector);

    // A page loaded once: the visits that follow keep this window (no full load).
    await page.goto(base + '/', {waitUntil: 'networkidle0'});
    assert.equal(await page.evaluate(() => typeof window.Turbo?.visit), 'function', 'Turbo is not loaded');
    // The Turbo events seen by the page, shown when a step fails
    await page.evaluate(() => {
        window.marker = true;
        window.turboEvents = [];
        ['turbo:before-visit', 'turbo:submit-start', 'turbo:submit-end', 'turbo:before-render', 'turbo:render', 'turbo:morph', 'turbo:frame-load', 'turbo:load']
            .forEach((name) => document.addEventListener(name, () => window.turboEvents.push(`${location.pathname}: ${name}`)));
    });
    const sameWindow = async (message) => assert.equal(await page.evaluate(() => window.marker), true, `${message}: the page was fully loaded, not visited by Turbo`);
    const menu = (href) => `#crudit-sidebar a[href="${href}"]`;

    // Listeners on the document and the window, by type (the wrappers are installed at the top).
    // A tooltip or a floating menu shown (by the focus, the pointer) has a listener of its own while it is: nothing is
    // shown when the listeners are counted.
    const listeners = async () => {
        await page.evaluate(() => document.activeElement?.blur());
        await page.mouse.move(0, 0);
        await sleep(300);

        return page.evaluate(() => {
            const counts = {};
            for (const [name, target] of [['document', document], ['window', window]]) {
                for (const key of window.__listeners.get(target)?.keys() ?? []) {
                    const type = key.split('|')[0];
                    counts[`${name} ${type}`] = (counts[`${name} ${type}`] ?? 0) + 1;
                }
            }

            return counts;
        });
    };

    // ---------- Visits back and forth: the controllers of each page connect, those of the page left disconnect and
    // remove their listeners. Counted on the home page after a first round trip (Turbo adds its own listener of the
    // clicks on the first one), and after five more.
    await visit(menu('/client/'));
    assert.equal(await path(), '/client/');
    await visit(menu('/page/interventions'));
    await visit('#crudit-sidebar a.crudit-nav__link[href="/"]');
    const before = await listeners();
    for (let i = 0; i < 5; i++) {
        await visit(menu('/client/'));
        assert.equal(await path(), '/client/');
        await visit(menu('/page/interventions'));
        assert.equal(await path(), '/page/interventions');
        await visit('#crudit-sidebar a.crudit-nav__link[href="/"]');
    }
    await sameWindow('visits');
    assert.deepEqual(await listeners(), before, 'listeners left on the document or the window after Turbo visits');
    ok('visits back and forth: no listener left on the document or the window');

    // ---------- The focus goes to the title of the page after a visit (page-title), not after the first load.
    await visit(menu('/page/interventions'));
    assert.equal(await page.evaluate(() => document.activeElement.id), 'page-title', 'the focus is not on the title after a visit');
    await back();
    assert.equal(await page.evaluate(() => document.activeElement.id), 'page-title', 'the focus is not on the title after Back');
    await sameWindow('title');
    ok('focus on the title after a visit and after Back');

    // ---------- The menu: the rail chosen by the user stays across the visits (the cookie, <html> stays) and comes
    // back with Back; the toggle works on each page.
    const sidebarWidth = () => page.$eval('#crudit-sidebar', (element) => Math.round(element.getBoundingClientRect().width));
    // The width follows a transition, which WebKit starts late: waited for until it changes, then until it stops (a
    // fixed pause is too short). Without a change expected, nothing to wait for.
    const settled = async (from) => {
        await page.waitForFunction((width) => Math.round(document.querySelector('#crudit-sidebar').getBoundingClientRect().width) !== width, {timeout: 3000}, from).catch(() => null);
        for (let last = -1, same = 0; same < 3;) {
            const width = await sidebarWidth();
            same = width === last ? same + 1 : 0;
            last = width;
            await sleep(100);
        }
    };
    const toggle = async () => {
        const from = await sidebarWidth();
        await page.click('.crudit-sidebar__collapse');
        await settled(from);
    };
    const full = await sidebarWidth();
    await toggle();
    const rail = await sidebarWidth();
    assert.ok(rail < full / 2, 'the toggle does not reduce the menu');
    await visit(menu('/client/'));
    assert.equal(await sidebarWidth(), rail, 'the rail is lost by a Turbo visit');
    await visit(menu('/page/interventions'));
    await back();
    assert.equal(await sidebarWidth(), rail, 'the rail is lost by Back');
    await toggle();
    assert.equal(await sidebarWidth(), full, 'the toggle does not expand the menu after Back');
    await visit(menu('/client/'));
    await toggle();
    assert.equal(await sidebarWidth(), rail, 'the toggle does not reduce the menu after a visit');
    await toggle();
    await sameWindow('rail');
    ok('menu: the rail across visits and Back, the toggle after each');

    // ---------- The drawer of a phone: opened, then a link visited: the next page is free (nothing inert, no scroll
    // lock, the drawer closed). Back does not show the drawer open, and it opens again.
    await page.setViewport({width: 390, height: 800});
    await sleep(300);
    const drawer = () => page.evaluate(() => ({
        open: getComputedStyle(document.querySelector('#crudit-sidebar')).visibility === 'visible',
        inert: document.querySelector('.crudit-shell__body').inert,
        locked: document.documentElement.classList.contains('crudit-has-drawer'),
        expanded: document.querySelector('.crudit-header__burger').getAttribute('aria-expanded'),
    }));
    await page.click('.crudit-header__burger');
    await sleep(400);
    assert.deepEqual(await drawer(), {open: true, inert: true, locked: true, expanded: 'true'}, 'the drawer does not open');
    await visit(menu('/page/interventions'));
    await sleep(400);
    assert.deepEqual(await drawer(), {open: false, inert: false, locked: false, expanded: 'false'}, 'the page reached from the drawer is locked or the drawer open');
    await page.click('.crudit-header__burger');
    await sleep(400);
    assert.equal((await drawer()).open, true, 'the drawer does not open after a visit');
    await page.keyboard.press('Escape');
    await sleep(400);
    await page.click('.crudit-header__burger');
    await sleep(400);
    await visit(menu('/client/'));
    await back();
    await sleep(400);
    assert.deepEqual(await drawer(), {open: false, inert: false, locked: false, expanded: 'false'}, 'Back shows the drawer open or the page locked');
    await page.click('.crudit-header__burger');
    await sleep(400);
    assert.deepEqual(await drawer(), {open: true, inert: true, locked: true, expanded: 'true'}, 'the drawer does not open after Back');
    await page.keyboard.press('Escape');
    await sleep(400);
    await sameWindow('drawer');
    await page.setViewport({width: 1440, height: 900});
    await sleep(300);
    ok('drawer of a phone: the page reached is free, Back does not show it open');

    // ---------- The global search: Ctrl K opens the palette once, after visits and after Back.
    const palette = () => page.evaluate(() => [...document.querySelectorAll('dialog')].filter((dialog) => dialog.open).map((dialog) => dialog.id));
    const ctrlK = async () => {
        await page.keyboard.down('Control');
        await page.keyboard.press('k');
        await page.keyboard.up('Control');
        await sleep(300);
    };
    await ctrlK();
    assert.deepEqual(await palette(), ['crudit-search'], 'Ctrl K does not open the palette');
    await page.keyboard.press('Escape');
    await sleep(300);
    assert.deepEqual(await palette(), [], 'Escape does not close the palette');
    await visit(menu('/page/interventions'));
    await ctrlK();
    assert.deepEqual(await palette(), ['crudit-search'], 'Ctrl K does not open the palette after a visit');
    await page.keyboard.press('Escape');
    await sleep(300);
    // A result opened by Enter: visited by Turbo (the palette is closed on the next page), then Back
    await ctrlK();
    await page.keyboard.type('Client 04');
    await page.waitForSelector('#crudit-search [role=option]', {timeout: 5000});
    await after('turbo:load', () => page.keyboard.press('Enter'));
    assert.match(await path(), /^\/client\/show\/\d+$/, 'the result of the search does not lead to the record');
    assert.deepEqual(await palette(), [], 'the palette stays open on the page reached');
    assert.equal(await page.evaluate(() => getComputedStyle(document.documentElement).overflow === 'hidden'), false, 'the page reached from the palette is locked');
    await back();
    assert.deepEqual(await palette(), [], 'Back shows the palette open');
    await ctrlK();
    assert.deepEqual(await palette(), ['crudit-search'], 'Ctrl K does not open the palette after Back');
    await page.keyboard.press('Escape');
    await sleep(300);
    await sameWindow('search');
    ok('global search: Ctrl K after visits and Back, a result visited');

    // ---------- The list: a filter typed in the bar is a Turbo visit (no full load), the chip and the reset too; Back
    // shows a list whose filters and selection work.
    const rows = () => page.$$eval('main tbody tr.crudit-ui-table__row', (trs) => trs.length);
    const bar = () => page.evaluate(() => {
        const element = document.querySelector('.crudit-ui-selection-bar');

        return {
            shown: element !== null && getComputedStyle(element).visibility !== 'hidden' && element.getClientRects().length > 0,
            checked: document.querySelectorAll('tbody input[type=checkbox]:checked').length,
            count: element?.querySelector('.crudit-ui-selection-bar__count')?.textContent.trim() ?? '',
        };
    });
    await go('/client/?client_reset=1');
    await go('/client/');
    const allRows = await rows();
    assert.ok(allRows > 1, 'the list has rows');
    await page.click('[data-filter-quick="name"] button[aria-expanded]');
    await after('turbo:load', () => page.type('[name="filter_client_name_value"]', 'Client 0'));
    await sameWindow('filter');
    assert.ok((await rows()) < allRows, 'the list is not filtered');
    assert.equal(await page.$eval('[name="filter_client_name_value"]', (input) => input.value), 'Client 0');
    ok('list: a filter of the bar is a Turbo visit');

    // The panel and a chip: the panel stays open after Apply, the chip removes its filter, the reset brings everything back
    await page.click('[data-2lenet--crudit-ui-bundle--filters-target="toggle"]');
    await sleep(400);
    await page.click('dialog.crudit-ui-filter-panel label:has([name="filter_client_active_value"][value="true"])');
    await after('turbo:load', () => page.click('dialog.crudit-ui-filter-panel [data-2lenet--crudit-ui-bundle--filters-target="apply"]'));
    await sameWindow('panel');
    assert.equal(await page.$eval('dialog.crudit-ui-filter-panel', (panel) => panel.open), true, 'the panel is closed after Apply');
    assert.equal(await page.$$eval('main ul.crudit-ui-chips > li', (chips) => chips.length), 1, 'one chip');
    await after('turbo:load', () => page.click('main ul.crudit-ui-chips button.crudit-ui-chip__remove'));
    assert.equal(await page.$$eval('main ul.crudit-ui-chips > li', (chips) => chips.length), 0, 'the chip is still there');
    await after('turbo:load', () => page.click('dialog.crudit-ui-filter-panel a[href*="client_reset=1"]'));
    assert.equal(await rows(), allRows, 'the reset does not bring all the rows back');
    await sameWindow('reset');
    ok('list: the panel, a chip, the reset');

    // The selection: the bar shows when a row is checked, follows the pager (a visit), is empty after a sort; Back
    // never shows a bar without a selection, or a selection without a bar.
    await go('/client/?client_reset=1&client_limit=10');
    assert.equal((await bar()).shown, false, 'the bar shows without a selection');
    await page.click('tbody tr:nth-child(2) input[type=checkbox]');
    assert.deepEqual([(await bar()).shown, (await bar()).checked], [true, 1], 'the bar does not show for a checked row');
    await after('turbo:load', () => page.evaluate(() => [...document.querySelectorAll('nav.crudit-ui-pager__nav a[href*="client_offset=10"]')].find((a) => a.offsetParent).click()));
    assert.match(await path(), /client_offset=10/);
    assert.deepEqual([(await bar()).shown, (await bar()).checked, (await bar()).count.startsWith('1 row selected')], [true, 0, true], 'the selection does not follow the pager visited by Turbo');
    await page.click('tbody tr:nth-child(3) input[type=checkbox]');
    assert.ok((await bar()).count.startsWith('2 rows selected'), 'the row of page 2 is not counted');
    await sameWindow('pager');
    await after('turbo:load', () => page.click('.crudit-ui-table__sort[href*="client_sort=email"]'));
    assert.deepEqual([(await bar()).shown, (await bar()).checked], [false, 0], 'a sort does not start from an empty selection');
    await page.click('tbody tr:first-child input[type=checkbox]');
    await visit(menu('/page/interventions'));
    await back();
    const afterBack = await bar();
    assert.equal(afterBack.shown, afterBack.checked > 0, `Back shows the bar ${afterBack.shown ? 'without' : 'hidden with'} a selection (${afterBack.checked} row checked)`);
    await page.click('tbody tr:nth-child(2) input[type=checkbox]');
    assert.equal((await bar()).shown, true, 'the bar does not show after Back');
    await sameWindow('selection');
    ok('list: the selection follows the pager, a sort empties it, Back keeps the bar consistent');

    // ---------- The record: reached by a visit, the confirmation names the element, the focus is given back; a modal
    // with fields; left and back, no dialog open.
    await go('/client/?client_reset=1&client_limit=25');
    const show = await page.$$eval('tbody tr', (trs) => trs.find((tr) => tr.textContent.includes('Client 04')).querySelector('a[href*="/client/show/"]').getAttribute('href'));
    await go(show);
    const opens = () => page.evaluate(() => [...document.querySelectorAll('dialog')].find((dialog) => dialog.open)?.id ?? null);
    const focus = () => page.evaluate(() => (document.activeElement?.textContent ?? '').trim());
    await page.click('.crudit-page-actions__secondary button.crudit-ui-btn--danger');
    await sleep(300);
    assert.equal(await opens(), 'crudit-confirm', 'the confirmation does not open after a visit');
    assert.equal(await text('#crudit-confirm-title'), 'Delete “Client 04”?', 'the confirmation does not name the element');
    assert.equal(await focus(), 'Cancel');
    await page.keyboard.press('Escape');
    await sleep(300);
    assert.deepEqual([await opens(), await focus()], [null, 'Delete'], 'Escape does not close the confirmation or give the focus back');
    await page.click('.crudit-page-actions__secondary button[commandfor^="modal_"]');
    await sleep(300);
    assert.ok((await opens())?.startsWith('modal_'), 'the modal of the action does not open after a visit');
    assert.equal(await page.evaluate(() => document.activeElement?.name), 'subject', 'the focus is not on the first field');
    // Left with the modal open (the layout, Back): not shown open again
    await go('/client/');
    await back();
    assert.deepEqual([await opens(), await page.evaluate(() => getComputedStyle(document.documentElement).overflow === 'hidden')], [null, false], 'Back shows the modal open or the page locked');
    await page.click('.crudit-page-actions__secondary button[commandfor^="modal_"]');
    await sleep(300);
    assert.ok((await opens())?.startsWith('modal_'), 'the modal does not open after Back');
    await page.keyboard.press('Escape');
    await sleep(300);
    assert.equal(await focus(), 'Send an e-mail', 'the focus is not given back after Back');
    await sameWindow('record');
    ok('record: the confirmation and the modal after a visit and after Back');

    // ---------- The batch actions: a link of the bar, the form of an action in a dialog and a free modal, each answered
    // by a redirect (303) that Turbo follows: no full load, the flash message as a toast (announced).
    const toasts = () => page.$$eval('#crudit-ui-toasts .crudit-ui-toast__message', (elements) => elements.map((toast) => toast.textContent.trim()));
    const announced = (expected) => page.waitForFunction((value) => document.querySelector('#crudit-ui-toasts [aria-live="polite"]').textContent.includes(value), {timeout: 3000}, expected)
        .then(() => true, () => false);
    const barAction = (label) => page.evaluate((expected) => {
        [...document.querySelectorAll('.crudit-ui-selection-bar__actions button[command=show-modal]')].find((button) => button.textContent.includes(expected)).click();
    }, label);
    const closeToasts = () => page.$$eval('#crudit-ui-toasts .crudit-ui-toast__close', (buttons) => buttons.forEach((button) => button.click()));

    await go('/client/?client_reset=1&client_limit=25');
    await page.click('tbody tr:first-child input[type=checkbox]');
    const first = await page.$eval('tbody tr:first-child input[type=checkbox]', (box) => box.dataset.id);
    await visit('.crudit-ui-selection-bar__actions a[href*="/client/batch/export"]');
    assert.equal(await path(), '/client/', 'the link of the bar does not lead back to the list');
    assert.ok((await toasts()).some((message) => message.includes(`export: ids=${first} all_page=0`)), 'the link of the bar did not send the selection');
    assert.ok(await announced(`export: ids=${first}`), 'the flash message is not announced after a redirect of Turbo');
    assert.equal((await bar()).shown, false, 'the selection is kept after the visit of a batch action');
    await sameWindow('batch link');
    await closeToasts();

    await page.click('tbody tr:nth-child(2) input[type=checkbox]');
    const second = await page.$eval('tbody tr:nth-child(2) input[type=checkbox]', (box) => box.dataset.id);
    await barAction('Change the status');
    await sleep(600);
    assert.equal(await page.evaluate(() => document.querySelector('dialog:modal form') !== null), true, 'the form of the action does not open in a dialog');
    await visit('dialog:modal button[type=submit]');
    assert.ok((await toasts()).some((message) => message.includes(`status: ids=${second} all_page=0`)), 'the form of the action did not send the selection');
    assert.equal(await page.evaluate(() => document.querySelector('dialog:modal')), null, 'the dialog stays open after the redirect');
    assert.equal(await page.evaluate(() => getComputedStyle(document.documentElement).overflow === 'hidden'), false, 'the page is locked after the redirect');
    await sameWindow('batch form');
    await closeToasts();

    await page.click('tbody tr:first-child input[type=checkbox]');
    await barAction('Send a message');
    await sleep(600);
    await page.type('dialog:modal textarea', 'hello');
    await visit('dialog:modal button[type=submit]');
    assert.ok((await toasts()).some((message) => message.includes(`message: ids=${first} all_page=0`)), 'the free modal did not send the selection');
    await sameWindow('batch modal');
    await closeToasts();
    ok('batch actions: a link, a form in a dialog, a free modal (redirect, toast)');

    // ---------- A form sent: a redirect (303) with the flash message as a toast, announced; a form with errors must
    // be answered by a 422 for Turbo to show it (it refuses a 200).
    await go('/client/new');
    const invalid = page.waitForResponse((response) => response.request().method() === 'POST', {timeout: 5000});
    await page.click('form [type=submit]');
    const status = (await invalid).status();
    assert.equal(status, 422, 'a form with errors is not answered by a 422 (Turbo refuses a 200)');
    await page.waitForFunction(() => document.querySelector('[aria-invalid=true]') !== null, {timeout: 3000});
    assert.equal(await path(), '/client/new', 'the form with errors changes the address');
    await sameWindow('form with errors');
    await page.type('#client_name', 'Client Turbo');
    // Required in the default locale (lot 3.2.c): its tab, in error, is the one shown
    await page.type('#client_description_en', 'Created by Turbo');
    await visit('form [type=submit]');
    assert.equal(await path(), '/client/', 'the form sent does not lead to the list');
    assert.ok((await toasts()).some((message) => message.includes('Success')), 'the flash message is not shown after the redirect of the form');
    assert.ok(await announced('Success'), 'the flash message is not announced after the redirect of the form');
    await back();
    assert.equal(await page.$eval('form [type=submit]', (button) => [button.getAttribute('aria-busy'), button.disabled]).then((state) => state.join()), ',false', 'the button of the form is left in loading in the snapshot');
    assert.deepEqual(await toasts(), [], 'the toast of the form is shown again by Back');
    await sameWindow('form');
    ok('form: a redirect and its toast, Back');

    // ---------- The workflow: a transition without a reload (fetch) in a page reached by a visit, the status is the new
    // one after a visit and Back, and its menu still works.
    await go('/intervention/');
    await page.evaluate(() => { window.notReloaded = true; });
    const wfRow = (reference) => page.$$eval('.crudit-ui-table__row', (trs, value) => '#' + trs.find((tr) => tr.querySelector('th[scope=row]')?.textContent.trim() === value).id, reference);
    let wf = await wfRow('INT-02');
    assert.equal(await text(`${wf} .crudit-workflow__trigger`), 'To plan, change the status of INT-02', 'the database is not the one of app:seed (INT-02 to plan)');
    await page.click(`${wf} .crudit-workflow__trigger`);
    await sleep(300);
    await page.click(`${wf} .crudit-workflow .crudit-ui-menu__item[data-transition=plan]`);
    await sleep(400);
    assert.equal(await opens(), 'crudit-confirm', 'the confirmation of the transition does not open after a visit');
    await page.click('#crudit-confirm .crudit-action');
    await page.waitForFunction((row) => document.querySelector(`${row} .crudit-workflow__trigger`)?.textContent.includes('Planned'), {timeout: 5000}, wf);
    assert.equal(await page.evaluate(() => window.notReloaded), true, 'the transition reloads the page');
    assert.equal(await page.evaluate((row) => document.activeElement === document.querySelector(`${row} .crudit-workflow__trigger`), wf), true, 'the focus is not back on the status');
    await visit(menu('/client/'));
    await back();
    wf = await wfRow('INT-02');
    assert.ok((await text(`${wf} .crudit-workflow__trigger`)).startsWith('Planned'), 'Back does not show the new status');
    await page.click(`${wf} .crudit-workflow__trigger`);
    await sleep(300);
    assert.equal(await attr(`${wf} .crudit-workflow__trigger`, 'aria-expanded'), 'true', 'the menu of the status does not open after Back');
    await page.click(`${wf} .crudit-workflow .crudit-ui-menu__item[data-transition=start]`);
    await sleep(400);
    assert.equal(await opens(), 'crudit-confirm', 'the confirmation of the transition does not open after Back');
    await page.keyboard.press('Escape');
    await sleep(300);
    await sameWindow('workflow');
    ok('workflow: a transition, the new status and the menu after Back');

    // ---------- Accessibility of the pages reached by Turbo (WCAG 2.1 A and AA, axe-core).
    await page.evaluate(axeSource);
    const audit = async (name) => {
        // The contrasts are measured once the opening of a dialog or a transition is over.
        await sleep(600);
        const violations = await page.evaluate(async () => (await window.axe.run(document, {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']})).violations
            .map((violation) => `${violation.id} (${violation.nodes.map((node) => node.target.join(' ')).join(', ')}): ${violation.help}`));
        assert.deepEqual(violations, [], `accessibility of ${name}`);
    };
    await go('/client/');
    await audit('the list reached by Turbo');
    await go(show);
    await audit('the record reached by Turbo');
    await go('/client/new');
    await audit('the form reached by Turbo');
    await go('/');
    await audit('the home page reached by Turbo');
    ok('axe: the pages reached by Turbo');

    // ---------- Saved filters (lot 3.1.f), signed in as alice (the session keeps her afterwards, so this is the last step):
    // saving, recalling, removing and deleting a set are POST forms sent by Turbo (a redirect, no full load).
    await page.setExtraHTTPHeaders({Authorization: 'Basic ' + Buffer.from('alice:alice').toString('base64')});
    const toast = (expected) => page.waitForFunction((text) => document.querySelector('#crudit-ui-toasts')?.textContent.includes(text), {timeout: 5000}, expected);
    const SAVE = 'button[aria-controls="crudit-filters-client-save"]';
    const MY_FILTERS = '.crudit-ui-filter-saved .crudit-ui-dropdown > button';
    await go('/client/?client_reset=1');
    await go('/client/');
    const everyRow = await rows();
    assert.ok(await page.$('.crudit-ui-filter-saved'), 'a user has "My filters" in a page visited by Turbo');
    // The panel is remembered open by the steps before: open it only if it is closed.
    if (!(await page.$eval('dialog.crudit-ui-filter-panel', (panel) => panel.open))) {
        await page.click('[data-2lenet--crudit-ui-bundle--filters-target="toggle"]');
        await sleep(400);
    }
    await page.click('dialog.crudit-ui-filter-panel label:has([name="filter_client_active_value"][value="true"])');
    await page.click(SAVE);
    await page.type('#crudit-filters-client-save-name', 'Active clients');
    await after('turbo:load', () => page.keyboard.press('Enter'));
    await sameWindow('save filters');
    await toast('The filters “Active clients” are saved.');
    assert.equal(await path(), '/client/', 'the address after the save is the list, clean');
    assert.ok((await rows()) < everyRow, 'the saved filters are not applied');
    assert.equal(await text('.crudit-ui-filter-saved__label'), 'Active clients', 'the set in use is not in the button');
    // The set in use removes its filters (a link), the set applies them again (a POST), both visits of Turbo.
    await page.click(MY_FILTERS);
    await after('turbo:load', () => page.click('a.crudit-ui-filter-saved__apply'));
    assert.equal(await rows(), everyRow, 'the set in use does not remove its filters');
    await page.click(MY_FILTERS);
    await after('turbo:load', () => page.click('button.crudit-ui-filter-saved__apply'));
    await toast('The filters “Active clients” are applied.');
    assert.ok((await rows()) < everyRow, 'the set is not applied again');
    await sameWindow('recall filters');
    // The deletion is asked in place, then sent.
    await page.click(MY_FILTERS);
    await page.click('.crudit-ui-filter-saved__item .crudit-ui-icon-btn');
    assert.equal(await focus(), 'Cancel', 'the question of the deletion does not give the focus to Cancel');
    await after('turbo:load', () => page.click('.crudit-ui-filter-saved__confirm .crudit-ui-btn--danger'));
    await toast('The filters “Active clients” are deleted.');
    await sameWindow('delete filters');
    await page.click(MY_FILTERS);
    assert.ok(await page.$('.crudit-ui-filter-saved__empty'), 'the deleted set is still listed');
    assert.equal(await page.$eval('#crudit-filters-client-saved-form input[name=_token]', (input) => input.value.length > 20), true, 'the token is not in the page visited');
    await page.keyboard.press('Escape');
    ok('saved filters: saved, recalled, removed and deleted by Turbo, no full load');

    known.forEach((defect) => console.warn('KNOWN DEFECT', defect));
    assert.deepEqual(errors, [], 'errors in the page');
    console.log(`OK ${url} (Turbo), ${passed} checks passed${known.length ? `, ${known.length} known defect(s)` : ''}`);
} catch (error) {
    const last = await browser.pages().then((pages) => pages[pages.length - 1].evaluate(() => (window.turboEvents ?? []).slice(-12).join('\n'))).catch(() => '');
    console.error(`Last Turbo events:\n${last}\nPage errors: ${JSON.stringify(errors)}`);
    throw error;
} finally {
    await browser.close();
}
