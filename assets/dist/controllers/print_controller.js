import { Controller } from '@hotwired/stimulus';

/*
 * Print of the page (PrintAction): the button prints, without inline script (strict CSP). The bar of the actions and
 * the layout are hidden by the styles of print.
 */
export default class extends Controller {
    print() {
        window.print();
    }
}
