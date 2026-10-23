import { Controller } from '@hotwired/stimulus';

/*
 * Focus of the title of the page: after a visit of Turbo, the focus goes to the <h1 id="page-title"> (PageHeader of
 * CruditUiBundle, tabindex -1), so a screen reader starts at the new page, as a full load does. Not on the first load
 * (the page starts from the top, the skip links first), not on a refresh of the same address, not when the address
 * has an anchor (#…: the browser goes there). Nothing without Turbo.
 *
 * One listener for the page, set when the module is loaded: Turbo replaces <body> at each visit, and a listener of a
 * controller on it would depend on the order of its connect and of turbo:load. A visit is told from the first load by
 * event.detail.timing: Turbo gives the timing of a visit, an empty object on the first load (the only sign it gives).
 */
let lastUrl = window.location.href;

document.addEventListener('turbo:load', (event) => {
    const url = window.location.href;
    const visit = Object.keys(event.detail?.timing ?? {}).length > 0;
    const moved = url !== lastUrl;
    lastUrl = url;
    if (visit && moved && window.location.hash === '') {
        document.getElementById('page-title')?.focus({preventScroll: true});
    }
});

// On <body> (layout/base.html.twig): it loads this module, also with `fetch: lazy` in controllers.json
export default class extends Controller {}
