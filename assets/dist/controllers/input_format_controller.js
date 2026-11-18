import { Controller } from '@hotwired/stimulus';

/*
 * Format helpers of the 1.x (classes of a field, lot 3.2.b), on the input; put by the form theme of Crudit when the
 * field has one of the classes. They act once the field is left, not while typing (the value does not change under
 * the fingers):
 * - crudit-upper-format, crudit-lower-format: the value in capitals, in small letters;
 * - crudit-email-format: in small letters, and aria-invalid when it is not an e-mail address;
 * - crudit-ip-format: aria-invalid when it is not an IPv4 address. While typing at the end of the value, a point is
 *   added once a byte is 26 or more or has 3 digits (as in the 1.x: a 3rd digit would go over 255); a point typed
 *   just after it, by habit, is ignored.
 * The invalid state set here is removed once the value is right. An error of the server (aria-invalid from the theme,
 * a value already used for example) is kept until the form is sent again: its message is still shown.
 */
const IP = /^((\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$/;
const EMAIL = /^\S+@\S+\.\S+$/;

export default class extends Controller {
    connect() {
        const classes = this.element.classList;
        this.upper = classes.contains('crudit-upper-format');
        this.lower = classes.contains('crudit-lower-format') || classes.contains('crudit-email-format');
        this.check = classes.contains('crudit-email-format') ? EMAIL : (classes.contains('crudit-ip-format') ? IP : null);
        this.ip = classes.contains('crudit-ip-format');
        this.serverInvalid = this.element.getAttribute('aria-invalid') === 'true';

        this.onChange = () => this.format();
        this.onInput = (event) => this.addPoint(event);
        this.element.addEventListener('change', this.onChange);
        if (this.ip) {
            this.element.addEventListener('input', this.onInput);
        }
    }

    disconnect() {
        this.element.removeEventListener('change', this.onChange);
        this.element.removeEventListener('input', this.onInput);
    }

    format() {
        let value = this.element.value;
        if (this.upper) {
            value = value.toUpperCase();
        } else if (this.lower) {
            value = value.toLowerCase();
        }
        if (value !== this.element.value) {
            this.element.value = value;
        }
        if (!this.check) {
            return;
        }
        if (value === '' || this.check.test(value)) {
            if (!this.serverInvalid) {
                this.element.removeAttribute('aria-invalid');
            }
        } else {
            this.element.setAttribute('aria-invalid', 'true');
        }
    }

    // A point once a byte is 26 or more or has 3 digits, only when typing at the end (not when correcting in the middle)
    addPoint(event) {
        const input = this.element;
        if (event.inputType !== 'insertText' || input.selectionStart !== input.value.length) {
            return;
        }
        if (event.data === '.' && input.value.endsWith('..')) {
            // The point was already added: the one typed by habit is dropped
            input.value = input.value.slice(0, -1);

            return;
        }
        const parts = input.value.split('.');
        const byte = parts[parts.length - 1];
        if (parts.length < 4 && (/^\d{3}$/.test(byte) || (/^\d{2}$/.test(byte) && Number(byte) >= 26))) {
            input.value = `${input.value}.`;
        }
    }
}
