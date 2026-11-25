import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {createRequire} from 'node:module';
import {launch} from './browser.mjs';

const require = createRequire(import.meta.url);
const axeSource = readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const base = (process.argv[2] ?? '').replace(/\/$/, '');
const project = process.argv[3] ?? process.env.PROJECT ?? 'chromium';
const shots = process.env.SHOTS;
assert.ok(base, 'Usage: node smoke.mjs <url> [chromium|webkit|iphone|pixel]');

const browser = await launch(project);
console.log(`Browser: ${browser.version()}`);
const errors = [];
let passed = 0;
const ok = (name) => { passed++; console.log('ok  ', name); };

// One page at a time: open() closes the previous one
let current = null;
// user: signed in with http_basic (tests/apps/crudit/src/Security/UserProvider.php) on the first request, then by the
// session only (switch_user keeps its user); platform: the client hints of a Mac (global search)
async function open(width, path = '/', {cookie = null, dark = false, rtl = false, user = null, platform = null} = {}) {
    if (current && !current.isClosed()) {
        await current.close();
    }
    const page = await browser.newPage();
    current = page;
    page.on('pageerror', (e) => errors.push(`pageerror: ${e.message} (${page.url()})${e.stack ? `\n${String(e.stack).split('\n').slice(0, 4).join('\n')}` : ''}`));
    page.on('console', (m) => m.type() === 'error' && errors.push(`console: ${m.text()}`));
    await page.setViewport({width, height: 800});
    if (platform) {
        // navigator.userAgentData (client hints, read first by the controllers) and navigator.platform, both.
        await page.setUserAgent({
            userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',
            userAgentMetadata: {platform: 'macOS', platformVersion: '14.0.0', architecture: 'arm', model: '', mobile: false},
            platform,
        });
    }
    await page.emulateMediaFeatures([{name: 'prefers-color-scheme', value: dark ? 'dark' : 'light'}]);
    // The pages share the cookies of the browser: menu, theme, session
    await page.deleteCookie(...(await page.cookies(base)));
    if (cookie) {
        await page.setCookie({name: 'crudit_sidebar', value: cookie, url: base});
    }
    if (user) {
        await page.setExtraHTTPHeaders({Authorization: 'Basic ' + Buffer.from(`${user}:${user}`).toString('base64')});
        await page.goto(base + '/', {waitUntil: 'networkidle0'});
        await page.setExtraHTTPHeaders({});
    }
    await page.goto(base + path, {waitUntil: 'networkidle0'});
    if (rtl) {
        await page.evaluate(() => { document.documentElement.dir = 'rtl'; document.documentElement.lang = 'ar'; });
    }
    return page;
}
const box = (page, sel) => page.$eval(sel, (e) => { const r = e.getBoundingClientRect(); return {x: r.x, y: r.y, w: r.width, h: r.height, r: r.right, b: r.bottom}; });
const visible = (page, sel) => page.$eval(sel, (e) => { const s = getComputedStyle(e); const r = e.getBoundingClientRect(); return s.visibility !== 'hidden' && s.display !== 'none' && r.width > 1 && r.height > 1; });
const attr = (page, sel, a) => page.$eval(sel, (e, a) => e.getAttribute(a), a);
const active = (page) => page.evaluate(() => { const a = document.activeElement; return a ? (a.id || a.className || a.tagName) : null; });
const inAside = (page) => page.evaluate(() => document.querySelector('#crudit-sidebar').contains(document.activeElement));
// What the lots have not reworked yet is not checked (none left in the layout since lot 2.2.a): add a selector here
// only for a zone still to be reworked.
const EXCLUDED = [];

// A pause for the transitions (drawer, dialog, menu) to be over: a fixed one is too short on a slow machine, and WebKit
// starts a transition late. The time to start, then until no animation is running (an endless one, a spinner, or one driven
// by the scroll, the shadow of the heading of a table, is not waited for), then a frame.
async function settle(page) {
    await new Promise((r) => setTimeout(r, 100));
    await page.waitForFunction(() => document.getAnimations().every((animation) => animation.playState !== 'running' || !(animation.timeline instanceof DocumentTimeline) || animation.effect?.getComputedTiming().iterations === Infinity), {timeout: 3000}).catch(() => null);
    await new Promise((r) => setTimeout(r, 50));
}
async function axe(page, label) {
    await new Promise((r) => setTimeout(r, 500));
    await page.evaluate(axeSource);
    const result = await page.evaluate((exclude) => axe.run({exclude}, {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice']}), EXCLUDED);
    const v = result.violations.map((x) => `${x.id}: ${x.nodes.map((n) => n.target.join(' ')).join(' | ')}`);
    assert.deepEqual(v, [], `axe ${label}`);
    ok(`axe ${label}`);
}
const shot = async (page, name) => shots && page.screenshot({path: `${shots}/${name}.png`});

// The width of the menu is animated: wait for it (a fixed delay is too short on a slow runner).
const sidebarWidth = (page, width) => page.waitForFunction((expected) => Math.abs(document.querySelector('#crudit-sidebar').getBoundingClientRect().width - expected) < 2, {timeout: 5000}, width).catch(() => {});

// ---------- Desktop, full menu (auto, ≥ 1280)
let page = await open(1440);
assert.equal(await attr(page, '.crudit-shell', 'data-sidebar'), 'auto');
assert.ok(Math.abs((await box(page, '#crudit-sidebar')).w - 240) < 2, 'full menu is 240 px');
assert.equal(await attr(page, '#crudit-sidebar', 'role'), 'complementary');
assert.ok(await visible(page, '#crudit-menu-filter'));
assert.ok(!(await visible(page, '.crudit-header__burger')), 'no burger on desktop');
assert.ok(!(await visible(page, '.crudit-sidebar__close')), 'no close on desktop');
assert.equal(await page.$eval('[data-2lenet--crudit-bundle--sidebar-target=toggleLabel]', (e) => e.textContent), 'Collapse the menu');
assert.equal(await page.$eval('[aria-current=page] .crudit-nav__label', (e) => e.textContent.trim()), 'Home');
assert.ok(await visible(page, '[data-menu-label=Clients] .crudit-nav__badge') && !(await visible(page, '[data-menu-label=Clients] .crudit-nav__dot')), 'badge shown, no dot');
assert.ok(await visible(page, '[data-menu-label=Clients] .crudit-nav__icon svg'), 'icon of the menu (SVG)');
ok('full menu at 1440');
await shot(page, 'desktop-full');
await axe(page, 'desktop full');

// current page: /page/invoices opens Billing
await page.goto(base + '/page/invoices', {waitUntil: 'networkidle0'});
assert.equal(await attr(page, '[data-menu-label=Billing] > .crudit-ui-tooltip-host > button', 'aria-expanded'), 'true');
assert.equal(await attr(page, '[data-menu-label=Stock] > .crudit-ui-tooltip-host > button', 'aria-expanded'), 'false');
assert.equal(await page.$eval('[aria-current=page] .crudit-nav__label', (e) => e.textContent.trim()), 'Invoices');
ok('sub-menu of the current page is open, aria-current set');

// open / close a submenu by click
await page.click('[data-menu-label=Stock] button');
assert.ok(await visible(page, '[data-menu-label=Stock] .crudit-nav__submenu'));
await page.click('[data-menu-label=Stock] button');
assert.ok(!(await visible(page, '[data-menu-label=Stock] .crudit-nav__submenu')));
ok('sub-menu toggles');
await shot(page, 'desktop-full-submenu');

// filter
await page.type('#crudit-menu-filter', 'bill');
const labels = () => page.$$eval('[data-menu-kind=item]:not([hidden])', (els) => els.filter((e) => e.offsetParent !== null).map((e) => e.dataset.menuLabel));
assert.deepEqual(await labels(), ['Billing', 'Invoices', 'Credit notes', 'Reminders']);
assert.ok(await visible(page, '.crudit-nav__heading--category'), 'heading of the group that matches stays');
assert.ok(!(await visible(page, '.crudit-nav__heading--title')) && !(await visible(page, '.crudit-nav__heading--header')), 'headings without match hidden');
await new Promise((r) => setTimeout(r, 700));
assert.equal(await page.$eval('#crudit-menu-filter-status', (e) => e.textContent), '3 items found');
ok('filter by parent label keeps its links, announces count');
await shot(page, 'desktop-filter');
await page.$eval('#crudit-menu-filter', (e) => { e.value = ''; });
await page.type('#crudit-menu-filter', 'remi');
assert.deepEqual(await labels(), ['Billing', 'Reminders']);
await page.$eval('#crudit-menu-filter', (e) => { e.value = ''; });
await page.type('#crudit-menu-filter', 'zzz');
assert.ok(await visible(page, '.crudit-sidebar__empty'));
await new Promise((r) => setTimeout(r, 700));
assert.equal(await page.$eval('#crudit-menu-filter-status', (e) => e.textContent), 'No item found');
ok('filter: child match, no result');
await page.$eval('#crudit-menu-filter', (e) => { e.value = ''; e.dispatchEvent(new Event('input', {bubbles: true})); });
assert.ok(!(await visible(page, '.crudit-sidebar__empty')));
assert.equal((await labels()).includes('Clients'), true);
assert.equal(await attr(page, '[data-menu-label=Billing] button', 'aria-expanded'), 'true');
assert.equal(await attr(page, '[data-menu-label=Stock] button', 'aria-expanded'), 'false', 'state restored after the filter');
assert.ok(await visible(page, '.crudit-nav__heading--category'));
ok('filter cleared: everything back, sub-menus restored');

// ---------- Rail by toggle, cookie, no jump
await page.click('.crudit-sidebar__collapse');
assert.equal(await attr(page, '.crudit-shell', 'data-sidebar'), 'collapsed');
await sidebarWidth(page, 72);
assert.ok(Math.abs((await box(page, '#crudit-sidebar')).w - 72) < 2, 'rail is 72 px');
assert.equal(await page.$eval('[data-2lenet--crudit-bundle--sidebar-target=toggleLabel]', (e) => e.textContent), 'Expand the menu');
assert.ok(!(await visible(page, '#crudit-menu-filter')), 'filter hidden in rail');
assert.ok((await box(page, '.crudit-nav__label')).w <= 1.5, 'labels hidden (sr-only) in rail');
assert.equal((await page.cookies()).find((c) => c.name === 'crudit_sidebar').value, 'collapsed');
assert.equal(await attr(page, '[data-menu-label=Billing] button', 'aria-expanded'), 'false', 'sub-menus closed in rail');
ok('toggle to rail, cookie written');
// badge: a dot of its tone instead, the badge stays for assistive technologies
assert.ok(await visible(page, '[data-menu-label=Clients] .crudit-nav__dot'), 'dot of the badge in the rail');
assert.ok((await box(page, '[data-menu-label=Clients] .crudit-nav__badge')).w <= 1.5, 'badge sr-only in the rail');
ok('badge as a dot in the rail');
assert.ok(await visible(page, '.crudit-brand__mark') && !(await visible(page, '.crudit-brand__full')), 'mark instead of the logo in the rail');
assert.equal(await page.$eval('.crudit-brand__mark', (e) => e.textContent.trim()), 'G');
ok('logo: the initial of the application in the rail');
await shot(page, 'desktop-rail');
await axe(page, 'desktop rail');

// tooltip on focus/hover of an item
await page.hover('[data-menu-label=Clients] a');
await settle(page);
const tip = await page.$eval('[data-menu-label=Clients] .crudit-ui-tooltip', (e) => { const s = getComputedStyle(e); const r = e.getBoundingClientRect(); return {vis: s.visibility, op: s.opacity, x: r.x, text: e.textContent}; });
assert.equal(tip.vis, 'visible'); assert.equal(tip.text, 'Clients · 12'); assert.ok(tip.x >= 64, `tooltip beside the rail (${tip.x})`);
ok('tooltip beside the rail on hover');
await shot(page, 'desktop-rail-tooltip');
await page.mouse.move(700, 400);

// flyout
await page.click('[data-menu-label=Billing] button');
assert.ok(await visible(page, '[data-menu-label=Billing] .crudit-nav__submenu'));
const fly = await box(page, '[data-menu-label=Billing] .crudit-nav__submenu');
assert.ok(fly.x >= 60 && fly.x <= 80, `flyout next to the rail (x=${fly.x})`);
const btn = await box(page, '[data-menu-label=Billing] button');
assert.ok(Math.abs(fly.y - btn.y) < 4, 'flyout aligned on the button');
await shot(page, 'desktop-rail-flyout');
await page.click('[data-menu-label=Stock] button');
assert.ok(!(await visible(page, '[data-menu-label=Billing] .crudit-nav__submenu')), 'one flyout at a time');
await page.keyboard.press('Escape');
assert.ok(!(await visible(page, '[data-menu-label=Stock] .crudit-nav__submenu')));
assert.equal(await page.evaluate(() => document.activeElement.closest('[data-menu-label]')?.dataset.menuLabel), 'Stock', 'focus back to the button');
await page.click('[data-menu-label=Stock] button');
await page.mouse.click(700, 500);
assert.ok(!(await visible(page, '[data-menu-label=Stock] .crudit-nav__submenu')), 'click outside closes');
await page.click('[data-menu-label=Stock] button');
await page.keyboard.press('Tab'); await page.keyboard.press('Tab'); await page.keyboard.press('Tab'); await page.keyboard.press('Tab');
assert.ok(!(await visible(page, '[data-menu-label=Stock] .crudit-nav__submenu')), 'focus leaving closes');
ok('rail flyouts: placement, one at a time, Escape, outside, focus');
await axe(page, 'desktop rail flyout');

// reload: arrives in rail, no jump
await page.goto(base + '/page/invoices', {waitUntil: 'domcontentloaded'});
assert.equal(await page.evaluate(() => document.querySelector('.crudit-shell').dataset.sidebar), 'collapsed');
assert.ok(Math.abs((await box(page, '#crudit-sidebar')).w - 72) < 2, 'rail on first paint');
ok('rail remembered (cookie read by the layout)');

// back to full
await page.waitForSelector('.crudit-sidebar__collapse');
await page.click('.crudit-sidebar__collapse');
await sidebarWidth(page, 240);
assert.ok(Math.abs((await box(page, '#crudit-sidebar')).w - 240) < 2);
assert.equal(await attr(page, '[data-menu-label=Billing] button', 'aria-expanded'), 'true', 'current sub-menu reopened');
ok('back to full menu');
await page.close();

// ---------- auto rail between 1024 and 1279, explicit expanded wins
page = await open(1100);
assert.ok(Math.abs((await box(page, '#crudit-sidebar')).w - 72) < 2, 'auto: rail at 1100');
assert.equal(await page.$eval('[data-2lenet--crudit-bundle--sidebar-target=toggleLabel]', (e) => e.textContent), 'Expand the menu');
await page.click('.crudit-sidebar__collapse');
await sidebarWidth(page, 240);
assert.ok(Math.abs((await box(page, '#crudit-sidebar')).w - 240) < 2, 'expanded at 1100');
assert.equal((await page.cookies()).find((c) => c.name === 'crudit_sidebar').value, 'expanded');
ok('auto = rail at 1100, expand by choice');
await page.setViewport({width: 1500, height: 800});
await new Promise((r) => setTimeout(r, 200));
await page.setViewport({width: 1100, height: 800});
await sidebarWidth(page, 240);
assert.ok(Math.abs((await box(page, '#crudit-sidebar')).w - 240) < 2, 'explicit choice kept when resizing');
await page.close();

// ---------- Mobile drawer
page = await open(390);
assert.ok(!(await visible(page, '#crudit-sidebar')), 'drawer closed');
assert.ok(await visible(page, '.crudit-header__burger'));
assert.equal(await attr(page, '.crudit-header__burger', 'aria-expanded'), 'false');
assert.ok((await box(page, '.crudit-shell__main')).x < 40, 'content takes the width');
await shot(page, 'mobile-closed');
await page.click('.crudit-header__burger');
await settle(page);
assert.ok(await visible(page, '#crudit-sidebar'));
assert.equal(await attr(page, '#crudit-sidebar', 'role'), 'dialog');
assert.equal(await attr(page, '#crudit-sidebar', 'aria-modal'), 'true');
assert.equal(await attr(page, '#crudit-sidebar', 'aria-label'), 'Main menu');
assert.equal(await attr(page, '.crudit-header__burger', 'aria-expanded'), 'true');
assert.equal(await page.$eval('.crudit-shell__body', (e) => e.inert), true);
assert.equal(await page.$eval('.crudit-skip-links', (e) => e.inert), true);
assert.ok(await page.evaluate(() => document.documentElement.classList.contains('crudit-has-drawer')));
assert.ok(await visible(page, '.crudit-shell__backdrop'));
assert.ok(await inAside(page), 'focus in the drawer');
assert.ok(await visible(page, '.crudit-sidebar__close'));
assert.ok(!(await visible(page, '.crudit-sidebar__collapse')), 'no reduce button in the drawer');
assert.ok((await box(page, '#crudit-sidebar')).w <= 320);
await shot(page, 'mobile-open');
await axe(page, 'mobile drawer open');
for (let i = 0; i < 40; i++) {
    await page.keyboard.press('Tab');
    assert.ok(await inAside(page), `focus trapped (Tab ${i})`);
}
for (let i = 0; i < 40; i++) {
    await page.keyboard.down('Shift'); await page.keyboard.press('Tab'); await page.keyboard.up('Shift');
    assert.ok(await inAside(page), `focus trapped (Shift+Tab ${i})`);
}
ok('drawer opens as modal dialog, inert page, focus trapped');
await page.keyboard.press('Escape');
await settle(page);
assert.ok(!(await visible(page, '#crudit-sidebar')));
assert.equal(await page.evaluate(() => document.activeElement.className.includes('crudit-header__burger')), true, 'focus back to burger');
assert.equal(await page.$eval('.crudit-shell__body', (e) => e.inert), false);
assert.equal(await attr(page, '#crudit-sidebar', 'role'), 'complementary');
assert.equal(await attr(page, '.crudit-header__burger', 'aria-expanded'), 'false');
ok('Escape closes, focus back to the burger');

await page.click('.crudit-header__burger');
await settle(page);
await page.mouse.click(380, 400);
await settle(page);
assert.ok(!(await visible(page, '#crudit-sidebar')), 'backdrop closes');
await page.click('.crudit-header__burger');
await settle(page);
await page.click('.crudit-sidebar__close');
await settle(page);
assert.ok(!(await visible(page, '#crudit-sidebar')), 'close button closes');
ok('backdrop and close button');

// skip link "Skip to menu" opens the drawer
await page.reload({waitUntil: 'networkidle0'});
await page.keyboard.press('Tab');
await page.keyboard.press('Tab');
assert.equal(await page.evaluate(() => document.activeElement.getAttribute('href')), '#main-nav');
await page.keyboard.press('Enter');
await settle(page);
assert.ok(await visible(page, '#crudit-sidebar'), 'skip link opens the drawer');
assert.equal(await active(page), 'main-nav');
ok('skip link to the menu opens the drawer, focus on the nav');

// filter in drawer + click on a link closes
await page.type('#crudit-menu-filter', 'client');
assert.deepEqual(await labels(), ['Clients']);
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('[data-menu-label=Clients] a')]);
assert.ok(page.url().endsWith('/client/'));
assert.ok(!(await visible(page, '#crudit-sidebar')), 'the drawer is closed on the page reached from it');
assert.equal(await page.$eval('.crudit-shell__body', (e) => e.inert), false);
ok('a link of the drawer navigates and the new page has a closed drawer');
await page.close();

// drawer open then widen to desktop
page = await open(390);
await page.click('.crudit-header__burger');
await settle(page);
await page.setViewport({width: 1500, height: 800});
await settle(page);
assert.equal(await page.$eval('.crudit-shell__body', (e) => e.inert), false, 'drawer closed on resize');
assert.equal(await page.evaluate(() => document.documentElement.classList.contains('crudit-has-drawer')), false);
assert.ok(await visible(page, '#crudit-sidebar'));
ok('resize to desktop closes the drawer');
await page.close();

// ---------- tablet 800: drawer too
page = await open(800);
assert.ok(!(await visible(page, '#crudit-sidebar')));
assert.ok(await visible(page, '.crudit-header__burger'));
await page.close();

// ---------- dark, RTL
page = await open(1440, '/page/invoices', {dark: true});
await shot(page, 'desktop-dark');
await axe(page, 'desktop dark');

// logo per theme, in CSS: the theme of the system, then the choice of the user (data-theme) over it
const logo = async (p) => ({light: await visible(p, '.crudit-brand__logo--light'), dark: await visible(p, '.crudit-brand__logo--dark')});
assert.deepEqual(await logo(page), {light: false, dark: true}, 'dark logo with the dark theme of the system');
await page.evaluate(() => { document.documentElement.dataset.theme = 'light'; });
assert.deepEqual(await logo(page), {light: true, dark: false}, 'light logo chosen by the user over the system');
page = await open(1440, '/page/invoices');
assert.deepEqual(await logo(page), {light: true, dark: false}, 'light logo with the light theme of the system');
await page.evaluate(() => { document.documentElement.dataset.theme = 'dark'; });
assert.deepEqual(await logo(page), {light: false, dark: true}, 'dark logo chosen by the user');
ok('logo per theme: system and choice of the user');

// footer: links and version, in a column on a phone, without horizontal scroll
page = await open(360, '/page/invoices');
assert.ok(await visible(page, '.crudit-footer__links') && await visible(page, '.crudit-footer__version'));
const links = await box(page, '.crudit-footer__nav');
const version = await box(page, '.crudit-footer__version');
assert.ok(version.y >= links.b, 'version under the links on a phone');
assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, 'no horizontal scroll');
await axe(page, 'phone footer');
page = await open(1440, '/page/invoices');
assert.ok(Math.abs((await box(page, '.crudit-footer__nav')).y - (await box(page, '.crudit-footer__version')).y) < 8, 'links and version on one line on desktop');
ok('footer: column on a phone, one line on desktop');
await page.close();
page = await open(1440, '/page/invoices', {cookie: 'collapsed', rtl: true});
await settle(page);
const rb = await box(page, '#crudit-sidebar');
assert.ok(rb.x > 1000, `rail on the right in RTL (${rb.x})`);
await page.click('[data-menu-label=Billing] button');
const rf = await box(page, '[data-menu-label=Billing] .crudit-nav__submenu');
assert.ok(rf.r < rb.x + 8 && rf.x < rb.x, `flyout on the left of the rail in RTL (${rf.x}-${rf.r} / ${rb.x})`);
await shot(page, 'desktop-rtl-flyout');
const tipx = await (async () => { await page.keyboard.press('Escape'); await page.hover('[data-menu-label=Clients] a'); await settle(page); return box(page, '[data-menu-label=Clients] .crudit-ui-tooltip'); })();
assert.ok(tipx.r <= rb.x + 8, `tooltip on the left in RTL (${tipx.r})`);
ok('RTL: rail, flyout and tooltip on the inline end side');
await page.close();
page = await open(390, '/', {rtl: true});
await page.click('.crudit-header__burger');
await settle(page);
const db = await box(page, '#crudit-sidebar');
assert.ok(db.r > 380, `drawer from the right in RTL (${db.r})`);
await shot(page, 'mobile-rtl-open');
await page.close();

// ---------- Defects found by the review of lot 2.1.b
// A flyout leaves no inline position behind when the menu stops being a rail
page = await open(1100, '/page/invoices');
await page.click('[data-menu-label=Billing] button');
await page.keyboard.press('Escape');
await page.click('.crudit-sidebar__collapse');
await settle(page);
assert.equal(await page.$eval('[data-menu-label=Billing] .crudit-nav__submenu', (e) => e.style.insetBlockStart), '', 'no inline position left');
const sub = await box(page, '[data-menu-label=Billing] .crudit-nav__submenu');
const parentButton = await box(page, '[data-menu-label=Billing] button');
assert.ok(sub.y >= parentButton.b - 2 && sub.y < parentButton.b + 12, `sub-menu in the flow under its button (${sub.y} / ${parentButton.b})`);
ok('a sub-menu is back in the flow when the rail is left');
await page.close();

// A page loaded in the rail does not show the sub-menu of the current page before it is opened
page = await open(1440, '/page/invoices', {cookie: 'collapsed'});
assert.ok(!(await visible(page, '[data-menu-label=Billing] .crudit-nav__submenu')), 'sub-menu hidden in the rail until opened');
assert.equal(await page.$eval('[data-menu-label=Billing] .crudit-nav__submenu', (e) => 'placed' in e.dataset), false);
ok('rail: the sub-menu of the current page is not shown before it is opened');

// The flyout follows its button when the menu scrolls
await page.setViewport({width: 1440, height: 420});
await page.click('[data-menu-label=Billing] button');
await page.$eval('#main-nav', (e) => { e.scrollTop = 60; });
await new Promise((r) => setTimeout(r, 200));
const flyBox = await box(page, '[data-menu-label=Billing] .crudit-nav__submenu');
const flyButton = await box(page, '[data-menu-label=Billing] button');
assert.ok(Math.abs(flyBox.y - flyButton.y) < 6 || flyBox.b <= 420, `the flyout follows its button (${flyBox.y} / ${flyButton.y})`);
ok('the flyout follows its button when the menu scrolls');
await page.close();

// Drawer: Escape and Tab work when the focus is on the page (click on a part that cannot take it)
page = await open(390);
await page.click('.crudit-header__burger');
await settle(page);
// The padding under the filter: nothing there can take the focus, which goes to <body>
const padding = await box(page, '.crudit-sidebar__filter');
await page.mouse.click(padding.x + padding.w / 2, padding.b - 2);
assert.equal(await page.evaluate(() => document.activeElement === document.body), true, 'the focus is on <body>');
await page.keyboard.press('Tab');
assert.ok(await inAside(page), 'Tab comes back into the drawer');
await page.mouse.click(padding.x + padding.w / 2, padding.b - 2);
assert.equal(await page.evaluate(() => document.activeElement === document.body), true);
await page.keyboard.press('Escape');
await settle(page);
assert.ok(!(await visible(page, '#crudit-sidebar')), 'Escape closes the drawer from <body>');
ok('drawer: Escape and Tab with the focus on the page');

// Drawer: a click that does not change the page leaves it open
await page.click('.crudit-header__burger');
await settle(page);
const opened = new Promise((resolve) => browser.once('targetcreated', resolve));
await page.keyboard.down('Control');
await page.click('[data-menu-label=Documentation] a');
await page.keyboard.up('Control');
await opened;
assert.ok(await visible(page, '#crudit-sidebar'), 'the drawer stays open on a click that opens a new tab');
for (const other of await browser.pages()) {
    if (other !== page && other.url() !== 'about:blank') { await other.close(); } else if (other !== page) { await other.close(); }
}
ok('drawer: a link that opens a new tab leaves it open');

// Drawer: a resize that closes it leaves the focus in the menu
await page.focus('.crudit-sidebar__close');
await page.setViewport({width: 1500, height: 800});
await settle(page);
assert.equal(await active(page), 'main-nav', 'the focus goes to the menu, not to <body>');
ok('a resize that closes the drawer keeps the focus');
await page.close();

// A project that empties the blocks menu_filter and menu_toggle: the controller works without them
const errorsBefore = errors.length;
page = await open(1440, '/overrides', {cookie: 'collapsed'});
await page.click('[data-menu-label=Billing] button');
assert.ok(await visible(page, '[data-menu-label=Billing] .crudit-nav__submenu'));
await page.setViewport({width: 390, height: 800});
await settle(page);
await page.click('.crudit-header__burger');
await settle(page);
assert.ok(await visible(page, '#crudit-sidebar'));
await page.keyboard.press('Escape');
await settle(page);
assert.ok(!(await visible(page, '#crudit-sidebar')));
assert.equal(errors.length, errorsBefore, 'no error without the blocks menu_filter and menu_toggle');
ok('the menu works without the filter and the button that reduces it');
await page.close();

// ---------- Points of the second review
// Drawer: everything outside the shell is inert (back to the top, skip links, toasts), the backdrop still closes it
page = await open(390);
await page.evaluate(() => { window.scrollTo(0, 800); });
await new Promise((r) => setTimeout(r, 200));
await page.click('.crudit-header__burger');
await settle(page);
const outside = await page.evaluate(() => [...document.body.children]
    .filter((e) => !['SCRIPT', 'STYLE', 'LINK', 'TEMPLATE'].includes(e.tagName) && !e.contains(document.querySelector('.crudit-shell')) && e !== document.querySelector('.crudit-shell'))
    .map((e) => `${e.className || e.tagName}:${e.inert}`));
assert.ok(outside.length >= 2 && outside.every((x) => x.endsWith(':true')), `everything outside the shell is inert (${outside})`);
assert.equal(await page.$eval('.crudit-shell__backdrop', (e) => e.inert), false, 'the backdrop is not inert');
assert.equal(await page.$eval('#crudit-sidebar', (e) => e.inert), false);
await page.keyboard.press('Escape');
await settle(page);
const left = await page.evaluate(() => [...document.querySelectorAll('[inert]')].length);
assert.equal(left, 0, 'nothing stays inert once the drawer is closed');
ok('drawer: everything around the menu is inert, and released on close');

// Escape in the filter empties it and nothing else
await page.click('.crudit-header__burger');
await settle(page);
await page.type('#crudit-menu-filter', 'inter');
await page.keyboard.press('Escape');
await new Promise((r) => setTimeout(r, 200));
assert.equal(await page.$eval('#crudit-menu-filter', (e) => e.value), '', 'Escape empties the filter');
assert.ok(await visible(page, '#crudit-sidebar'), 'and the drawer stays open');
assert.ok((await labels()).includes('Clients'), 'the menu is whole again');
await page.keyboard.press('Escape');
await settle(page);
assert.ok(!(await visible(page, '#crudit-sidebar')), 'a second Escape closes the drawer');
ok('Escape in the filter empties it, a second one closes the drawer');
await page.close();

// Back to `auto`: the cookie goes when the choice is what `auto` gives at this width
page = await open(1440);
await page.click('.crudit-sidebar__collapse');
assert.equal((await page.cookies()).find((c) => c.name === 'crudit_sidebar')?.value, 'collapsed');
await page.click('.crudit-sidebar__collapse');
assert.equal((await page.cookies()).find((c) => c.name === 'crudit_sidebar'), undefined, 'the cookie is gone');
assert.equal(await attr(page, '.crudit-shell', 'data-sidebar'), 'auto');
await page.setViewport({width: 1100, height: 800});
await sidebarWidth(page, 72);
assert.ok(Math.abs((await box(page, '#crudit-sidebar')).w - 72) < 2, 'auto again: the rail at 1100');
ok('the menu goes back to auto when the choice is what auto gives');
await page.close();

// ---------- Header and user menu (lot 2.2.a)
const userButton = '#crudit-user-menu > button';
const userPanel = '#crudit-user-menu-panel';
for (const width of [360, 768, 1280]) {
    page = await open(width, '/', {user: 'alice'});
    assert.equal(await visible(page, '.crudit-header__brand .crudit-brand'), width < 1024, `logo in the header below 1024 px (${width})`);
    assert.equal(await visible(page, '.crudit-header__user-name'), width >= 1024, `name of the user shown from 1024 px (${width})`);
    await page.click(userButton);
    assert.equal(await attr(page, userButton, 'aria-expanded'), 'true');
    assert.ok(await visible(page, userPanel));
    const panel = await box(page, userPanel);
    assert.ok(panel.x >= 0 && panel.r <= width, `the panel stays on the screen (${width})`);
    await shot(page, `header-user-menu-${width}`);
    await axe(page, `user menu open at ${width}`);
    await page.keyboard.press('Escape');
    assert.equal(await attr(page, userButton, 'aria-expanded'), 'false');
    assert.ok(await page.$eval(userButton, (e) => e === document.activeElement), 'Escape gives the focus back to the button');
    ok(`user menu at ${width}: opens, Escape closes it`);
}

// Appearance: the choice stays in the open panel, puts data-theme and the cookie, aria-pressed follows
page = await open(1280, '/', {user: 'alice'});
await page.click(userButton);
await page.click(`${userPanel} button[value=dark]`);
assert.equal(await attr(page, 'html', 'data-theme'), 'dark');
assert.equal(await attr(page, `${userPanel} button[value=dark]`, 'aria-pressed'), 'true');
assert.equal(await attr(page, `${userPanel} button[value=system]`, 'aria-pressed'), 'false');
assert.ok(await visible(page, userPanel), 'the panel stays open');
assert.equal((await page.cookies()).find((c) => c.name === 'crudit_ui_theme')?.value, 'dark');
await axe(page, 'user menu open, dark');
await page.click(`${userPanel} button[value=system]`);
assert.equal(await attr(page, 'html', 'data-theme'), null);
ok('appearance chosen in the user menu');

// Impersonation: the banner above the bar, the flag on the avatar, the way back
page = await open(1280, '/?_switch_user=bob', {user: 'alice'});
assert.ok(await visible(page, '.crudit-impersonation'));
assert.equal(await page.$eval('.crudit-impersonation strong', (e) => e.textContent), 'Bob Durand');
assert.ok(await visible(page, '.crudit-header__user .crudit-ui-avatar--impersonated'));
await shot(page, 'header-impersonation');
await axe(page, 'impersonation');
page = await open(360, '/?_switch_user=bob', {user: 'alice', rtl: true});
await page.click(userButton);
await axe(page, 'impersonation, user menu open, RTL at 360');
await page.keyboard.press('Escape');
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('.crudit-impersonation a')]);
assert.equal(await page.$('.crudit-impersonation'), null);
ok('impersonation: banner, flag, way back');

// Logout: a POST from the menu
page = await open(1280, '/', {user: 'alice'});
await page.click(userButton);
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click(`${userPanel} button[type=submit]`)]);
assert.equal(await page.$(userButton), null, 'signed out');
ok('logout by POST');
await page.close();

// ---------- Header of the CRUD pages (lot 2.2.b): PageHeader, breadcrumb from the menu. axe on the header only: the
// lists and the forms are reworked in phase 3
async function axeHeader(page, label) {
    await page.evaluate(axeSource);
    const result = await page.evaluate(() => axe.run({include: [['.crudit-ui-page-header']]}, {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice']}));
    assert.deepEqual(result.violations.map((x) => `${x.id}: ${x.nodes.map((n) => n.target.join(' ')).join(' | ')}`), [], `axe ${label}`);
    ok(`axe ${label}`);
}
const crumbs = (page) => page.$$eval('nav.crudit-ui-breadcrumb li', (lis) => lis.filter((li) => li.offsetParent !== null).map((li) => li.textContent.trim()));
page = await open(1280, '/client/');
assert.deepEqual(await crumbs(page), ['Home', 'Operations', 'Clients']);
await page.goto(base + await page.$eval('main a[href*="/client/show/"]', (a) => a.getAttribute('href')), {waitUntil: 'networkidle0'});
const record = await page.$eval('h1#page-title', (e) => e.textContent.trim());
assert.deepEqual(await crumbs(page), ['Home', 'Operations', 'Clients', record]);
assert.equal(await page.$$eval('h1', (h) => h.length), 1, 'one h1');
await shot(page, 'crud-show-1280');
await axeHeader(page, 'header of a record at 1280');
await page.setViewport({width: 360, height: 800});
await new Promise((r) => setTimeout(r, 200));
assert.deepEqual(await crumbs(page), ['Clients'], 'on a phone, the link "← parent" only');
assert.ok(await visible(page, 'nav.crudit-ui-breadcrumb .crudit-ui-breadcrumb__back'));
await shot(page, 'crud-show-360');
await axeHeader(page, 'header of a record at 360');
ok('header of a CRUD page: breadcrumb from the menu, one h1, "← parent" on a phone');
await page.close();

// ---------- Actions of the header and of the lines (lot 3.1.a): one primary action, the secondary ones in the menu
// "More" below md, the confirmation and the modals opened by commandfor, the focus given back, axe on the header,
// the dialogs and the actions of the lines
const shown = (page, sel) => page.$$eval(sel, (els) => els.filter((e) => e.getClientRects().length > 0 && getComputedStyle(e).visibility !== 'hidden').length);
const openDialog = (page) => page.evaluate(() => [...document.querySelectorAll('dialog')].find((d) => d.open)?.id ?? null);
const focusText = (page) => page.evaluate(() => (document.activeElement?.textContent ?? '').trim());
async function axeOn(page, selector, label) {
    // Once the animations are over (a dialog that opens): during them the colours are blended with the backdrop, and
    // axe reads a contrast that is not the final one.
    await settle(page);
    await page.evaluate(axeSource);
    const result = await page.evaluate((selector) => axe.run({include: [[selector]]}, {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice']}), selector);
    assert.deepEqual(result.violations.map((x) => `${x.id}: ${x.nodes.map((n) => n.target.join(' ')).join(' | ')}`), [], `axe ${label}`);
    ok(`axe ${label}`);
}
for (const [width, options] of [[1600, {}], [1280, {dark: true}], [768, {rtl: true}], [360, {}], [360, {dark: true, rtl: true}]]) {
    page = await open(width, '/client/', options);
    const label = `${width}${options.dark ? ' dark' : ''}${options.rtl ? ' rtl' : ''}`;
    assert.equal(await shown(page, '.crudit-page-actions .crudit-ui-btn--primary'), 1, `one primary action at ${label}`);
    // From md the secondary ones are in the bar; below, in the menu "More"
    assert.equal(await shown(page, '.crudit-page-actions__secondary'), width >= 768 ? 4 : 0, `secondary actions at ${label}`);
    await page.click('.crudit-page-actions__more > button');
    assert.equal(await shown(page, '.crudit-page-actions__more .crudit-page-actions__mobile-only'), width >= 768 ? 0 : 4, `the secondary ones in "More" at ${label}`);
    await axeOn(page, '.crudit-ui-page-header', `header with the menu "More" open at ${label}`);
    await page.keyboard.press('Escape');
    assert.equal(await attr(page, '.crudit-page-actions__more > button', 'aria-expanded'), 'false');
    assert.equal(await page.evaluate(() => document.activeElement === document.querySelector('.crudit-page-actions__more > button')), true, `Escape gives the focus back to "More" at ${label}`);
    if (width === 360) {
        const bar = await box(page, '.crudit-page-actions');
        const primary = await box(page, '.crudit-page-actions__primary');
        assert.ok(primary.w > bar.w / 2, 'on a phone, the primary action takes the room of the bar');
    }
    await shot(page, `actions-${label.replace(/ /g, '-')}`);
}
ok('bar of the actions from 360 to 1600 px, light and dark, left and right to left');

// The lines: the edition, then the menu of the line
page = await open(1280, '/client/');
assert.equal(await attr(page, 'tbody tr:first-child .crudit-ui-table__action-list a.crudit-ui-icon-btn', 'href').then((h) => /\/client\/edit\/\d+$/.test(h)), true);
await page.click('tbody tr:first-child .crudit-ui-table__action-list .crudit-ui-dropdown > button');
assert.equal(await shown(page, 'tbody tr:first-child .crudit-ui-table__action-list .crudit-ui-menu__item'), 4, 'Deactivate (no icon), Show, Delete (disabled), Note');
await axeOn(page, 'tbody tr:first-child .crudit-ui-table__action-list', 'actions of a line, menu open');
await page.keyboard.press('Escape');
ok('actions of a line: the edition, the menu named after the line');

// A confirmation that names the element (an inactive client: Client 04), then a modal with fields, from the record
await page.goto(base + await page.$$eval('tbody tr', (trs) => trs.find((tr) => tr.textContent.includes('Client 04')).querySelector('a[href*="/client/show/"]').getAttribute('href')), {waitUntil: 'networkidle0'});
await page.click('.crudit-page-actions__secondary button.crudit-ui-btn--danger');
await new Promise((r) => setTimeout(r, 200));
assert.equal(await openDialog(page), 'crudit-confirm');
assert.equal(await page.$eval('#crudit-confirm-title', (e) => e.textContent.trim()), 'Delete “Client 04”?');
assert.equal(await focusText(page), 'Cancel', 'the focus on Cancel, the least destructive choice');
await axeOn(page, '#crudit-confirm', 'confirmation');
await page.keyboard.press('Escape');
await new Promise((r) => setTimeout(r, 200));
assert.equal(await openDialog(page), null);
assert.equal(await focusText(page), 'Delete', 'the focus back on the action');
await page.click('.crudit-page-actions__secondary button[commandfor^="modal_"]');
await new Promise((r) => setTimeout(r, 200));
const modal = await openDialog(page);
assert.ok(modal?.startsWith('modal_'), 'the modal of the action is open');
assert.equal(await page.evaluate(() => document.activeElement?.name), 'subject', 'the focus on the first field');
await axeOn(page, `#${modal}`, 'modal with fields');
await page.keyboard.press('Escape');
await new Promise((r) => setTimeout(r, 200));
assert.equal(await focusText(page), 'Send an e-mail', 'the focus back on the action');
ok('confirmation that names the element and modal with fields, the focus given back');
await page.close();

// ---------- Focus of the title after a visit of Turbo (lot 2.2.b): turbo:load simulated, the application has no Turbo
page = await open(1280, '/page/invoices');
// Turbo gives the timing of a visit in turbo:load, none on the first load
const load = (url, visit = true) => page.evaluate((url, visit) => {
    document.activeElement?.blur();
    if (url) {
        history.pushState({}, '', url);
    }
    document.dispatchEvent(new CustomEvent('turbo:load', {detail: {url: location.href, timing: visit ? {visitStart: 1} : {}}}));
    return document.activeElement?.id ?? '';
}, url, visit);
assert.equal(await load(null, false), '', 'first load: the focus does not move');
assert.equal(await load('/page/credits'), 'page-title', 'a visit: the focus on the title');
assert.equal(await load(null), '', 'a refresh of the same address: the focus does not move');
assert.equal(await load('/page/items#content'), '', 'an address with an anchor: the focus does not move');
assert.equal(await load('/page/movements', false), '', 'no timing (not a visit): the focus does not move');
// As Turbo does: the address changes, <body> is replaced (its controllers connect again), then turbo:load
const focusAfterSwap = await page.evaluate(async () => {
    document.activeElement?.blur();
    history.pushState({}, '', '/page/reminders');
    document.body.replaceWith(document.body.cloneNode(true));
    await new Promise((r) => setTimeout(r, 100));
    document.dispatchEvent(new CustomEvent('turbo:load', {detail: {url: location.href, timing: {visitStart: 1}}}));
    return document.activeElement?.id ?? '';
});
assert.equal(focusAfterSwap, 'page-title', 'a visit that replaces <body>: the focus on the title');
ok('focus of the title after a visit of Turbo');

// ---------- Global search (lot 2.2.c): the button of the header, the command palette, Ctrl K
const palette = (page) => page.evaluate(() => {
    const dialog = document.getElementById('crudit-search');
    const input = dialog.querySelector('input');

    return {
        open: dialog.open,
        focus: document.activeElement === input,
        groups: Array.from(dialog.querySelectorAll('[role=group]')).map((g) => g.querySelector('p').textContent),
        options: Array.from(dialog.querySelectorAll('[role=option]')).map((o) => `${o.querySelector('.crudit-ui-palette__title').textContent} / ${o.querySelector('.crudit-ui-palette__subtitle')?.textContent ?? ''}`),
        message: dialog.querySelector('.crudit-ui-palette__message').hidden ? null : dialog.querySelector('.crudit-ui-palette__message').textContent,
    };
});
const results = (page) => page.waitForFunction(() => document.querySelector('#crudit-search [role=option], #crudit-search .crudit-ui-palette__message:not([hidden])'), {timeout: 3000});

page = await open(1440, '/page/invoices');
assert.ok(await visible(page, '.crudit-search-trigger'), 'the button of the search is shown');
assert.ok(await visible(page, '.crudit-search-trigger__hint') && await visible(page, '.crudit-search-trigger__kbd'), 'the hint and the keys on desktop');
assert.equal(await attr(page, '.crudit-search-trigger', 'aria-keyshortcuts'), 'Control+K Meta+K');
// The header is checked as a whole on the other pages (lot 2.2.a): here, the button alone.
await page.evaluate(axeSource);
assert.deepEqual(await page.evaluate(async () => (await axe.run('.crudit-search-trigger', {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice']})).violations.map((v) => v.id)), [], 'axe of the button of the search');
ok('the button of the search is in the header, accessible');
assert.equal(await page.$eval('.crudit-search-trigger__kbd', (e) => e.textContent), 'Ctrl K', 'the shortcut is Ctrl K outside a Mac');

// The shortcut shown is the one of the platform; without a palette on the page the button hides itself.
page = await open(1440, '/page/invoices', {platform: 'MacIntel'});
assert.equal(await page.$eval('.crudit-search-trigger__kbd', (e) => e.textContent), '⌘ K', 'the shortcut is not ⌘ K on a Mac');
await page.evaluate(() => {
    const button = document.querySelector('.crudit-search-trigger');
    document.getElementById('crudit-search').remove();
    button.replaceWith(button.cloneNode(true));
});
await page.waitForFunction(() => document.querySelector('.crudit-search-trigger').hidden, {timeout: 2000});
ok('⌘ K on a Mac, the button hidden without a palette');
page = await open(1440, '/page/invoices');

// Ctrl K from any page opens it, the focus in the field; the clients and the screens; Enter opens the active one.
await page.keyboard.down('Control');
await page.keyboard.press('k');
await page.keyboard.up('Control');
assert.deepEqual((({open, focus}) => ({open, focus}))(await palette(page)), {open: true, focus: true}, 'Ctrl K opens the palette');
await page.keyboard.type('cli');
await results(page);
assert.deepEqual((await palette(page)).groups, ['Clients', 'Screens'], 'the clients, then the screens');
assert.ok((await palette(page)).options.includes('Clients / Screen'), 'the screen "Clients", whose second line is the name of the kind');
assert.equal((await palette(page)).options.length, 11, 'ten clients at most (the default limit), then the screen');
await page.keyboard.down('Control');
await page.keyboard.press('a');
await page.keyboard.up('Control');
await page.keyboard.type('client2');
await results(page);
let found = await palette(page);
assert.deepEqual(found.groups, ['Clients'], 'the clients found by their name');
assert.ok(found.options.includes('Client 20 / client20@example.org'), 'the e-mail is the second line');
ok('Ctrl K: the palette finds the clients and the screens');
await page.evaluate(axeSource);
assert.deepEqual(await page.evaluate(async () => (await axe.run('#crudit-search', {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice']})).violations.map((v) => `${v.id} ${v.nodes.map((n) => n.target.join(' ')).join(' | ')}`)), [], 'axe of the palette');
ok('axe of the palette with results');
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.keyboard.press('Enter')]);
assert.match(page.url(), /\/client\/show\/\d+$/, 'Enter opens the active result');
assert.match(await page.$eval('main', (e) => e.textContent), /Client 2\d/);
ok('Enter opens the client');

// The button opens it; on the screens, the sub-menu is the second line; the link of a role nobody has is not found.
await page.click('.crudit-search-trigger');
assert.equal((await palette(page)).open, true, 'the button opens the palette');
await page.keyboard.type('invoices');
await results(page);
assert.deepEqual(await palette(page).then((p) => [p.groups, p.options]), [['Screens'], ['Invoices / Billing']]);
await page.keyboard.down('Control');
await page.keyboard.press('a');
await page.keyboard.up('Control');
await page.keyboard.type('secret');
await results(page);
assert.equal((await palette(page)).message, 'No result for “secret”.', 'a screen that the user may not see is not found');
await page.keyboard.press('Escape');
assert.equal((await palette(page)).open, false);
assert.equal(await page.evaluate(() => document.activeElement.className.includes('crudit-search-trigger')), true, 'the focus goes back to the button');
ok('the button, the screens, a refused screen, Escape');
await page.close();

// Ctrl K and the button in right to left, on a phone (an icon, the label for assistive technologies only) and in dark
page = await open(360, '/page/invoices');
const trigger = await box(page, '.crudit-search-trigger');
assert.ok(trigger.w < 50 && trigger.w >= 28, `the button is an icon on a phone (${trigger.w} px)`);
assert.ok(!(await visible(page, '.crudit-search-trigger__hint')));
assert.match(await page.$eval('.crudit-search-trigger', (e) => e.textContent), /Search/, 'the label stays for assistive technologies');
await page.click('.crudit-search-trigger');
const full = await page.$eval('#crudit-search', (e) => { const r = e.getBoundingClientRect(); return [Math.round(r.width), Math.round(r.height)]; });
assert.deepEqual(full, [360, 800], 'the palette is the whole screen on a phone');
await page.keyboard.type('client2');
await results(page);
assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth), true, 'no horizontal scroll');
ok('the search on a phone');
await page.close();

page = await open(1440, '/page/invoices', {rtl: true, dark: true});
await page.click('.crudit-search-trigger');
await page.keyboard.type('client2');
await results(page);
assert.equal(await page.$eval('#crudit-search .crudit-ui-palette__field', (e) => getComputedStyle(e).direction), 'rtl');
await page.evaluate(axeSource);
assert.deepEqual(await page.evaluate(async () => (await axe.run('#crudit-search', {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice']})).violations.map((v) => v.id)), [], 'axe of the palette, dark, right to left');
ok('the palette in dark and right to left');
await page.close();

// A server error is said, not swallowed
page = await open(1440, '/page/invoices');
await page.setRequestInterception(true);
page.on('request', (request) => request.url().includes('/crudit/search') ? request.respond({status: 500, body: 'x'}) : request.continue());
await page.keyboard.down('Control');
await page.keyboard.press('k');
await page.keyboard.up('Control');
await page.keyboard.type('client');
await results(page);
assert.equal((await palette(page)).message, 'The search failed. Try again.');
errors.splice(0, errors.length, ...errors.filter((e) => !e.includes('status of 500')));
ok('a server error is shown');
await page.close();

// ---------- Table of the list (lot 3.1.b): the Table of CruditUiBundle fed by the ListBrick. axe on the table only: the
// header of the page is reworked in 3.1.a.
const axeTable = async (page, label) => {
    await page.evaluate(axeSource);
    assert.deepEqual(await page.evaluate(async () => (await axe.run('.crudit-ui-table', {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice']})).violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(' | ')}`)), [], `axe table ${label}`);
    ok(`axe table ${label}`);
};

page = await open(1440, '/client/');
assert.ok(await visible(page, '.crudit-ui-table__table') && !(await visible(page, '.crudit-ui-table__cards')), 'a table from md up, not the cards');
assert.equal(await attr(page, 'th[aria-sort=ascending]', 'aria-sort'), 'ascending', 'sorted by name by default');
assert.ok((await page.$$eval('.crudit-ui-table__row', (rows) => rows.length)) > 0, 'rows');
ok('list: table, sorted column');
await shot(page, 'list-desktop');
await axeTable(page, 'desktop');

// sort by the heading: a link, the order is in the address and the column says it
await page.click('.crudit-ui-table__sort[href*="client_sort=email"]');
await page.waitForFunction(() => location.search.includes('client_sort=email'));
await new Promise((r) => setTimeout(r, 600));
assert.equal(await page.$eval('th[aria-sort=ascending] .crudit-ui-table__sort', (e) => e.textContent.trim().toLowerCase().includes('email')), true);
ok('list: sort by a heading');

// the edit action is the icon, named after the row; the others are in the menu "More actions for …"
// an active client cannot be deleted (disabled): the row of an inactive one
const row = '#' + await page.$$eval('.crudit-ui-table__row', (rows) => rows.find((r) => r.querySelector('[data-confirm-link]')).id);
const rowLabel = await page.$eval(`${row} th[scope=row]`, (e) => e.textContent.trim());
assert.ok((await page.$eval(`${row} .crudit-ui-table__action-list a`, (e) => e.textContent)).includes(rowLabel), 'icon named after the row');
await page.click(`${row} .crudit-ui-dropdown > button`);
await settle(page);
assert.ok(await visible(page, `${row} .crudit-ui-menu__item[data-confirm-link]`), 'the menu has the deletion');
await axeTable(page, 'menu open');
await shot(page, 'list-row-menu');

// the deletion asks for a confirmation that names the action, Cancel closes it, focus back on the button of the row
await page.click(`${row} .crudit-ui-menu__item[data-confirm-link]`);
await settle(page);
assert.equal(await page.$eval('#crudit-confirm', (e) => e.open), true, 'the confirmation opens');
await page.keyboard.press('Escape');
await settle(page);
assert.equal(await page.$eval('#crudit-confirm', (e) => e.open), false, 'Escape closes it');
ok('list: row actions, confirmation of the deletion');

// keyboard: Tab reaches the sort links, the edit link and the menu button, in the order of the page
await page.focus('.crudit-ui-table__sort');
await page.keyboard.press('Tab');
assert.ok(await page.evaluate(() => document.activeElement.classList.contains('crudit-ui-table__sort')), 'Tab goes to the next heading');
ok('list: keyboard');

// ---------- Selection and batch actions (lot 3.1.c): the SelectionBar of CruditUiBundle under the table
page = await open(1440, '/client/');
assert.ok(!(await visible(page, '.crudit-ui-selection-bar')), 'no bar while no row is checked');
await page.click('tbody tr:first-child input[type=checkbox]');
assert.ok(await visible(page, '.crudit-ui-selection-bar'), 'the bar shows when a row is checked');
assert.match(await page.$eval('.crudit-ui-selection-bar__count', (e) => e.textContent.trim()), /^1 row selected/);
assert.match(await page.$eval('[data-controller~="2lenet--crudit-ui-bundle--selection-bar"] [role=status]', (e) => e.textContent), /1 row selected/, 'announced in a live region');
await page.click('thead input[type=checkbox]');
const pageRows = await page.$$eval('tbody tr.crudit-ui-table__row', (rows) => rows.length);
assert.match(await page.$eval('.crudit-ui-selection-bar__count', (e) => e.textContent.trim()), new RegExp(`^${pageRows} rows selected`));
assert.ok((await page.$eval('thead input[type=checkbox]', (e) => e.checked)), 'the box of the header is checked');
await axeOn(page, '.crudit-ui-selection-bar', 'selection bar');
ok('list: selection bar');
// a link of the bar receives the ids and all_page
const ids = await page.$$eval('tbody tr.crudit-ui-table__row input[type=checkbox]', (boxes) => boxes.map((b) => b.dataset.id).join(','));
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('.crudit-ui-selection-bar__actions a[href*="/client/batch/export"]')]);
assert.ok((await page.$eval('body', (b) => b.textContent)).includes(`export: ids=${ids} all_page=0`), 'the link received the selection');
ok('batch action: link');
// the form of an action, in a dialog: the fields of the selection are filled when it is sent
page = await open(1440, '/client/');
await page.click('tbody tr:nth-child(2) input[type=checkbox]');
const second = await page.$eval('tbody tr:nth-child(2) input[type=checkbox]', (b) => b.dataset.id);
await (await page.$$('.crudit-ui-selection-bar__actions button[command=show-modal]')).reduce(async (done, b) => (await done) || ((await b.evaluate((e) => e.textContent.includes('Change the status'))) ? b.click() || true : false), Promise.resolve(false));
assert.ok(await visible(page, 'dialog[open] form'), 'the form opens in a dialog');
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('dialog[open] button[type=submit]')]);
assert.ok((await page.$eval('body', (b) => b.textContent)).includes(`status: ids=${second} all_page=0`), 'the form received the selection');
ok('batch action: form in a dialog');
// a free modal (setModal) receives the selection when it opens
page = await open(1440, '/client/');
await page.click('tbody tr:first-child input[type=checkbox]');
const first = await page.$eval('tbody tr:first-child input[type=checkbox]', (b) => b.dataset.id);
await (await page.$$('.crudit-ui-selection-bar__actions button[command=show-modal]')).reduce(async (done, b) => (await done) || ((await b.evaluate((e) => e.textContent.includes('Send a message'))) ? b.click() || true : false), Promise.resolve(false));
assert.ok(await visible(page, 'dialog[open] textarea'), 'the free modal opens');
await page.type('dialog[open] textarea', 'hello');
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('dialog[open] button[type=submit]')]);
assert.ok((await page.$eval('body', (b) => b.textContent)).includes(`message: ids=${first} all_page=0`), 'the free modal received the selection');
ok('batch action: free modal');
// the selection follows the pager, and only the pager
page = await open(1440, '/client/?client_limit=10');
await page.click('tbody tr:nth-child(2) input[type=checkbox]');
const kept = await page.$eval('tbody tr:nth-child(2) input[type=checkbox]', (b) => b.dataset.id);
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('nav[aria-label] a[href*="client_offset=10"]')]);
assert.ok(!(await page.$eval('tbody tr:first-child input[type=checkbox]', (e) => e.checked)), 'the rows of the page 2 are not checked');
assert.match(await page.$eval('.crudit-ui-selection-bar__count', (e) => e.textContent.trim()), /^1 row selected/, 'the row of the page 1 is still counted');
await page.click('tbody tr:nth-child(3) input[type=checkbox]');
assert.match(await page.$eval('.crudit-ui-selection-bar__count', (e) => e.textContent.trim()), /^2 rows selected/);
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.evaluate(() => [...document.querySelectorAll('.crudit-ui-pager nav a[href*="client_offset=0"]')].find((a) => a.offsetParent).click())]);
assert.ok(await page.$eval('tbody tr:nth-child(2) input[type=checkbox]', (e) => e.checked), 'back on the page 1, the row is checked');
assert.equal(await page.$eval('tbody tr:nth-child(2) input[type=checkbox]', (b) => b.dataset.id), kept);
await page.reload({waitUntil: 'networkidle0'});
assert.ok(!(await visible(page, '.crudit-ui-selection-bar')), 'a reload starts from an empty selection');
await page.click('tbody tr:nth-child(2) input[type=checkbox]');
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('.crudit-ui-table__sort[href*="client_sort=email"]')]);
assert.ok(!(await visible(page, '.crudit-ui-selection-bar')), 'a sort starts from an empty selection');
ok('selection: follows the pager only');
// ---------- Pager of the list (lot 3.1.d): the Pager of CruditUiBundle at the bottom of the table, the parameters of the 1.x
page = await open(1440, '/client/?client_limit=10&client_offset=10');
assert.equal(await page.$$eval('.crudit-ui-pager', (e) => e.length), 1, 'the pager is not alone');
assert.equal(await page.$eval('.crudit-ui-pager__summary', (e) => e.textContent.trim()), '11 to 20 of 25 clients', 'pager: summary');
assert.equal(await page.$eval('.crudit-ui-pager nav [aria-current]', (e) => e.textContent.replace(/\s+/g, ' ').trim()), 'Page 2', 'pager: current page');
assert.equal(await page.evaluate(() => Boolean(document.querySelector('.crudit-ui-table__table').compareDocumentPosition(document.querySelector('.crudit-ui-pager')) & Node.DOCUMENT_POSITION_FOLLOWING)), true, 'pager: under the table, not above it');
await page.evaluate(axeSource);
assert.deepEqual(await page.evaluate(async () => (await axe.run('.crudit-ui-pager', {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice']})).violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(' | ')}`)), [], 'axe pager');
ok('pager: summary, current page, under the table, axe');
// a link keeps the sort and the rows per page
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('.crudit-ui-pager nav a[href*="client_offset=20"]')]);
assert.equal(await page.$eval('.crudit-ui-pager__summary', (e) => e.textContent.trim()), '21 to 25 of 25 clients');
assert.equal(await page.$$eval('.crudit-ui-pager nav [aria-disabled="true"]', (e) => e.filter((n) => n.tagName === 'A').length), 0, 'pager: a dead link on the last page');
assert.equal(await page.$$eval('tbody tr.crudit-ui-table__row', (e) => e.length), 5);
// the rows per page: the list reloads at once, back to the first page (the server keeps the page in the session)
await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.$eval('.crudit-ui-pager select', (select) => { select.value = '30'; select.dispatchEvent(new Event('change', {bubbles: true})); })]);
assert.equal(await page.$eval('.crudit-ui-pager__summary', (e) => e.textContent.trim()), '1 to 25 of 25 clients', 'pager: the rows per page do not go back to the first page');
assert.equal(await page.$eval('.crudit-ui-pager select', (e) => e.value), '30');
// a page past the last one goes to the last page
page = await open(1440, '/client/?client_limit=10&client_offset=500');
assert.equal(await page.$eval('.crudit-ui-pager__summary', (e) => e.textContent.trim()), '21 to 25 of 25 clients', 'pager: a page past the end does not go to the last page');
// on a phone: previous, "1 / 3", next, and the page does not scroll sideways
page = await open(360, '/client/?client_limit=10');
assert.equal(await page.$eval('.crudit-ui-pager__status', (e) => e.textContent.trim()), '1 / 3');
assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, 'pager: the page scrolls sideways on a phone');
ok('pager: link, rows per page, page past the end, phone');
// Clear
page = await open(1440, '/client/');
await page.click('tbody tr:first-child input[type=checkbox]');
await page.click('[data-2lenet--crudit-ui-bundle--selection-bar-target=clearButton]');
assert.ok(!(await visible(page, '.crudit-ui-selection-bar')), 'the bar is hidden once the selection is cleared');
assert.ok(!(await page.$eval('tbody tr:first-child input[type=checkbox]', (e) => e.checked)), 'the row is unchecked');
ok('selection: clear');

// small screen: the cards (same fields), no table, no horizontal scroll of the page
page = await open(360, '/client/');
assert.ok(!(await visible(page, '.crudit-ui-table__table')) && await visible(page, '.crudit-ui-table__card'), 'cards below md');
assert.ok((await page.$$eval('.crudit-ui-table__card', (c) => c[0].querySelectorAll('dt').length)) >= 3, 'the cards show the fields of the columns');
assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth), true, 'no horizontal scroll');
await shot(page, 'list-mobile');
await axeTable(page, 'mobile');
ok('list: cards at 360');

page = await open(1440, '/client/', {rtl: true, dark: true});
assert.equal(await page.$eval('.crudit-ui-table__table', (e) => getComputedStyle(e).direction), 'rtl');
await axeTable(page, 'dark, right to left');
await shot(page, 'list-dark-rtl');
await page.close();

// ---------- Workflow (lot 3.4.c): the status, the menu of the states its transitions lead to, the confirmation in the
// dialog of the layout, the transition sent without a reload (toast, focus back on the status), an error said, the
// status rendered whatever the field, a success without HTML (reload), the counters. app:seed: INT-01 without technician (cannot be planned), INT-02 to plan, INT-03 planned.
const wfRow = (page, reference) => page.$$eval('.crudit-ui-table__row', (rows, reference) => '#' + rows.find((r) => r.querySelector('th[scope=row]')?.textContent.trim() === reference).id, reference);
const text = (page, sel) => page.$eval(sel, (e) => e.textContent.replace(/\s+/g, ' ').trim());

page = await open(1440, '/intervention/');
await page.evaluate(() => { window.notReloaded = true; });
let wf = await wfRow(page, 'INT-02');
assert.equal(await text(page, `${wf} .crudit-workflow__trigger`), 'To plan, change the status of INT-02');
await page.focus(`${wf} .crudit-workflow__trigger`);
await page.keyboard.press('Enter');
await settle(page);
assert.equal(await attr(page, `${wf} .crudit-workflow__trigger`, 'aria-expanded'), 'true', 'Enter opens the menu');
assert.deepEqual(await page.$$eval(`${wf} .crudit-workflow .crudit-ui-menu__item`, (items) => items.map((i) => i.textContent.trim())), ['Planned', 'Cancelled']);
await axeOn(page, '.crudit-ui-table', 'workflow menu open');
await shot(page, 'workflow-menu');

// The item opens the confirmation, which names the element; Cancel has the focus
await page.focus(`${wf} .crudit-workflow .crudit-ui-menu__item[data-transition=plan]`);
await page.keyboard.press('Enter');
await settle(page);
assert.equal(await page.$eval('#crudit-confirm', (e) => e.open), true, 'the confirmation opens');
assert.equal(await text(page, '#crudit-confirm-title'), 'INT-02: move to “Planned”?');
assert.equal(await page.evaluate(() => document.activeElement.closest('#crudit-confirm') !== null && document.activeElement.textContent.trim()), 'Cancel', 'focus on Cancel');
assert.ok(await page.$eval('#crudit-confirm .crudit-action', (e) => e.classList.contains('crudit-ui-btn--primary')), 'not destructive: primary');
await axeOn(page, '#crudit-confirm', 'workflow confirmation');

// Confirm: no reload, the status changes, a toast says it, the focus is back on the status
await page.click('#crudit-confirm .crudit-action');
await page.waitForFunction((wf) => document.querySelector(`${wf} .crudit-workflow__trigger`)?.textContent.includes('Planned'), {timeout: 5000}, wf);
assert.equal(await page.evaluate(() => window.notReloaded), true, 'no reload');
assert.equal(await page.$eval('#crudit-confirm', (e) => e.open), false, 'the confirmation is closed');
await page.waitForFunction(() => document.querySelector('#crudit-ui-toasts').textContent.includes('INT-02: status changed from “To plan” to “Planned”.'), {timeout: 3000});
assert.equal(await page.evaluate((wf) => document.activeElement === document.querySelector(`${wf} .crudit-workflow__trigger`), wf), true, 'focus back on the status');
// The card of the line (phones) is up to date too; a status carries no id (its HTML is in the table and the card)
const card = await page.evaluate(() => {
    const field = [...document.querySelectorAll('.crudit-ui-table__card .crudit-workflow')].find((f) => f.textContent.includes('INT-02'));
    return {
        trigger: field.querySelector('.crudit-workflow__trigger').textContent.replace(/\s+/g, ' ').trim(),
        items: [...field.querySelectorAll('.crudit-ui-menu__item')].map((i) => i.textContent.trim()),
        ids: document.querySelectorAll('.crudit-workflow [id]').length,
    };
});
assert.deepEqual(card, {trigger: 'Planned, change the status of INT-02', items: ['In progress', 'Cancelled'], ids: 0}, 'the card is up to date');
ok('workflow: a transition from the keyboard, without a reload');

// A destructive transition: Confirm in danger
wf = await wfRow(page, 'INT-01');
assert.deepEqual(await page.$$eval(`${wf} .crudit-workflow .crudit-ui-menu__item`, (items) => items.map((i) => i.textContent.trim())), ['Cancelled'], 'plan refused by its guard');
await page.click(`${wf} .crudit-workflow__trigger`);
await settle(page);
await page.click(`${wf} .crudit-workflow .crudit-ui-menu__item[data-transition=cancel]`);
await settle(page);
assert.ok(await page.$eval('#crudit-confirm .crudit-action', (e) => e.classList.contains('crudit-ui-btn--danger')), 'destructive: danger');
await page.keyboard.press('Escape');
await settle(page);
ok('workflow: a destructive transition is confirmed in danger');

// Refused by the server: the status stays, the message is said
wf = await wfRow(page, 'INT-03');
await page.setRequestInterception(true);
const refuse = (request) => request.url().includes('/workflow/') ? request.respond({status: 409, contentType: 'application/json', body: JSON.stringify({status: 'ko', message: 'Refused by the server.'})}) : request.continue();
page.on('request', refuse);
await page.click(`${wf} .crudit-workflow__trigger`);
await settle(page);
await page.click(`${wf} .crudit-workflow .crudit-ui-menu__item[data-transition=start]`);
await settle(page);
await page.click('#crudit-confirm .crudit-action');
await page.waitForFunction(() => document.querySelector('#crudit-ui-toasts').textContent.includes('Refused by the server.'), {timeout: 3000});
assert.ok((await text(page, `${wf} .crudit-workflow__trigger`)).startsWith('Planned'), 'the status has not changed');
assert.equal(await attr(page, `${wf} .crudit-workflow__trigger`, 'aria-busy'), null, 'no longer busy');
page.off('request', refuse);
await page.setRequestInterception(false);
errors.splice(0, errors.length, ...errors.filter((error) => !error.includes('409')));
ok('workflow: a refusal of the server is said, the status stays');

// Changed from anywhere (a sublist, a key of the project, a template): the server renders the status whatever the
// field, without a reload
await page.$eval(`${wf} .crudit-workflow`, (field) => field.setAttribute('data-2lenet--crudit-bundle--workflow-field-value', 'unknown'));
await page.evaluate(() => { window.notReloaded = true; });
await page.click(`${wf} .crudit-workflow__trigger`);
await settle(page);
await page.click(`${wf} .crudit-workflow .crudit-ui-menu__item[data-transition=start]`);
await settle(page);
await page.click('#crudit-confirm .crudit-action');
await page.waitForFunction(() => document.querySelector('#crudit-ui-toasts')?.textContent.includes('INT-03: status changed from “Planned” to “In progress”.'), {timeout: 3000});
assert.equal(await page.evaluate(() => window.notReloaded), true, 'not reloaded');
wf = await wfRow(page, 'INT-03');
assert.ok((await text(page, `${wf} .crudit-workflow__trigger`)).startsWith('In progress'), 'the new status');
ok('workflow: the status is rendered whatever the field, without a reload');

// A success without usable HTML: the page is reloaded (the status has changed), no toast of error
await page.evaluate(() => { window.notReloaded = true; });
await page.setRequestInterception(true);
const noHtml = (request) => request.url().includes('/workflow/') ? request.respond({status: 200, contentType: 'application/json', body: JSON.stringify({status: 'ok', message: 'Changed.'})}) : request.continue();
page.on('request', noHtml);
await page.click(`${wf} .crudit-workflow__trigger`);
await settle(page);
await page.click(`${wf} .crudit-workflow .crudit-ui-menu__item[data-transition=finish]`);
await settle(page);
await Promise.all([page.waitForNavigation({timeout: 5000}), page.click('#crudit-confirm .crudit-action')]);
assert.equal(await page.evaluate(() => window.notReloaded), undefined, 'reloaded');
assert.equal(await page.$('#crudit-ui-toasts .crudit-ui-toast--danger'), null, 'no toast of error');
page.off('request', noHtml);
await page.setRequestInterception(false);
ok('workflow: a success without HTML reloads the page');

// The cards of the phones show the status too; dark, right to left
page = await open(360, '/intervention/');
assert.ok(await visible(page, '.crudit-ui-table__card .crudit-workflow__trigger'), 'the status in the cards');
await axeOn(page, '.crudit-ui-table', 'workflow cards');
page = await open(1440, '/intervention/', {rtl: true, dark: true});
await axeOn(page, '.crudit-ui-table', 'workflow dark, right to left');
await shot(page, 'workflow-dark-rtl');

// The counters: the status of each state (its tone), then its number, in the order of the places
page = await open(1440, '/intervention/counters');
assert.deepEqual(await page.$$eval('.crudit-ui-counter', (counters) => counters.map((c) => c.textContent.replace(/\s+/g, '').trim())), ['Toplan1', 'Planned1', 'Inprogress2', 'Done1', 'Cancelled1', 'Archived0']);
await axeOn(page, '.crudit-ui-counters', 'counters');
await shot(page, 'workflow-counters');
ok('workflow: counters');

// ---------- Values of the fields (lot 3.4.a): the amounts at the end (their column), a long text on two lines in a cell and whole in
// its card, axe on the values of a record (its grid is lot 3.3.a), in light and in dark.
const axeValues = async (page, label) => {
    await page.evaluate(axeSource);
    assert.deepEqual(await page.evaluate(async () => (await axe.run({include: [...document.querySelectorAll('.crudit-value')].map((e) => [e])}, {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice']})).violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(' | ')}`)), [], `axe values ${label}`);
    ok(`axe values ${label}`);
};
page = await open(1440, '/client/');
// The class only: its CSS is the one of the Table of CruditUiBundle
assert.ok(await page.$eval('tbody tr:first-child td[id$="-amount-amount"]', (e) => e.classList.contains('crudit-ui-table__cell--end')), 'the amount at the end');
const lineHeight = await page.$eval('tbody tr:first-child .crudit-value--markdown', (e) => parseFloat(getComputedStyle(e).lineHeight));
assert.ok(await page.$eval('tbody tr:first-child .crudit-value--markdown', (e) => e.getBoundingClientRect().height) <= lineHeight * 2 + 1, 'the notes on two lines in a cell');
assert.ok(await page.$eval('tbody tr:first-child .crudit-value--markdown', (e) => e.getBoundingClientRect().width >= 14 * parseFloat(getComputedStyle(document.documentElement).fontSize) - 1), 'the notes not squeezed in a wide table');
await shot(page, 'fields-list');
await page.setViewport({width: 360, height: 800});
await new Promise((r) => setTimeout(r, 200));
assert.ok(await page.$eval('.crudit-ui-table__card .crudit-value--markdown', (e) => e.getBoundingClientRect().height) > lineHeight * 2 + 1, 'the notes whole in a card');
await page.close();
// In a cell, a web address shows its host and a JSON is on a line; the record shows them whole
page = await open(1440, '/client/');
await page.goto(base + await page.$eval('main a[href*="/client/show/"]', (a) => a.getAttribute('href')), {waitUntil: 'networkidle0'});
assert.notEqual(await page.$eval('.crudit-value__path', (e) => getComputedStyle(e).display), 'none', 'the path of the address on the record');
assert.equal(await page.$eval('.crudit-value__json > li', (e) => getComputedStyle(e).display), 'list-item', 'the JSON as a list on the record');
await page.goto(base + '/client/', {waitUntil: 'networkidle0'});
assert.equal(await page.$eval('td .crudit-value__path', (e) => getComputedStyle(e).display), 'none', 'the host only in a cell');
assert.equal(await page.$eval('td .crudit-value__json > li', (e) => getComputedStyle(e).display), 'inline', 'the JSON on a line in a cell');
await page.close();
for (const dark of [false, true]) {
    page = await open(1280, '/client/', {dark});
    await page.goto(base + await page.$eval('main a[href*="/client/show/"]', (a) => a.getAttribute('href')), {waitUntil: 'networkidle0'});
    assert.ok((await page.$$eval('.crudit-value', (v) => v.length)) >= 15, 'every field has a value');
    await shot(page, dark ? 'fields-show-dark' : 'fields-show');
    await axeValues(page, dark ? 'of a record, dark' : 'of a record');
    await page.close();
}
ok('fields: alignment, two lines in a cell, values of a record');

// ---------- Record (lot 3.3.a): the status beside the title, the key information, the sections in two columns on a
// wide screen and one below, folded and opened with the keyboard, two values per line on a phone. app:seed: INT-02 is
// late (an alert), its 4th section is refused to everybody, the last one folded.
const recordOf = async (page, reference) => {
    const id = await page.$$eval('.crudit-ui-table__row', (rows, reference) => rows.find((r) => r.querySelector('th[scope=row]')?.textContent.trim() === reference).id, reference);
    return `/intervention/show/${id.replace(/^list-intervention-/, '')}`;
};
const boxes = (page, sel) => page.$$eval(sel, (nodes) => nodes.map((n) => { const b = n.getBoundingClientRect(); return {top: Math.round(b.top), left: Math.round(b.left), width: Math.round(b.width)}; }));
page = await open(1440, '/intervention/');
const recordPath = await recordOf(page, 'INT-02');
await page.goto(base + recordPath, {waitUntil: 'networkidle0'});
assert.ok(await page.$('.crudit-ui-page-header__title-row .crudit-workflow'), 'the status beside the title');
assert.ok((await text(page, '.crudit-show .crudit-ui-alert')).includes('Late'), 'the alert of a late intervention');
assert.deepEqual(await page.$$eval('.crudit-show-section h2 button', (b) => b.map((x) => [x.textContent.trim(), x.getAttribute('aria-expanded')])), [['Intervention', 'true'], ['Billing', 'true'], ['History', 'false']]);
let sections = await boxes(page, '.crudit-show-sections > .crudit-show-section');
assert.ok(sections[0].top === sections[1].top && sections[1].left > sections[0].left, 'two columns at 1440');
// The history (getShowWideSections()) alone on its line, over both columns
assert.ok(sections[2].top > sections[0].top && sections[2].left === sections[0].left && sections[2].width > sections[0].width * 1.8, 'a wide section over the whole line');
// Folded and opened with the keyboard
await page.focus('#crudit-show-intervention-section-billing h2 button');
await page.keyboard.press('Enter');
await page.waitForFunction(() => document.querySelector('#crudit-show-intervention-section-billing h2 button').getAttribute('aria-expanded') === 'false', {timeout: 3000});
assert.equal(await visible(page, '#crudit-show-intervention-section-billing dl'), false, 'Enter folds the section');
await page.keyboard.press('Enter');
await page.waitForFunction(() => document.querySelector('#crudit-show-intervention-section-billing h2 button').getAttribute('aria-expanded') === 'true', {timeout: 3000});
// The section of billing: 2 values per line, the description over the whole line
const billing = await boxes(page, '#crudit-show-intervention-section-billing .crudit-show-field');
assert.ok(billing[1].top > billing[0].top && billing[1].width > billing[0].width * 1.5, 'a long text over the whole line');
await shot(page, 'record');
await axe(page, 'record at 1440');
await page.setViewport({width: 1024, height: 800});
await new Promise((r) => setTimeout(r, 200));
sections = await boxes(page, '.crudit-show-sections > .crudit-show-section');
assert.ok(sections[1].top > sections[0].top && sections[1].left === sections[0].left, 'one column below 1280');
await page.setViewport({width: 360, height: 800});
await new Promise((r) => setTimeout(r, 200));
const keys = await boxes(page, '.crudit-show-highlights__item');
assert.ok(keys[0].top === keys[1].top && keys[2].top > keys[0].top, 'two key values per line on a phone');
assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'no horizontal scroll on a phone');
await shot(page, 'record-phone');
await axe(page, 'record at 360');
await page.close();
for (const [dark, rtl] of [[true, false], [false, true]]) {
    page = await open(1440, recordPath, {dark, rtl});
    await axe(page, `record${dark ? ', dark' : ''}${rtl ? ', right to left' : ''}`);
    await page.close();
}
ok('record: header, alert, sections, keyboard, columns, phone');
// ---------- Tabs of a record (lot 3.3.b): the component Tabs of CruditUiBundle under the sections, the tab with a role
// nobody has not rendered, the arrows, the tab in the address (?tab=, replaceState) kept by a reload, a form in a tab
// that comes back to it once saved, no horizontal scroll on a phone.
page = await open(1440, '/client/');
const clientPath = await page.$eval('main a[href*="/client/show/"]', (a) => a.getAttribute('href'));
await page.goto(base + clientPath, {waitUntil: 'networkidle0'});
const tabState = () => page.evaluate(() => ({
    tabs: [...document.querySelectorAll('.crudit-show-tabs [role="tab"]')].map((t) => [t.textContent.trim().replace(/\s+\d+$/, ''), t.getAttribute('aria-selected')]),
    shown: [...document.querySelectorAll('.crudit-show-tabs [role="tabpanel"]:not([hidden])')].map((p) => p.id.replace(/^.*-panel-/, '')),
    tab: new URL(location.href).searchParams.get('tab'),
    focus: document.activeElement?.getAttribute('role'),
}));
let tabsNow = await tabState();
assert.deepEqual(tabsNow.tabs, [['Tags', 'true'], ['Notes', 'false']], `tabs: the tabs granted, the first selected (${JSON.stringify(tabsNow)})`);
assert.equal(await page.$eval('.crudit-show-tabs [role="tablist"]', (l) => l.getAttribute('aria-label')), 'Related information');
await page.click('.crudit-show-tabs [role="tab"]:nth-child(2)');
await page.waitForFunction(() => new URL(location.href).searchParams.get('tab') === 'notes', {timeout: 3000}).catch(() => null);
tabsNow = await tabState();
assert.deepEqual([tabsNow.shown, tabsNow.tab], [['notes'], 'notes'], `tabs: a click does not select the tab or write it in the address (${JSON.stringify(tabsNow)})`);
await page.keyboard.press('ArrowLeft');
tabsNow = await tabState();
assert.deepEqual([tabsNow.shown, tabsNow.tab, tabsNow.focus], [['tags'], 'tags', 'tab'], `tabs: the arrows (${JSON.stringify(tabsNow)})`);
await page.keyboard.press('End');
await page.reload({waitUntil: 'networkidle0'});
tabsNow = await tabState();
assert.deepEqual([tabsNow.shown, tabsNow.tabs[1][1]], [['notes'], 'true'], `tabs: a reload does not keep the tab (${JSON.stringify(tabsNow)})`);
await shot(page, 'record-tabs');
await axe(page, 'record with tabs');
// A form in a tab: saved, back to its tab
await page.$eval('.crudit-show-tabs [role="tabpanel"]:not([hidden]) textarea', (t) => { t.value = 'Smoke notes'; });
await Promise.all([
    page.waitForNavigation({waitUntil: 'networkidle0'}),
    page.$eval('.crudit-show-tabs [role="tabpanel"]:not([hidden]) form', (f) => f.requestSubmit()),
]);
tabsNow = await tabState();
assert.deepEqual([tabsNow.shown, tabsNow.tab], [['notes'], 'notes'], `tabs: a form saved does not come back to its tab (${JSON.stringify(tabsNow)})`);
assert.equal(await page.$eval('.crudit-show-tabs [role="tabpanel"]:not([hidden]) textarea', (t) => t.value), 'Smoke notes');
await page.setViewport({width: 360, height: 800});
await new Promise((r) => setTimeout(r, 200));
assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'tabs: no horizontal scroll on a phone');
await axe(page, 'record with tabs at 360');
await page.close();
for (const [dark, rtl] of [[true, false], [false, true]]) {
    page = await open(1440, clientPath, {dark, rtl});
    await axe(page, `record with tabs${dark ? ', dark' : ''}${rtl ? ', right to left' : ''}`);
    await page.close();
}
ok('tabs: granted, arrows, address, reload, form back to its tab, phone');
// ---------- Form (lot 3.2.a): the theme of Crudit imposed (the application declares bootstrap_5_layout), the grid of
// 4 columns then the section of 2, one column on a phone, a field of 2 columns in a narrow grid, the help of a field,
// the switch, axe on the form in light, in dark and right to left.
for (const [width, dark, rtl] of [[1440, false, false], [1440, true, false], [1280, false, true], [360, false, false]]) {
    page = await open(width, '/client/new', {dark, rtl});
    const columns = await page.$$eval('.crudit-form-grid', (grids) => grids.map((grid) => getComputedStyle(grid).gridTemplateColumns.split(' ').length));
    assert.deepEqual(columns, width === 360 ? [1, 1, 1] : [4, 2, 4], `columns of the grids at ${width}`);
    assert.ok(await page.$$eval('.crudit-form-grid', (grids) => grids.every((grid) => grid.scrollWidth <= grid.clientWidth)), `no field out of its grid at ${width}`);
    if (width === 1440 && !dark) {
        // The width of a field: 2 columns for the e-mail, the whole line for the notes
        const [name, email, progress, channels, notes, grid] = await page.$$eval('#client_name, #client_email, #client_progress, #client_channels, #client_notes, .crudit-form-section:nth-of-type(2) .crudit-form-grid',
            (elements) => elements.map((e) => (e.closest('.crudit-ui-field') ?? e).getBoundingClientRect().width));
        assert.ok(email > name * 1.9, 'the e-mail on 2 columns');
        assert.ok(progress > name * 2.9 && progress < name * 3.5, 'the progress on 3 columns');
        assert.ok(Math.abs(channels - grid) < 1, 'a field of 3 columns in a section of 2: the whole line');
        assert.ok(Math.abs(notes - grid) < 1, 'the notes on the whole line');
        // A field of 2 columns in a grid with room for one (a dialog, a narrow column of a wide screen): the whole
        // line, no implicit column (it would overflow, or squeeze the single column of the next fields)
        const [fits, field, next, room] = await page.evaluate(() => {
            const box = document.createElement('div');
            box.style.width = '20rem';
            box.innerHTML = '<div class="crudit-form-grid crudit-form-grid--4"><div class="crudit-ui-field crudit-form-field--span-2">A</div><div class="crudit-ui-field">B</div></div>';
            document.querySelector('main').append(box);
            const narrow = box.firstElementChild;
            const result = [narrow.scrollWidth <= narrow.clientWidth, narrow.children[0].getBoundingClientRect().width, narrow.children[1].getBoundingClientRect().width, narrow.clientWidth];
            box.remove();
            return result;
        });
        assert.ok(fits && Math.abs(field - room) < 1, 'a field of 2 columns in a narrow grid: the whole line, no overflow');
        assert.ok(Math.abs(next - room) < 1, 'a narrow grid keeps one column: the next field on the whole line too');
        await page.click('.crudit-ui-toggletip');
        assert.ok(await page.$eval('#client_name_tooltip', (panel) => panel.matches(':popover-open')), 'the help of the field opens');
        await page.keyboard.press('Escape');
        await page.click('label[for="client_active"]');
        assert.equal(await page.$eval('#client_active', (input) => input.checked), false, 'the label toggles the switch');
        await shot(page, 'form');
    }
    await axeOn(page, 'main form', `form at ${width}${dark ? ', dark' : ''}${rtl ? ', RTL' : ''}`);
    await page.close();
}
ok('form: theme of Crudit, grid, widths, help, switch');

// ---------- Base types (lot 3.2.b): the telephone on one line (on the other side right to left), its focus around both,
// the counter of characters, the format helpers once the field is left, the segmented control with the arrows, the
// code of a colour, a date in lists on one line.
for (const [width, rtl] of [[1440, false], [1280, true], [360, false]]) {
    page = await open(width, '/client/new', {rtl});
    const [code, number] = await page.$$eval('#client_phone .crudit-telephone-calling-code, #client_phone_number', (elements) => elements.map((e) => {
        const {top, left, height} = e.getBoundingClientRect();

        return {top, left, height};
    }));
    assert.ok(Math.abs(code.top - number.top) < 1 && Math.abs(code.height - number.height) < 1, `the calling code and the number on one line at ${width}`);
    assert.ok(rtl ? code.left > number.left : code.left < number.left, `the calling code before the number${rtl ? ', right to left' : ''}`);
    const lists = await page.$$eval('#client_createdAt select', (selects) => selects.map((select) => Math.round(select.getBoundingClientRect().top)));
    assert.equal(new Set(lists).size, 1, `the lists of a date on one line at ${width}`);
    if (width === 1440) {
        await page.focus('#client_phone_country');
        await page.keyboard.press('Shift');
        assert.notEqual(await page.$eval('#client_phone', (group) => getComputedStyle(group).outlineStyle), 'none', 'the focus of the calling code surrounds the field');

        await page.type('#client_notes', 'Back door');
        assert.equal(await page.$eval('#client_notes_count [data-crudit-ui-count]', (span) => span.textContent), '9 / 80 characters', 'the counter follows the typing');

        await page.click('#client_email', {clickCount: 3});
        await page.keyboard.type('Bob@Example.COM');
        assert.equal(await page.$eval('#client_email', (input) => input.value), 'Bob@Example.COM', 'the value does not change while typing');
        await page.keyboard.press('Tab');
        assert.equal(await page.$eval('#client_email', (input) => input.value), 'bob@example.com', 'crudit-email-format: small letters once the field is left');
        assert.equal(await page.$eval('#client_email', (input) => input.getAttribute('aria-invalid')), null);
        await page.click('#client_email', {clickCount: 3});
        await page.keyboard.type('bob');
        await page.keyboard.press('Tab');
        assert.equal(await page.$eval('#client_email', (input) => input.getAttribute('aria-invalid')), 'true', 'crudit-email-format: a wrong address is invalid');
        await page.click('#client_website', {clickCount: 3});
        await page.keyboard.type('HTTPS://WWW.EXAMPLE.COM');
        await page.keyboard.press('Tab');
        assert.equal(await page.$eval('#client_website', (input) => input.value), 'https://www.example.com', 'crudit-lower-format once the field is left');
        // crudit-ip-format: the point once a byte is 26 or more or has 3 digits (as in the 1.x), a point typed by habit just after it ignored
        const ip = () => page.$eval('#client_ip', (input) => input.value);
        await page.click('#client_ip');
        await page.keyboard.type('192');
        assert.equal(await ip(), '192.', 'a point after the 3rd digit of a byte');
        await page.keyboard.type('.');
        assert.equal(await ip(), '192.', 'a point typed just after the one added is ignored');
        await page.keyboard.type('30');
        assert.equal(await ip(), '192.30.', 'a point once the byte is 26 or more');
        await page.keyboard.type('25');
        assert.equal(await ip(), '192.30.25', 'no point under 26: a 3rd digit can follow');
        await page.keyboard.type('5.1');
        await page.keyboard.press('Tab');
        assert.equal(await ip(), '192.30.255.1');
        assert.equal(await page.$eval('#client_ip', (input) => input.getAttribute('aria-invalid')), null, 'a right IP address is not invalid');
        // An error of the server (aria-invalid from the theme, a value already used) is kept with a right value
        await page.$eval('#client_ip', (input) => {
            const copy = input.cloneNode();
            copy.id = 'client_ip_server';
            copy.value = '';
            copy.setAttribute('aria-invalid', 'true');
            input.after(copy);
        });
        await page.waitForFunction(() => document.getElementById('client_ip_server') !== null);
        await new Promise((resolve) => setTimeout(resolve, 100)); // Stimulus connects the copy (MutationObserver)
        await page.click('#client_ip_server');
        await page.keyboard.type('2552551.1');
        await page.keyboard.press('Tab');
        assert.equal(await page.$eval('#client_ip_server', (input) => input.value), '255.255.1.1', 'the controller is not connected to the copy');
        assert.equal(await page.$eval('#client_ip_server', (input) => input.getAttribute('aria-invalid')), 'true', 'the error of the server is kept, its message still shown');
        await page.$eval('#client_ip_server', (input) => input.remove());

        await page.click('#client_priority .crudit-ui-segmented__option:nth-child(2)');
        assert.equal(await page.$eval('#client_priority input:checked', (radio) => radio.value), 'low');
        await page.keyboard.press('ArrowRight');
        assert.equal(await page.$eval('#client_priority input:checked', (radio) => radio.value), 'high', 'the arrows move the segmented control');

        await page.$eval('#client_color', (input) => {
            input.value = '#ff0000';
            input.dispatchEvent(new Event('input', {bubbles: true}));
        });
        assert.equal(await page.$eval('#client_color + [data-color-code]', (span) => span.textContent), '#ff0000', 'the code follows the colour');
        await shot(page, 'form-base-types');
    }
    await axeOn(page, 'main form', `base types at ${width}${rtl ? ', RTL' : ''}`);
    await page.close();
}
ok('form: base types (telephone, counter, format helpers, segmented control, colour, date in lists)');

// ---------- Rich types (lot 3.2.c): an entity in a list with search, its choices asked to the route autocomplete
// (20 at a time, the next ones at the end of the list), a choice with several answers filtered in the page; the
// choices are sent with the form.
for (const [width, rtl] of [[1440, false], [1280, true], [360, false]]) {
    page = await open(width, '/client/new', {rtl});
    await page.focus('#client_referrer');
    await page.waitForFunction(() => document.querySelectorAll('#client_referrer_listbox [role="option"]').length === 20, {timeout: 5000}).catch(() => null);
    assert.equal(await page.$$eval('#client_referrer_listbox [role="option"]', (options) => options.length), 20, `the first choices of the server when the list opens at ${width}`);
    // WebKit scrolls to the field focused later than Chromium, after the list has opened: the list follows it at the
    // next frame (scroll, then requestAnimationFrame).
    await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    // The list under its input (above it when there is no room below), inside the screen
    const box = await page.$eval('#client_referrer_listbox', (list) => {
        const rect = list.getBoundingClientRect();
        const field = list.previousElementSibling.getBoundingClientRect();

        return {left: rect.left, right: rect.right, top: rect.top, bottom: rect.bottom, fieldTop: field.top, fieldBottom: field.bottom, screen: document.documentElement.clientWidth, height: window.innerHeight};
    });
    const nextToField = (box.top >= box.fieldBottom && box.top - box.fieldBottom < 16) || (box.bottom <= box.fieldTop && box.fieldTop - box.bottom < 16);
    assert.ok(nextToField && box.left >= 0 && box.right <= box.screen && box.top >= 0 && box.bottom <= box.height, `the list is next to its input, in the screen at ${width} (${JSON.stringify(box)})`);
    await axeOn(page, 'main form', `list with search open at ${width}${rtl ? ', RTL' : ''}`);
    if (width === 1440) {
        await page.$eval('#client_referrer_listbox', (list) => {
            list.scrollTop = list.scrollHeight;
        });
        await page.waitForFunction(() => document.querySelectorAll('#client_referrer_listbox [role="option"]').length === 25, {timeout: 5000}).catch(() => null);
        assert.equal(await page.$$eval('#client_referrer_listbox [role="option"]', (options) => options.length), 25, 'the end of the list asks the next choices');
        await page.keyboard.type('client07');
        await page.waitForFunction(() => {
            const options = document.querySelectorAll('#client_referrer_listbox [role="option"]');
            return options.length === 1 && options[0].textContent === 'Client 07';
        }, {timeout: 5000}).catch(() => null);
        assert.deepEqual(await page.$$eval('#client_referrer_listbox [role="option"]', (options) => options.map((option) => option.textContent)), ['Client 07'], 'the choices of the text typed');
        await page.keyboard.press('Enter');
        assert.equal(await page.$eval('#client_referrer', (input) => input.getAttribute('aria-expanded')), 'false', 'a choice closes a list with one answer');
        assert.deepEqual(await page.$$eval('.crudit-ui-combobox:has(#client_referrer) .crudit-ui-combobox__token-label', (labels) => labels.map((label) => label.textContent)), ['Client 07']);
        assert.notEqual(await page.$eval('input[type="hidden"][name="client[referrer]"]', (input) => input.value), '', 'the choice is in the field sent');

        await page.focus('#client_zones');
        await page.keyboard.type('nor');
        await page.keyboard.press('Enter');
        await page.keyboard.press('Escape');
        assert.deepEqual(await page.$eval('select[name="client[zones][]"]', (select) => [...select.selectedOptions].map((option) => option.value)), ['n', 's'], 'Enter adds the choice to the others');
        assert.deepEqual(await page.$$eval('.crudit-ui-combobox:has(#client_zones) .crudit-ui-combobox__token-label', (labels) => labels.map((label) => label.textContent)), ['South', 'North'], 'the tokens in the order of the choices');
        await shot(page, 'form-rich-types');

        await page.click('#client_name', {clickCount: 3});
        await page.keyboard.type('Client of the smoke test');
        await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), page.click('main form button[type="submit"]')]);
        // Saved: its record shows the entity chosen
        const savedId = await page.$$eval('main tbody tr.crudit-ui-table__row', (rows) => rows
            .find((row) => row.textContent.includes('Client of the smoke test'))
            ?.querySelector('input[type="checkbox"]')?.dataset.id ?? null);
        assert.ok(savedId, `the form with the lists with search is not saved (${page.url()})`);
        await page.goto(`${base}/client/show/${savedId}`, {waitUntil: 'networkidle0'});
        assert.ok((await page.$eval('main', (main) => main.textContent)).includes('Client 07'), 'the entity chosen is not saved');
    }
    await page.close();
}
ok('form: rich types (list with search, from the server or the page)');

// ---------- Filters (lot 3.1.e): the bar, the panel, the chips; the state is kept by the server (session)
const rowsOf = (page) => page.$$eval('main tbody tr.crudit-ui-table__row', (rows) => rows.length);
const filterNav = async (page, action) => {
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), action()]);
};
page = await open(1440, '/client/');
const allRows = await rowsOf(page);
assert.ok(allRows > 1, 'the list has rows');
assert.equal(await attr(page, 'main form.crudit-ui-filter-bar', 'role'), 'search');
// A main filter applies at once, typed text after a pause.
await page.click('[data-filter-quick="name"] button[aria-expanded]');
await filterNav(page, () => page.type('[name="filter_client_name_value"]', 'Client 0'));
assert.ok((await rowsOf(page)) < allRows, 'the list is filtered');
assert.ok(page.url().includes('filter_client_name_value=Client+0') || page.url().includes('filter_client_name_value=Client%200'), 'the filter is in the address of the form');
assert.ok(await visible(page, '[data-filter-quick="name"] .crudit-ui-filter-bar__quick-button--active'), 'the main filter shows it is active');
// Found again without a parameter after another page: still filtered (the session).
const filtered = await rowsOf(page);
await page.goto(base + '/client/', {waitUntil: 'networkidle0'});
assert.equal(await rowsOf(page), filtered, 'the filter is kept in the session');
assert.equal(await page.$eval('[name="filter_client_name_value"]', (input) => input.value), 'Client 0');
ok('filters: a main filter applies at once and is kept');
// The panel: in the page, not modal, kept open after a reload.
await page.click('[data-2lenet--crudit-ui-bundle--filters-target="toggle"]');
assert.deepEqual(await page.evaluate(() => {
    const panel = document.querySelector('dialog.crudit-ui-filter-panel');
    return [panel.open, panel.matches(':modal'), document.querySelector('[data-2lenet--crudit-ui-bundle--filters-target="toggle"]').getAttribute('aria-expanded')];
}), [true, false, 'true'], 'filters: the panel opens in the page');
await new Promise((resolve) => setTimeout(resolve, 400));
await shot(page, 'filters-panel');
await axe(page, 'filters, panel open');
// A filter of the panel applies with "Apply", and is a chip.
await page.click('dialog.crudit-ui-filter-panel label:has([name="filter_client_active_value"][value="true"])');
await filterNav(page, () => page.click('dialog.crudit-ui-filter-panel [data-2lenet--crudit-ui-bundle--filters-target="apply"]'));
assert.ok(await page.$eval('dialog.crudit-ui-filter-panel', (panel) => panel.open), 'filters: the panel stays open after Apply');
assert.equal(await page.$$eval('main ul.crudit-ui-chips > li', (chips) => chips.length), 1, 'filters: one chip, the name is a main filter');
assert.ok((await page.$eval('main ul.crudit-ui-chips > li', (chip) => chip.textContent)).includes('Active'), 'filters: the chip names its filter');
// The chip removes its filter.
await filterNav(page, () => page.click('main ul.crudit-ui-chips button.crudit-ui-chip__remove'));
assert.equal(await page.$$eval('main ul.crudit-ui-chips > li', (chips) => chips.length), 0, 'filters: the chip is gone');
assert.equal(await page.$eval('[name="filter_client_name_value"]', (input) => input.value), 'Client 0', 'filters: the other filter stays');
// Reset: the link of the panel.
await filterNav(page, () => page.click('dialog.crudit-ui-filter-panel a[href*="client_reset=1"]'));
assert.equal(await rowsOf(page), allRows, 'filters: reset brings all the rows back');
ok('filters: the panel, a chip, reset');
await page.close();
// ---------- Saved filters (lot 3.1.f): "My filters" and "Save these filters", per user (alice), POST with a CSRF token
const FT = '[data-2lenet--crudit-ui-bundle--filters-target="toggle"]';
const SAVE = 'button[aria-controls="crudit-filters-client-save"]';
const toastSays = (page, text) => page.waitForFunction((expected) => document.querySelector('#crudit-ui-toasts')?.textContent.includes(expected), {timeout: 3000}, text);
const savedNav = (page, action) => Promise.all([page.waitForNavigation({waitUntil: 'networkidle0'}), action()]);
page = await open(1440, '/client/', {user: 'alice'});
assert.ok(await visible(page, '.crudit-ui-filter-saved'), 'saved filters: "My filters" is in the bar of a user');
assert.equal(await page.$eval('.crudit-ui-filter-saved__label', (label) => label.textContent), 'My filters');
// The fields of the save are kept out of the form: the address of a GET of the filters never carries the token or the name.
assert.equal(await page.$$eval('form.crudit-ui-filter-bar input[name="_token"], form.crudit-ui-filter-bar input[name="redirect"]', (fields) => fields.every((field) => field.disabled) && fields.length), 2, 'saved filters: token and address are disabled');
assert.equal(await page.$eval('#crudit-filters-client-save-name', (field) => field.hasAttribute('name')), false, 'saved filters: the name has no name until Save');
await page.click(FT);
assert.equal(await page.$eval('.crudit-ui-filter-save', (region) => region.hidden), true, 'saved filters: the name field is folded');
assert.equal(await attr(page, SAVE, 'aria-expanded'), 'false');
await page.click(SAVE);
assert.equal(await attr(page, SAVE, 'aria-expanded'), 'true');
assert.ok(await visible(page, '.crudit-ui-filter-save'), 'saved filters: "Save these filters" shows the name');
assert.equal(await page.evaluate(() => document.activeElement.id), 'crudit-filters-client-save-name', 'saved filters: the focus is in the name');
// Escape folds the field, the panel stays open, the focus goes back to the button.
await page.keyboard.press('Escape');
assert.equal(await page.$eval('.crudit-ui-filter-save', (region) => region.hidden), true, 'saved filters: Escape folds the field');
assert.equal(await page.$eval('dialog.crudit-ui-filter-panel', (panel) => panel.open), true, 'saved filters: Escape does not close the panel');
assert.equal(await page.evaluate((sel) => document.activeElement === document.querySelector(sel), SAVE), true, 'saved filters: the focus is back on the button');
// No criterion: the server says so, nothing is saved.
await page.click(SAVE);
await page.type('#crudit-filters-client-save-name', 'Nothing');
await savedNav(page, () => page.keyboard.press('Enter'));
await toastSays(page, 'No filter to save');
assert.ok(!page.url().includes('_token') && !page.url().includes('name='), 'saved filters: the address carries neither the token nor the name');
// A filter of the panel (the draft, not applied), then "Save these filters": saved and applied.
if (!(await page.$eval('dialog.crudit-ui-filter-panel', (panel) => panel.open))) {
    await page.click(FT);
}
await page.click('dialog.crudit-ui-filter-panel label:has([name="filter_client_active_value"][value="true"])');
await page.click(SAVE);
await page.type('#crudit-filters-client-save-name', '  Active   clients ');
await shot(page, 'saved-filters-name');
await axe(page, 'saved filters, name field open');
await savedNav(page, () => page.keyboard.press('Enter'));
await toastSays(page, 'The filters “Active clients” are saved.');
assert.ok(!page.url().includes('_token') && !page.url().includes('Active'), 'saved filters: back on the list, the address is clean');
assert.ok((await rowsOf(page)) < allRows, 'saved filters: the filters are applied');
assert.equal(await page.$eval('.crudit-ui-filter-saved__label', (label) => label.textContent), 'Active clients', 'saved filters: the set in use is in the button');
assert.ok((await attr(page, '.crudit-ui-filter-saved__button', 'class')).includes('--active'));
const savedRows = await rowsOf(page);
// The list of the sets: the one in use is checked and removes its filters.
await page.click('.crudit-ui-filter-saved .crudit-ui-dropdown > button');
assert.ok(await visible(page, '.crudit-ui-filter-saved__list'), 'saved filters: the list opens');
assert.equal(await page.$eval('.crudit-ui-filter-saved__apply', (link) => [link.tagName, link.getAttribute('aria-current')]).then(String), 'A,true', 'saved filters: the set in use is a link, checked');
await shot(page, 'saved-filters-list');
await axe(page, 'saved filters, list open');
await savedNav(page, () => page.click('a.crudit-ui-filter-saved__apply'));
assert.equal(await rowsOf(page), allRows, 'saved filters: a click on the set in use removes its filters');
assert.equal(await page.$eval('.crudit-ui-filter-saved__label', (label) => label.textContent), 'My filters', 'saved filters: no set in use');
// A click on the set applies it again (a POST).
await page.click('.crudit-ui-filter-saved .crudit-ui-dropdown > button');
await savedNav(page, () => page.click('button.crudit-ui-filter-saved__apply'));
assert.equal(await rowsOf(page), savedRows, 'saved filters: a click on a set applies it');
// The same name replaces the set: "Replace".
if (!(await page.$eval('dialog.crudit-ui-filter-panel', (panel) => panel.open))) {
    await page.click(FT);
}
await page.click(SAVE);
await page.type('#crudit-filters-client-save-name', 'ACTIVE CLIENTS');
assert.equal((await page.$eval('[data-2lenet--crudit-ui-bundle--saved-filters-target="submit"]', (button) => button.textContent)).trim(), 'Replace', 'saved filters: a name already taken says Replace');
assert.ok((await page.$eval('#crudit-filters-client-save-help', (help) => help.textContent)).includes('replaced'), 'saved filters: and says why');
await page.keyboard.press('Escape');
// The bin asks in place and names the set; Escape folds the question, the list stays open.
await page.click('.crudit-ui-filter-saved .crudit-ui-dropdown > button');
await page.click('.crudit-ui-filter-saved__item .crudit-ui-icon-btn');
assert.ok(await visible(page, '.crudit-ui-filter-saved__confirm'), 'saved filters: the deletion is asked in place');
assert.ok((await page.$eval('.crudit-ui-filter-saved__confirm', (confirm) => confirm.textContent)).includes('Delete the filters “Active clients”?'), 'saved filters: the question names the set');
assert.equal(await page.evaluate(() => document.activeElement.textContent.trim()), 'Cancel', 'saved filters: the focus is on Cancel');
await page.keyboard.press('Escape');
assert.equal(await page.$eval('.crudit-ui-filter-saved__confirm', (confirm) => confirm.hidden), true, 'saved filters: Escape folds the question');
assert.ok(await visible(page, '.crudit-ui-filter-saved__list'), 'saved filters: and the list stays open');
await page.click('.crudit-ui-filter-saved__item .crudit-ui-icon-btn');
await shot(page, 'saved-filters-delete');
await axe(page, 'saved filters, deletion asked');
await savedNav(page, () => page.click('.crudit-ui-filter-saved__confirm .crudit-ui-btn--danger'));
await toastSays(page, 'The filters “Active clients” are deleted.');
await page.click('.crudit-ui-filter-saved .crudit-ui-dropdown > button');
assert.ok(await visible(page, '.crudit-ui-filter-saved__empty'), 'saved filters: no set left, the button says where to save one');
ok('saved filters: save, apply, remove, replace, delete');
await page.close();
// A user who is not signed in has no saved filters.
page = await open(1440, '/client/');
assert.equal(await page.$('.crudit-ui-filter-saved'), null, 'saved filters: nothing without a user');
assert.equal(await page.$('.crudit-ui-filter-save'), null);
await page.close();
// A phone: the bookmark alone, its name stays in the accessible name.
page = await open(360, '/client/', {user: 'alice'});
assert.ok(await visible(page, '.crudit-ui-filter-saved__button'), 'saved filters: the button is on a phone');
assert.ok((await page.$eval('.crudit-ui-filter-saved__label', (label) => label.getBoundingClientRect().width)) <= 1, 'saved filters: the label is hidden visually on a phone');
assert.ok((await page.$eval('.crudit-ui-filter-saved__button', (button) => button.textContent)).includes('My filters'), 'saved filters: but still read');
await axe(page, 'saved filters, phone');
await page.close();

// A phone: the main filters are in the side panel, modal.
page = await open(360, '/client/');
await page.click('[data-2lenet--crudit-ui-bundle--filters-target="toggle"]');
await new Promise((resolve) => setTimeout(resolve, 400));
assert.deepEqual(await page.evaluate(() => {
    const panel = document.querySelector('dialog.crudit-ui-filter-panel');
    return [panel.matches(':modal'), panel.querySelector('[name="filter_client_name_value"]') !== null, document.documentElement.scrollWidth <= window.innerWidth];
}), [true, true, true], 'filters: a modal panel with the main filters on a phone');
await shot(page, 'filters-phone');
await axe(page, 'filters, phone');
await page.close();
for (const [dark, rtl] of [[true, false], [false, true]]) {
    page = await open(1440, '/client/', {dark, rtl});
    await page.click('[data-2lenet--crudit-ui-bundle--filters-target="toggle"]');
    await new Promise((resolve) => setTimeout(resolve, 400));
    await axe(page, `filters, panel open${dark ? ', dark' : ''}${rtl ? ', right to left' : ''}`);
    await page.close();
}
ok('filters: a phone, dark, right to left');

// ---------- Export of the list (lot 3.1.g): the dialog (formats as cards, what the file holds), then the controller `export` of
// CruditUiBundle: the form stays a GET that Turbo leaves alone, the token, the cookie of the server tells the download
// began (the dialog closes, a toast), without it an error and a second try. The download itself is not made here (the
// submit is stopped; the cookie is the one the server sets, tested by PHPUnit): the browser would leave the page.
page = await open(1440, '/client/');
const exportButton = await page.evaluateHandle(() => [...document.querySelectorAll('.crudit-page-actions button[command="show-modal"]')].find((b) => b.textContent.trim() === 'Export'));
await exportButton.click();
await settle(page);
assert.equal(await page.evaluate(() => document.querySelector('dialog[open]')?.querySelector('h2')?.textContent.trim()), 'Export the list', 'export: the dialog opens');
assert.equal(await page.evaluate(() => document.activeElement.textContent.trim()), 'Cancel', 'export: focus on Cancel');
assert.deepEqual(await page.$$eval('dialog[open] .crudit-ui-export__title', (e) => e.map((n) => n.textContent.trim())), ['Excel', 'CSV', 'PDF']);
assert.equal(await page.$eval('dialog[open] input[name=format]:checked', (e) => e.value), 'xls', 'export: the first format is checked');
// the keyboard: arrows change the format
await page.focus('dialog[open] input[name=format]:checked');
await page.keyboard.press('ArrowDown');
assert.equal(await page.$eval('dialog[open] input[name=format]:checked', (e) => e.value), 'csv', 'export: the arrows choose the format');
await axeOn(page, 'dialog[open]', 'export dialog');
await shot(page, 'export-dialog');
await page.evaluate(() => document.querySelector('dialog[open] form').addEventListener('submit', (event) => event.preventDefault()));
await page.click('dialog[open] button[type=submit]');
assert.equal(await page.$eval('dialog[open] [role=status]', (e) => e.textContent.trim()), 'Export in progress…', 'export: the status');
assert.equal(await page.$eval('dialog[open] button[type=submit]', (e) => e.getAttribute('aria-busy')), 'true', 'export: the button is busy');
const token = await page.$eval('dialog[open] input[name=download_token]', (e) => e.value);
assert.match(token, /^[A-Za-z0-9-]{8,64}$/, 'export: the token');
await page.evaluate((token) => { document.cookie = `crudit_export=${token}; path=/`; }, token);
await page.waitForFunction(() => !document.querySelector('dialog[open]'), {timeout: 3000});
await page.waitForFunction(() => document.querySelector('#crudit-ui-toasts').textContent.includes('Export done.'), {timeout: 3000});
assert.equal(await page.evaluate(() => document.cookie.includes('crudit_export')), false, 'export: the cookie is deleted');
// no cookie: the error is shown and the form can be sent again; the token is still watched, a late file closes the dialog
await exportButton.click();
await settle(page);
await page.evaluate(() => {
    const form = document.querySelector('dialog[open] form');
    form.setAttribute('data-2lenet--crudit-ui-bundle--export-timeout-value', '800');
    form.addEventListener('submit', (event) => event.preventDefault());
});
await page.click('dialog[open] button[type=submit]');
assert.equal(await page.$$eval('dialog[open] button[type=submit] .crudit-ui-icon--spin', (e) => e.length), 1, 'export: the spinner shows in the button while it works');
await page.waitForFunction(() => !document.querySelector('dialog[open] [role=alert]').hidden, {timeout: 4000});
assert.equal(await page.$eval('dialog[open] [role=alert]', (e) => e.textContent.trim()), 'The export did not start. Try again, or reload the page if your session has expired.');
assert.equal(await page.$eval('dialog[open] button[type=submit]', (e) => e.getAttribute('aria-busy')), null, 'export: the button is free again');
assert.equal(await page.$$eval('dialog[open] button[type=submit] .crudit-ui-icon--spin', (e) => e.length), 0, 'export: the spinner is gone');
assert.equal(await page.$eval('dialog[open]', (e) => e.open), true, 'export: the dialog stays open on an error');
await axeOn(page, 'dialog[open]', 'export dialog, error');
const lateToken = await page.$eval('dialog[open] input[name=download_token]', (e) => e.value);
await page.evaluate((token) => { document.cookie = `crudit_export=${token}; path=/`; }, lateToken);
await page.waitForFunction(() => !document.querySelector('dialog[open]'), {timeout: 3000});
await page.waitForFunction(() => document.querySelector('#crudit-ui-toasts').textContent.includes('Export done.'), {timeout: 3000});
ok('export: a file that comes after the delay still closes the dialog');
// A times out, the user sends B, the file of A arrives: the toast only, B goes on; then B closes the dialog
await exportButton.click();
await settle(page);
const sendAttempt = async () => {
    await page.click('dialog[open] button[type=submit]');
    return page.$eval('dialog[open] input[name=download_token]', (e) => e.value);
};
await page.evaluate(() => {
    const form = document.querySelector('dialog[open] form');
    form.setAttribute('data-2lenet--crudit-ui-bundle--export-timeout-value', '800');
    form.addEventListener('submit', (event) => event.preventDefault());
});
const tokenA = await sendAttempt();
await page.waitForFunction(() => !document.querySelector('dialog[open] [role=alert]').hidden, {timeout: 4000});
await page.evaluate(() => document.querySelector('dialog[open] form').setAttribute('data-2lenet--crudit-ui-bundle--export-timeout-value', '8000'));
const tokenB = await sendAttempt();
assert.notEqual(tokenA, tokenB, 'export: each attempt has its own token');
await page.evaluate((token) => { document.cookie = `crudit_export=${token}; path=/`; }, tokenA);
await page.waitForFunction(() => document.querySelector('#crudit-ui-toasts').textContent.includes('Export done.'), {timeout: 3000});
assert.equal(await page.$eval('dialog[open] button[type=submit]', (e) => e.getAttribute('aria-busy')), 'true', 'export: the older file does not stop the attempt in progress');
await page.evaluate((token) => { document.cookie = `crudit_export=${token}; path=/`; }, tokenB);
await page.waitForFunction(() => !document.querySelector('dialog[open]'), {timeout: 3000});
ok('export: the late file of an older attempt does not stop the attempt in progress');
// the server answers a page, not a file (an unknown format: a message and a redirect to the list): the error at once, the page does not move.
// A page of its own: the submits of the steps above were stopped, and the delay (60 s) is not the one that ends this wait.
page = await open(1440, '/client/');
const exportButton2 = await page.evaluateHandle(() => [...document.querySelectorAll('.crudit-page-actions button[command="show-modal"]')].find((b) => b.textContent.trim() === 'Export'));
await exportButton2.click();
await settle(page);
const before = page.url();
await page.evaluate(() => {
    const radio = document.querySelector('dialog[open] input[name=format]');
    radio.value = 'doc';
    radio.checked = true;
});
await page.click('dialog[open] button[type=submit]');
await page.waitForFunction(() => !document.querySelector('dialog[open] [role=alert]').hidden, {timeout: 8000});
assert.equal(page.url(), before, 'export: an error page does not take the user away');
// the 422 page of Crudit carries the reason (data-crudit-export-error): the dialog shows it, not the generic message
assert.equal(await page.$eval('dialog[open] [role=alert]', (e) => e.textContent.trim()), 'The export could not be generated.');
// the browser logs the 422 of that page: it is the answer that this step asks for
errors.splice(0, errors.length, ...errors.filter((error) => !error.includes('status of 422')));
assert.equal(await page.$eval('dialog[open] form', (e) => e.getAttribute('target')), null, 'export: the form goes back to its own page');
// the error is gone when the dialog opens again
await page.keyboard.press('Escape');
await settle(page);
await exportButton2.click();
await settle(page);
assert.equal(await page.$eval('dialog[open] [role=alert]', (e) => e.hidden), true, 'export: the error of the last try is gone');
ok('export: a page in place of a file is an error at once, and it goes when the dialog opens again');
// a phone, right to left
page = await open(360, '/client/', {rtl: true});
await page.evaluate(() => [...document.querySelectorAll('button[command="show-modal"]')].find((b) => b.textContent.trim().includes('Export') || b.getAttribute('commandfor')?.startsWith('modal_')).click());
await settle(page);
assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, 'export: the page scrolls sideways on a phone');
await page.close();
ok('export: dialog, formats by keyboard, busy, cookie, error, phone');

// ---------- Turbo cache hook / reduced motion: no errors
console.log(errors.length ? 'ERRORS:\n' + errors.join('\n') : 'no console errors');
console.log(`${passed} checks passed`);
await browser.close();
process.exit(errors.length ? 1 : 0);
