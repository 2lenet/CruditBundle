import { Controller } from '@hotwired/stimulus';

const COOKIE = 'crudit_sidebar';
const YEAR = 31536000;
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])';
const DISCLOSURE_TOGGLE = '2lenet--crudit-ui-bundle--disclosure:toggle';
const CLOSED = 'crudit-ui-disclosure__panel--closed';
const OPEN = 'crudit-sidebar--open';

const normalize = (text) => text.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();

/*
 * Menu of the layout (layout/base.html.twig, <div id="crudit-sidebar" role="complementary">: a <div>, because an
 * <aside> cannot have the role dialog, which the drawer takes when it is open). Three states, the same markup:
 *
 * - Full menu (from 1024 px, or always when the user chose it) and rail of icons (1024 to 1279 px by default, or by
 *   choice): the state is in `data-sidebar` of the shell, read from the cookie crudit_sidebar by the layout
 *   (auto, collapsed, expanded), so the page arrives in the right state; the CSS does the rest. The button of the
 *   footer writes the cookie. In the rail, a sub-menu is a panel next to its button (one open at a time, closed by
 *   Escape, a click outside or the focus leaving it).
 * - Drawer (below 1024 px): the same element, which becomes a dialog (role, aria-modal) when opened by the burger
 *   of the header or by the skip link "Skip to menu": the rest of the page is inert, the focus is trapped, Escape and
 *   the backdrop close it, the focus goes back to the burger. No native <dialog>: the menu is rendered once.
 *
 * The filter of the menu hides the items that do not match, opens the sub-menus that hold matches, and announces the
 * count (delayed, so as not to cut the typing). Only `sidebar`, `nav` and `body` are required: the filter, the button
 * that reduces the menu, the burger and the backdrop are blocks that a project can empty. Contract of the markup of the items: `data-menu-kind` ("item",
 * "heading" or "separator") on the <li>, `data-menu-label` on the items, `aria-current="page"` on the current link, a
 * sub-menu is an item with a disclosure (button aria-expanded and panel).
 */
export default class extends Controller {
    static targets = ['sidebar', 'body', 'backdrop', 'nav', 'filter', 'status', 'empty', 'toggle', 'toggleLabel', 'close', 'burger'];

    connect() {
        const style = getComputedStyle(document.documentElement);
        const lg = parseFloat(style.getPropertyValue('--breakpoint-lg'));
        const xl = parseFloat(style.getPropertyValue('--breakpoint-xl'));
        if (Number.isNaN(lg) || Number.isNaN(xl)) {
            throw new Error('Breakpoints not found: is the stylesheet of CruditUiBundle loaded?');
        }
        this.desktop = window.matchMedia(`(min-width: ${lg}px)`);
        this.wide = window.matchMedia(`(min-width: ${xl}px)`);
        this.drawerOpen = false;
        this.filterTimer = null;
        this.beforeFilter = null;
        this.beforeRail = null;
        this.sidebarLabel = this.sidebarTarget.getAttribute('aria-label');
        this.wasRail = false;

        this.onResize = () => this.sync();
        this.onKeydown = (event) => this.keydown(event);
        this.onNavScroll = () => this.followFlyout();
        this.onToggle = (event) => this.disclosureToggled(event);
        this.onPointerdown = (event) => this.closeFlyoutsOutside(event);
        this.onFocusout = (event) => this.focusLeft(event);
        this.onClick = (event) => this.click(event);
        this.onBeforeCache = () => this.reset();

        this.desktop.addEventListener('change', this.onResize);
        this.wide.addEventListener('change', this.onResize);
        document.addEventListener('keydown', this.onKeydown);
        this.navTarget.addEventListener('scroll', this.onNavScroll, {passive: true});
        this.element.addEventListener(DISCLOSURE_TOGGLE, this.onToggle);
        this.element.addEventListener('focusout', this.onFocusout);
        document.addEventListener('pointerdown', this.onPointerdown);
        document.addEventListener('click', this.onClick);
        document.addEventListener('turbo:before-cache', this.onBeforeCache);

        this.sync();
    }

    disconnect() {
        this.desktop.removeEventListener('change', this.onResize);
        this.wide.removeEventListener('change', this.onResize);
        document.removeEventListener('keydown', this.onKeydown);
        this.navTarget.removeEventListener('scroll', this.onNavScroll);
        this.element.removeEventListener(DISCLOSURE_TOGGLE, this.onToggle);
        this.element.removeEventListener('focusout', this.onFocusout);
        document.removeEventListener('pointerdown', this.onPointerdown);
        document.removeEventListener('click', this.onClick);
        document.removeEventListener('turbo:before-cache', this.onBeforeCache);
        window.clearTimeout(this.filterTimer);
        // The shell can go without the whole page being replaced: nothing is left inert.
        this.drawerOpen = false;
        this.render();
    }

    /** The menu is a rail: from 1024 px, by choice or (without choice) below 1280 px. */
    get rail() {
        const mode = this.element.dataset.sidebar;

        return this.desktop.matches && (mode === 'collapsed' || (mode === 'auto' && !this.wide.matches));
    }

    // Drawer: opening and closing

    open() {
        if (this.desktop.matches || this.drawerOpen) {
            return;
        }
        this.drawerOpen = true;
        this.render();
        (this.hasCloseTarget ? this.closeTarget : this.navTarget).focus();
    }

    close(event) {
        // The backdrop and the button "Close": the focus goes back to the burger.
        this.closeDrawer(event instanceof Event);
    }

    closeDrawer(restoreFocus) {
        if (!this.drawerOpen) {
            return;
        }
        this.drawerOpen = false;
        this.render();
        if (restoreFocus && this.hasBurgerTarget) {
            this.burgerTarget.focus();
        }
    }

    // The same markup in the three states: role and name of the dialog, inert, backdrop, burger.
    render() {
        const modal = this.drawerOpen && !this.desktop.matches;
        this.sidebarTarget.classList.toggle(OPEN, modal);
        if (this.hasBackdropTarget) {
            this.backdropTarget.hidden = !modal;
        }
        document.documentElement.classList.toggle('crudit-has-drawer', modal);
        if (modal) {
            this.sidebarTarget.setAttribute('role', 'dialog');
            this.sidebarTarget.setAttribute('aria-modal', 'true');
            this.sidebarTarget.setAttribute('aria-label', this.sidebarTarget.dataset.menuLabel);
        } else {
            this.sidebarTarget.setAttribute('role', 'complementary');
            this.sidebarTarget.removeAttribute('aria-modal');
            this.sidebarTarget.setAttribute('aria-label', this.sidebarLabel);
        }
        for (const part of [this.bodyTarget, document.querySelector('.crudit-skip-links')]) {
            if (part) {
                part.inert = modal;
            }
        }
        if (this.hasBurgerTarget) {
            this.burgerTarget.setAttribute('aria-expanded', String(modal));
        }
    }

    // Rail: the button of the footer, and the label that follows the state

    toggle() {
        const mode = this.rail ? 'expanded' : 'collapsed';
        this.element.dataset.sidebar = mode;
        document.cookie = `${COOKIE}=${mode}; path=/; max-age=${YEAR}; SameSite=Lax`;
        this.sync();
    }

    sync() {
        const drawerWasOpen = this.drawerOpen;
        if (this.desktop.matches && this.drawerOpen) {
            this.drawerOpen = false;
        }
        this.render();
        // The resize closed the drawer: its close button and the burger are gone, the focus goes to the menu.
        if (drawerWasOpen && !this.drawerOpen) {
            const active = document.activeElement;
            if (active === document.body || (active instanceof HTMLElement && active.offsetParent === null)) {
                this.navTarget.focus({preventScroll: true});
            }
        }

        const rail = this.rail;
        if (this.hasToggleTarget) {
            const label = rail ? this.toggleTarget.dataset.expandLabel : this.toggleTarget.dataset.collapseLabel;
            if (this.hasToggleLabelTarget) {
                this.toggleLabelTarget.textContent = label;
            }
            const tooltip = this.toggleTarget.closest('.crudit-ui-tooltip-host')?.querySelector('.crudit-ui-tooltip');
            if (tooltip) {
                tooltip.textContent = label;
            }
            this.toggleTarget.classList.toggle('crudit-sidebar__collapse--rail', rail);
        }

        if (rail !== this.wasRail) {
            this.wasRail = rail;
            if (rail) {
                // The sub-menus are panels next to their button, closed: the filter is hidden, its effect goes.
                this.clearFilter();
                this.beforeRail = this.parents().filter((item) => this.isOpen(item));
                this.parents().forEach((item) => this.setOpen(item, false));
            } else {
                this.parents().forEach((item) => this.setOpen(item, this.hasCurrent(item) || (this.beforeRail ?? []).includes(item)));
                this.beforeRail = null;
            }
        }
    }

    // Sub-menus (disclosures)

    parents() {
        return [...this.navTarget.querySelectorAll('[data-menu-kind="item"]')].filter((item) => item.querySelector(':scope > .crudit-nav__submenu') !== null);
    }

    isOpen(item) {
        return item.querySelector('[aria-expanded]')?.getAttribute('aria-expanded') === 'true';
    }

    hasCurrent(item) {
        return item.querySelector('[aria-current="page"]') !== null;
    }

    // The state of the disclosure is in the DOM (aria-expanded and class of the panel): the controller of CruditUiBundle reads it.
    setOpen(item, open) {
        const button = item.querySelector('[aria-expanded]');
        const panel = item.querySelector('.crudit-nav__submenu');
        if (!button || !panel) {
            return;
        }
        button.setAttribute('aria-expanded', String(open));
        panel.classList.toggle(CLOSED, !open);
        // The place of a flyout is only for the rail: out of it the panel is in the flow of the menu.
        panel.style.removeProperty('inset-block-start');
        delete panel.dataset.placed;
    }

    disclosureToggled(event) {
        const item = event.target.closest('[data-menu-kind="item"]');
        if (!item || !this.rail) {
            return;
        }
        if (event.detail.open) {
            // One panel at a time, placed next to its button.
            this.parents().filter((other) => other !== item).forEach((other) => this.setOpen(other, false));
            this.placeFlyout(item);
        }
    }

    // Next to its button, brought back into the window when it would go out of it. `data-placed` shows it (a panel of
    // the rail that is open but not placed yet, as rendered by the server, stays hidden).
    placeFlyout(item) {
        const panel = item.querySelector('.crudit-nav__submenu');
        const top = item.querySelector('[aria-expanded]').getBoundingClientRect().top;
        panel.dataset.placed = '';
        panel.style.insetBlockStart = `${top}px`;
        const overflow = panel.getBoundingClientRect().bottom - window.innerHeight;
        if (overflow > 0) {
            panel.style.insetBlockStart = `${Math.max(8, top - overflow - 8)}px`;
        }
    }

    // The menu scrolls under an open flyout: it follows its button.
    followFlyout() {
        if (!this.rail) {
            return;
        }
        const open = this.parents().find((item) => this.isOpen(item));
        if (open) {
            this.placeFlyout(open);
        }
    }

    closeFlyouts() {
        if (this.rail) {
            this.parents().forEach((item) => this.setOpen(item, false));
        }
    }

    closeFlyoutsOutside(event) {
        if (this.rail && !event.target.closest('.crudit-nav__item--parent')) {
            this.closeFlyouts();
        }
    }

    focusLeft(event) {
        const item = event.target.closest?.('.crudit-nav__item--parent');
        if (this.rail && item && event.relatedTarget && !item.contains(event.relatedTarget)) {
            this.setOpen(item, false);
        }
    }

    // Keyboard

    keydown(event) {
        if (event.key === 'Escape') {
            const open = this.rail ? this.parents().find((item) => this.isOpen(item)) : null;
            if (open) {
                this.setOpen(open, false);
                open.querySelector('[aria-expanded]').focus();
            } else if (this.drawerOpen) {
                this.closeDrawer(true);
            }

            return;
        }
        if (event.key === 'Tab' && this.drawerOpen) {
            this.trapFocus(event);
        }
    }

    // The rest of the page is inert: Tab goes round the drawer.
    trapFocus(event) {
        const focusables = [...this.sidebarTarget.querySelectorAll(FOCUSABLE)].filter((element) => element.offsetParent !== null);
        if (focusables.length === 0) {
            return;
        }
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        // The focus is on the page (a click on a part of the drawer that cannot take it): Tab comes back in.
        if (!this.sidebarTarget.contains(document.activeElement)) {
            event.preventDefault();
            (event.shiftKey ? last : first).focus();
        } else if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    click(event) {
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
        if (!link) {
            return;
        }
        // The skip link "Skip to menu" below 1024 px: the menu is in the drawer, closed.
        if (link.getAttribute('href') === '#main-nav' && !this.desktop.matches) {
            event.preventDefault();
            this.open();
            this.navTarget.focus();

            return;
        }
        // A link of the menu that changes the page (not a click that opens a new tab or window, an anchor, a link
        // without address): the focus goes to the new page, not back to the burger.
        const plain = event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey
            && link.target !== '_blank' && link.getAttribute('href') !== '#';
        if (plain && this.drawerOpen && this.sidebarTarget.contains(link)) {
            this.closeDrawer(false);
        }
    }

    // Filter of the menu

    filter() {
        if (!this.hasFilterTarget) {
            return;
        }
        const query = normalize(this.filterTarget.value.trim());
        const entries = [...this.navTarget.querySelectorAll('[data-menu-kind]')];
        const top = entries.filter((element) => !element.parentElement.closest('[data-menu-kind="item"]'));

        window.clearTimeout(this.filterTimer);
        if (query === '') {
            entries.forEach((element) => { element.hidden = false; });
            this.announce(true, null);
            if (this.beforeFilter !== null) {
                this.parents().forEach((item) => this.setOpen(item, this.beforeFilter.includes(item)));
                this.beforeFilter = null;
            }

            return;
        }
        if (this.beforeFilter === null) {
            this.beforeFilter = this.parents().filter((item) => this.isOpen(item));
        }

        const matches = (item) => normalize(item.dataset.menuLabel ?? '').includes(query);
        let count = 0;
        let heading = null;
        for (const element of top) {
            const kind = element.dataset.menuKind;
            if (kind === 'separator') {
                element.hidden = true;
            } else if (kind === 'heading') {
                element.hidden = true;
                heading = element;
            } else {
                const children = [...element.querySelectorAll('[data-menu-kind="item"]')];
                // A sub-menu whose label matches keeps all its links.
                const own = matches(element);
                children.forEach((child) => { child.hidden = !own && !matches(child); });
                const shown = children.length > 0 ? children.filter((child) => !child.hidden).length : (own ? 1 : 0);
                element.hidden = shown === 0;
                count += shown;
                if (children.length > 0) {
                    this.setOpen(element, shown > 0);
                }
                if (shown > 0 && heading) {
                    heading.hidden = false;
                }
            }
        }
        this.announce(count > 0, count);
    }

    // The message "no item" and the count read by assistive technologies (delayed: not to cut the typing).
    announce(emptyHidden, count) {
        if (this.hasEmptyTarget) {
            this.emptyTarget.hidden = emptyHidden;
        }
        if (!this.hasStatusTarget) {
            return;
        }
        this.statusTarget.textContent = '';
        if (count !== null) {
            const texts = this.statusTarget.dataset;
            this.filterTimer = window.setTimeout(() => {
                this.statusTarget.textContent = count === 0 ? texts.none : (count === 1 ? texts.one : texts.many.replace('%count%', String(count)));
            }, 500);
        }
    }

    clearFilter() {
        if (this.hasFilterTarget) {
            this.filterTarget.value = '';
            this.filter();
        }
    }

    // Before Turbo caches the page: nothing open, nothing filtered.
    reset() {
        this.closeDrawer(false);
        this.clearFilter();
        this.closeFlyouts();
    }
}
