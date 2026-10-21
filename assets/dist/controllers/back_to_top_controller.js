import { Controller } from '@hotwired/stimulus';

// Scrolled distance from which the button is shown (as in the crudit-v2 mockup)
const THRESHOLD = 600;

/*
 * Back to the top (layout/_back_to_top.html.twig): the button is shown once the page has scrolled. A click goes back
 * to the top, smoothly unless motion is reduced, and the focus goes to the first skip link: the next Tab starts from
 * the top of the page, not from the bottom.
 */
export default class extends Controller {
    connect() {
        this.onScroll = () => {
            this.element.hidden = window.scrollY <= THRESHOLD;
        };
        window.addEventListener('scroll', this.onScroll, {passive: true});
        this.onScroll();
    }

    disconnect() {
        window.removeEventListener('scroll', this.onScroll);
    }

    top() {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({top: 0, behavior: reduce ? 'auto' : 'smooth'});
        document.getElementById('crudit-skip-to-content')?.focus({preventScroll: true});
    }
}
