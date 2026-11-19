// Playwright browsers for the smoke tests, behind the few methods of the Puppeteer API the tests were written with
// (evaluate with several arguments, setViewport, setRequestInterception, waitForNavigation...), so that the same test
// runs on every project: node smoke.mjs <url> [project]
//
//   chromium  Chrome on a desktop
//   webkit    Safari on a desktop (WebKit)
//   iphone    Safari on an iPhone (WebKit, touch, mobile viewport)
//   pixel     Chrome on an Android phone (Chromium, touch, mobile viewport)
//
// CHROME_PATH uses an installed Chrome instead of the Chromium of Playwright.
import {chromium, devices, webkit} from 'playwright';

export const projects = {
    chromium: {type: chromium, context: {...devices['Desktop Chrome'], viewport: {width: 1280, height: 800}}},
    webkit: {type: webkit, context: {...devices['Desktop Safari'], viewport: {width: 1280, height: 800}}},
    iphone: {type: webkit, context: devices['iPhone 15']},
    pixel: {type: chromium, context: devices['Pixel 7']},
};

// The function is sent as a text: the arguments of Puppeteer (several, handles included) travel in one array.
const call = (fn) => `(${fn})`;
// Run in the page: nothing of this module is there, so every function is self-contained.
const run = ([source, args]) => (0, eval)(source)(...args);
const runOn = (first, [source, args]) => (0, eval)(source)(first, ...args);
const waitUntil = (options = {}) => ({...options, waitUntil: options.waitUntil?.startsWith('networkidle') ? 'networkidle' : options.waitUntil});

class Request {
    constructor(route) {
        this.route = route;
    }

    url() {
        return this.route.request().url();
    }

    method() {
        return this.route.request().method();
    }

    headers() {
        return this.route.request().headers();
    }

    postData() {
        return this.route.request().postData();
    }

    continue() {
        return this.route.continue();
    }

    respond({status = 200, body = '', ...options}) {
        return this.route.fulfill({status, body, ...options});
    }

    abort() {
        return this.route.abort();
    }
}

const proxies = new WeakMap();

const adapt = (page, context) => {
    if (proxies.has(page)) {
        return proxies.get(page);
    }
    const interceptors = new Map();
    let intercepting = false;
    const own = {
        // A text is a script, as in Puppeteer (the source of axe-core): its statements run in the page.
        evaluate: (fn, ...args) => typeof fn === 'string' ? page.evaluate((source) => (0, eval)(source), fn) : page.evaluate(run, [call(fn), args]),
        $eval: (selector, fn, ...args) => page.$eval(selector, runOn, [call(fn), args]),
        $$eval: (selector, fn, ...args) => page.$$eval(selector, runOn, [call(fn), args]),
        // Puppeteer: (function, options, ...arguments); Playwright: (function, argument, options).
        waitForFunction: (fn, options = {}, ...args) => page.waitForFunction(run, [call(fn), args], options),
        // Playwright waits until the element is visible, stable and not covered (a dialog that closes, a toast), which
        // Puppeteer did not: the click is what a user does. Except on a disabled element (aria-disabled): the tests
        // check that it does nothing, and Playwright would wait for it to be enabled.
        async click(selector, options) {
            const disabled = await page.$eval(selector, (element) => element.matches(':disabled, [aria-disabled="true"]')).catch(() => false);

            return page.click(selector, {force: disabled, ...options});
        },
        goto: (address, options) => page.goto(address, waitUntil(options)),
        reload: (options) => page.reload(waitUntil(options)),
        // WebKit reports a form answered by 204 (no navigation) as an aborted navigation: wait for the next one.
        async waitForNavigation(options) {
            for (;;) {
                try {
                    return await page.waitForNavigation(waitUntil(options));
                } catch (error) {
                    if (!error.message.includes('Aborted: 204')) {
                        throw error;
                    }
                }
            }
        },
        // Playwright keeps the type of device of the context: only the size changes.
        setViewport: ({width, height}) => page.setViewportSize({width, height}),
        emulateMediaFeatures: (features) => page.emulateMedia(Object.fromEntries(features.map(({name, value}) => [name === 'prefers-color-scheme' ? 'colorScheme' : name, value]))),
        emulateMediaType: (media) => page.emulateMedia({media}),
        // The cookies belong to the context in Playwright, to the browser in Puppeteer: the same here (shared by the pages).
        cookies: (...urls) => context.cookies(...urls),
        setCookie: (...cookies) => context.addCookies(cookies),
        deleteCookie: async (...cookies) => {
            for (const {name, domain, path} of cookies) {
                await context.clearCookies({name, domain, path});
            }
        },
        // Client hints and platform of another system (before the page loads). Playwright sets the user agent for a
        // whole context only: the properties read by the scripts of the page are replaced instead.
        async setUserAgent({userAgent, userAgentMetadata, platform}) {
            await page.addInitScript(({userAgent, userAgentMetadata, platform}) => {
                const define = (target, property, value) => Object.defineProperty(target, property, {get: () => value, configurable: true});
                define(Navigator.prototype, 'userAgent', userAgent);
                platform && define(Navigator.prototype, 'platform', platform);
                userAgentMetadata && define(Navigator.prototype, 'userAgentData', {
                    mobile: userAgentMetadata.mobile,
                    platform: userAgentMetadata.platform,
                    brands: [],
                    getHighEntropyValues: async () => ({...userAgentMetadata}),
                });
            }, {userAgent, userAgentMetadata, platform});
        },
        accessibility: {snapshot: () => page.locator('body').ariaSnapshot()},
        async setRequestInterception(enabled) {
            intercepting = enabled;
            if (enabled) {
                await page.route('**/*', (route) => {
                    const request = new Request(route);
                    interceptors.size ? [...interceptors.values()].forEach((listener) => listener(request)) : route.continue();
                });
            } else {
                await page.unroute('**/*');
                interceptors.clear();
            }
        },
        // While the interception is on, a listener of 'request' can answer the request; otherwise it only watches.
        on(event, listener) {
            event === 'request' && intercepting ? interceptors.set(listener, listener) : page.on(event, listener);
            return proxy;
        },
        off(event, listener) {
            interceptors.delete(listener);
            page.off(event, listener);
            return proxy;
        },
    };
    const proxy = new Proxy(page, {
        get: (target, property) => property in own ? own[property] : typeof target[property] === 'function' ? target[property].bind(target) : target[property],
    });
    proxies.set(page, proxy);

    return proxy;
};

export async function launch(name = 'chromium') {
    const project = projects[name];
    if (!project) {
        throw new Error(`Unknown project "${name}": ${Object.keys(projects).join(', ')}`);
    }
    const executablePath = project.type === chromium ? process.env.CHROME_PATH : undefined;
    const browser = await project.type.launch({executablePath, args: project.type === chromium ? ['--no-sandbox'] : []});
    const context = await browser.newContext(project.context);

    return {
        name,
        // Same device for every page, like the browser of Puppeteer.
        newPage: async () => adapt(await context.newPage(), context),
        pages: async () => context.pages().map((page) => adapt(page, context)),
        // A page opened by the one tested (a link in a new tab), as targetcreated of Puppeteer.
        once: (event, listener) => context.once(event === 'targetcreated' ? 'page' : event, listener),
        version: () => `${name}: ${project.type.name()} ${browser.version()}`,
        close: () => browser.close(),
    };
}
