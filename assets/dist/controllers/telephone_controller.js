import { Controller } from '@hotwired/stimulus';

/*
 * TelephoneType (form/_telephone_widget.html.twig): shows the calling code of the selected country in the compact
 * box. One controller per field, so the fields added later (collections) are covered too.
 */
export default class extends Controller {
    static targets = ['country', 'code'];

    connect() {
        // After a back navigation (bfcache) or a form reset, the select may no longer match the box
        this.onPageShow = () => this.sync();
        this.onReset = () => {
            // The reset event fires before the values are restored
            setTimeout(() => this.sync());
        };
        // this.element is the <div> of the field, which has no `form`
        this.form = this.element.closest('form');
        window.addEventListener('pageshow', this.onPageShow);
        this.form?.addEventListener('reset', this.onReset);
        this.sync();
    }

    disconnect() {
        window.removeEventListener('pageshow', this.onPageShow);
        this.form?.removeEventListener('reset', this.onReset);
    }

    sync() {
        const option = this.countryTarget.selectedOptions[0];
        if (option) {
            // The "+ ?" placeholder of a legacy value has no data-code: its label is shown instead
            this.codeTarget.textContent = option.dataset.code ?? option.text;
        }
    }
}
