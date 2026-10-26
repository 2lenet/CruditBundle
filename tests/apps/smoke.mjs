import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {createRequire} from 'node:module';
import puppeteer from 'puppeteer-core';

const require = createRequire(import.meta.url);
const axeSource = readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const base = (process.argv[2] ?? '').replace(/\/$/, '');
const executablePath = process.argv[3] ?? process.env.CHROME_PATH ?? '/usr/bin/google-chrome';
const shots = process.env.SHOTS;
assert.ok(base, 'Usage: node smoke.mjs <url> [chrome path]');

const browser = await puppeteer.launch({executablePath, headless: true, args: ['--no-sandbox']});
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
    page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
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

async function axe(page, label) {
    await new Promise((r) => setTimeout(r, 500));
    await page.evaluate(axeSource);
    const result = await page.evaluate((exclude) => axe.run({exclude}, {runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice']}), EXCLUDED);
    const v = result.violations.map((x) => `${x.id}: ${x.nodes.map((n) => n.target.join(' ')).join(' | ')}`);
    assert.deepEqual(v, [], `axe ${label}`);
    ok(`axe ${label}`);
}
const shot = async (page, name) => shots && page.screenshot({path: `${shots}/${name}.png`});

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
await new Promise((r) => setTimeout(r, 300));
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
await new Promise((r) => setTimeout(r, 300));
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
await new Promise((r) => setTimeout(r, 300));
assert.ok(Math.abs((await box(page, '#crudit-sidebar')).w - 240) < 2);
assert.equal(await attr(page, '[data-menu-label=Billing] button', 'aria-expanded'), 'true', 'current sub-menu reopened');
ok('back to full menu');
await page.close();

// ---------- auto rail between 1024 and 1279, explicit expanded wins
page = await open(1100);
assert.ok(Math.abs((await box(page, '#crudit-sidebar')).w - 72) < 2, 'auto: rail at 1100');
assert.equal(await page.$eval('[data-2lenet--crudit-bundle--sidebar-target=toggleLabel]', (e) => e.textContent), 'Expand the menu');
await page.click('.crudit-sidebar__collapse');
await new Promise((r) => setTimeout(r, 300));
assert.ok(Math.abs((await box(page, '#crudit-sidebar')).w - 240) < 2, 'expanded at 1100');
assert.equal((await page.cookies()).find((c) => c.name === 'crudit_sidebar').value, 'expanded');
ok('auto = rail at 1100, expand by choice');
await page.setViewport({width: 1500, height: 800});
await new Promise((r) => setTimeout(r, 200));
await page.setViewport({width: 1100, height: 800});
await new Promise((r) => setTimeout(r, 300));
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
await new Promise((r) => setTimeout(r, 400));
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
await new Promise((r) => setTimeout(r, 400));
assert.ok(!(await visible(page, '#crudit-sidebar')));
assert.equal(await page.evaluate(() => document.activeElement.className.includes('crudit-header__burger')), true, 'focus back to burger');
assert.equal(await page.$eval('.crudit-shell__body', (e) => e.inert), false);
assert.equal(await attr(page, '#crudit-sidebar', 'role'), 'complementary');
assert.equal(await attr(page, '.crudit-header__burger', 'aria-expanded'), 'false');
ok('Escape closes, focus back to the burger');

await page.click('.crudit-header__burger');
await new Promise((r) => setTimeout(r, 400));
await page.mouse.click(380, 400);
await new Promise((r) => setTimeout(r, 400));
assert.ok(!(await visible(page, '#crudit-sidebar')), 'backdrop closes');
await page.click('.crudit-header__burger');
await new Promise((r) => setTimeout(r, 400));
await page.click('.crudit-sidebar__close');
await new Promise((r) => setTimeout(r, 400));
assert.ok(!(await visible(page, '#crudit-sidebar')), 'close button closes');
ok('backdrop and close button');

// skip link "Skip to menu" opens the drawer
await page.reload({waitUntil: 'networkidle0'});
await page.keyboard.press('Tab');
await page.keyboard.press('Tab');
assert.equal(await page.evaluate(() => document.activeElement.getAttribute('href')), '#main-nav');
await page.keyboard.press('Enter');
await new Promise((r) => setTimeout(r, 400));
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
await new Promise((r) => setTimeout(r, 400));
await page.setViewport({width: 1500, height: 800});
await new Promise((r) => setTimeout(r, 400));
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
await new Promise((r) => setTimeout(r, 300));
const rb = await box(page, '#crudit-sidebar');
assert.ok(rb.x > 1000, `rail on the right in RTL (${rb.x})`);
await page.click('[data-menu-label=Billing] button');
const rf = await box(page, '[data-menu-label=Billing] .crudit-nav__submenu');
assert.ok(rf.r < rb.x + 8 && rf.x < rb.x, `flyout on the left of the rail in RTL (${rf.x}-${rf.r} / ${rb.x})`);
await shot(page, 'desktop-rtl-flyout');
const tipx = await (async () => { await page.keyboard.press('Escape'); await page.hover('[data-menu-label=Clients] a'); await new Promise((r) => setTimeout(r, 300)); return box(page, '[data-menu-label=Clients] .crudit-ui-tooltip'); })();
assert.ok(tipx.r <= rb.x + 8, `tooltip on the left in RTL (${tipx.r})`);
ok('RTL: rail, flyout and tooltip on the inline end side');
await page.close();
page = await open(390, '/', {rtl: true});
await page.click('.crudit-header__burger');
await new Promise((r) => setTimeout(r, 400));
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
await new Promise((r) => setTimeout(r, 300));
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
await new Promise((r) => setTimeout(r, 400));
// The padding under the filter: nothing there can take the focus, which goes to <body>
const padding = await box(page, '.crudit-sidebar__filter');
await page.mouse.click(padding.x + padding.w / 2, padding.b - 2);
assert.equal(await page.evaluate(() => document.activeElement === document.body), true, 'the focus is on <body>');
await page.keyboard.press('Tab');
assert.ok(await inAside(page), 'Tab comes back into the drawer');
await page.mouse.click(padding.x + padding.w / 2, padding.b - 2);
assert.equal(await page.evaluate(() => document.activeElement === document.body), true);
await page.keyboard.press('Escape');
await new Promise((r) => setTimeout(r, 400));
assert.ok(!(await visible(page, '#crudit-sidebar')), 'Escape closes the drawer from <body>');
ok('drawer: Escape and Tab with the focus on the page');

// Drawer: a click that does not change the page leaves it open
await page.click('.crudit-header__burger');
await new Promise((r) => setTimeout(r, 400));
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
await new Promise((r) => setTimeout(r, 400));
assert.equal(await active(page), 'main-nav', 'the focus goes to the menu, not to <body>');
ok('a resize that closes the drawer keeps the focus');
await page.close();

// A project that empties the blocks menu_filter and menu_toggle: the controller works without them
const errorsBefore = errors.length;
page = await open(1440, '/overrides', {cookie: 'collapsed'});
await page.click('[data-menu-label=Billing] button');
assert.ok(await visible(page, '[data-menu-label=Billing] .crudit-nav__submenu'));
await page.setViewport({width: 390, height: 800});
await new Promise((r) => setTimeout(r, 300));
await page.click('.crudit-header__burger');
await new Promise((r) => setTimeout(r, 400));
assert.ok(await visible(page, '#crudit-sidebar'));
await page.keyboard.press('Escape');
await new Promise((r) => setTimeout(r, 400));
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
await new Promise((r) => setTimeout(r, 400));
const outside = await page.evaluate(() => [...document.body.children]
    .filter((e) => !['SCRIPT', 'STYLE', 'LINK', 'TEMPLATE'].includes(e.tagName) && !e.contains(document.querySelector('.crudit-shell')) && e !== document.querySelector('.crudit-shell'))
    .map((e) => `${e.className || e.tagName}:${e.inert}`));
assert.ok(outside.length >= 2 && outside.every((x) => x.endsWith(':true')), `everything outside the shell is inert (${outside})`);
assert.equal(await page.$eval('.crudit-shell__backdrop', (e) => e.inert), false, 'the backdrop is not inert');
assert.equal(await page.$eval('#crudit-sidebar', (e) => e.inert), false);
await page.keyboard.press('Escape');
await new Promise((r) => setTimeout(r, 400));
const left = await page.evaluate(() => [...document.querySelectorAll('[inert]')].length);
assert.equal(left, 0, 'nothing stays inert once the drawer is closed');
ok('drawer: everything around the menu is inert, and released on close');

// Escape in the filter empties it and nothing else
await page.click('.crudit-header__burger');
await new Promise((r) => setTimeout(r, 400));
await page.type('#crudit-menu-filter', 'inter');
await page.keyboard.press('Escape');
await new Promise((r) => setTimeout(r, 200));
assert.equal(await page.$eval('#crudit-menu-filter', (e) => e.value), '', 'Escape empties the filter');
assert.ok(await visible(page, '#crudit-sidebar'), 'and the drawer stays open');
assert.ok((await labels()).includes('Clients'), 'the menu is whole again');
await page.keyboard.press('Escape');
await new Promise((r) => setTimeout(r, 400));
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
await new Promise((r) => setTimeout(r, 300));
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
assert.equal(await attr(page, 'tbody tr:first-child .crudit-row-actions a.crudit-ui-icon-btn', 'href').then((h) => /\/client\/edit\/\d+$/.test(h)), true);
await page.click('tbody tr:first-child .crudit-row-actions .crudit-ui-dropdown > button');
assert.equal(await shown(page, 'tbody tr:first-child .crudit-row-actions .crudit-ui-menu__item'), 2);
await axeOn(page, 'tbody tr:first-child .crudit-row-actions', 'actions of a line, menu open');
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

// ---------- Turbo cache hook / reduced motion: no errors
console.log(errors.length ? 'ERRORS:\n' + errors.join('\n') : 'no console errors');
console.log(`${passed} checks passed`);
await browser.close();
process.exit(errors.length ? 1 : 0);
