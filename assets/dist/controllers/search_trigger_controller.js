import { Controller } from '@hotwired/stimulus';

/*
 * Button of the global search (layout/header/_search.html.twig):
 * - the shortcut shown is the one of the platform: "⌘ K" on a Mac or an iPad, "Ctrl K" elsewhere (both work);
 * - the button is hidden when the page has no palette to open (a project emptied the block `search_dialog` but kept
 *   `header_search`): it would do nothing.
 */
export default class extends Controller {
    static targets = ['shortcut'];

    connect() {
        if (!document.getElementById(this.element.getAttribute('commandfor') ?? '')) {
            this.element.hidden = true;

            return;
        }

        const platform = navigator.userAgentData?.platform ?? navigator.platform ?? '';
        if (/mac|iphone|ipad/i.test(platform) && this.hasShortcutTarget) {
            this.shortcutTarget.textContent = '⌘ K';
        }
    }
}
