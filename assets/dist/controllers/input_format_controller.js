import { Controller } from '@hotwired/stimulus';

/*
 * Format helpers of the 1.x (classes of a field, lot 3.2.b), on the input; put by the form theme of Crudit when the
 * field has one of the classes. They act once the field is left, not while typing (the value does not change under
 * the fingers):
 * - crudit-upper-format, crudit-lower-format: the value in capitals, in small letters;
 * - crudit-email-format: in small letters, and aria-invalid when it is not an e-mail address;
 * - crudit-ip-format: aria-invalid when it is not an IPv4 address. While typing at the end of the value, a point is
 *   added after the 3rd digit of a byte.
 * The invalid state set here is removed once the value is right; an error of the server (aria-invalid from the theme)
 * is kept until the field is left with a right value.
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
            this.element.removeAttribute('aria-invalid');
        } else {
            this.element.setAttribute('aria-invalid', 'true');
        }
    }

    // A point after the 3rd digit of a byte, only when typing at the end (not when correcting in the middle)
    addPoint(event) {
        const input = this.element;
        if (event.inputType !== 'insertText' || input.selectionStart !== input.value.length) {
            return;
        }
        const parts = input.value.split('.');
        if (parts.length < 4 && /^\d{3}$/.test(parts[parts.length - 1])) {
            input.value = `${input.value}.`;
        }
    }
}
