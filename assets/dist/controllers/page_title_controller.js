import { Controller } from '@hotwired/stimulus';

// The address of the last page shown. In the module, not in the controller: Turbo replaces <body>, and the
// controller with it, at each visit, and the new one connects after the address has changed
let lastUrl = window.location.href;

/*
 * Focus of the title of the page (on <body>, layout/base.html.twig): after a visit of Turbo, the focus goes to the
 * <h1 id="page-title"> (PageHeader of CruditUiBundle, tabindex -1), so a screen reader starts at the new page, as a
 * full load does. Not on the first load (the page starts from the top, the skip links first: Turbo gives no timing of
 * a visit then), not on a refresh of the same address, not when the address has an anchor (#…: the browser goes
 * there). Nothing without Turbo.
 */
export default class extends Controller {
    connect() {
        this.onLoad = (event) => {
            const url = window.location.href;
            const visit = Object.keys(event.detail?.timing ?? {}).length > 0;
            const moved = url !== lastUrl;
            lastUrl = url;
            if (visit && moved && window.location.hash === '') {
                document.getElementById('page-title')?.focus({preventScroll: true});
            }
        };
        document.addEventListener('turbo:load', this.onLoad);
    }

    disconnect() {
        document.removeEventListener('turbo:load', this.onLoad);
    }
}
