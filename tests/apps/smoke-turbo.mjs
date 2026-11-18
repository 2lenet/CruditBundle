// Smoke test of the screens of Crudit under Turbo Drive (test application tests/apps/turbo): node smoke-turbo.mjs <url> [chrome path]
// Real Turbo visits, not simulated events (smoke.mjs only simulates turbo:load): the layout (menu, drawer, search),
// the list (filters, selection, pager), the record (confirmation, modals), the forms answered by a redirect (303) or
// by their errors (422), the workflow, the cache and Back, the controllers disconnected and connected again without
// leaving listeners behind, accessibility of the pages.
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {createRequire} from 'node:module';
import puppeteer from 'puppeteer-core';

const url = process.argv[2];
const executablePath = process.argv[3] ?? process.env.CHROME_PATH ?? '/usr/bin/google-chrome';
assert.ok(url, 'Usage: node smoke-turbo.mjs <url> [chrome path]');
const base = url.replace(/\/$/, '');

const browser = await puppeteer.launch({executablePath, headless: true, args: ['--no-sandbox']});
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
    page.on('pageerror', (error) => errors.push(`page error: ${error.message}`));
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

    // Listeners on the document and the window, counted by the DevTools protocol, by type.
    const cdp = await page.createCDPSession();
    const listeners = async () => {
        const counts = {};
        for (const target of ['document', 'window']) {
            const {result} = await cdp.send('Runtime.evaluate', {expression: target});
            const {listeners: found} = await cdp.send('DOMDebugger.getEventListeners', {objectId: result.objectId});
            await cdp.send('Runtime.releaseObject', {objectId: result.objectId});
            found.forEach(({type}) => {
                counts[`${target} ${type}`] = (counts[`${target} ${type}`] ?? 0) + 1;
            });
        }

        return counts;
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
    const full = await sidebarWidth();
    await page.click('.crudit-sidebar__collapse');
    await sleep(400);
    const rail = await sidebarWidth();
    assert.ok(rail < full / 2, 'the toggle does not reduce the menu');
    await visit(menu('/client/'));
    assert.equal(await sidebarWidth(), rail, 'the rail is lost by a Turbo visit');
    await visit(menu('/page/interventions'));
    await back();
    assert.equal(await sidebarWidth(), rail, 'the rail is lost by Back');
    await page.click('.crudit-sidebar__collapse');
    await sleep(400);
    assert.equal(await sidebarWidth(), full, 'the toggle does not expand the menu after Back');
    await visit(menu('/client/'));
    await page.click('.crudit-sidebar__collapse');
    await sleep(400);
    assert.equal(await sidebarWidth(), rail, 'the toggle does not reduce the menu after a visit');
    await page.click('.crudit-sidebar__collapse');
    await sleep(400);
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
    await after('turbo:load', () => page.click('dialog.crudit-ui-filter-panel button[type="submit"]'));
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
    await after('turbo:load', () => page.evaluate(() => [...document.querySelectorAll('nav[aria-label=navigation] a[href*="client_offset=10"]')].find((a) => a.offsetParent).click()));
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
