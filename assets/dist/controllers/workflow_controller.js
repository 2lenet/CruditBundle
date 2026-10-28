import { Controller } from '@hotwired/stimulus';

/*
 * Transition of a workflow (field/workflow.html.twig): an item of the menu of the status opens the dialog of the layout
 * (crudit-confirm, data-confirm-dispatch), whose "Confirm" dispatches "2lenet--crudit-bundle--confirm:confirmed" on
 * the item. The controller then sends the transition (POST, CSRF token) and, on success, replaces the field by the one
 * of the answer, says the change in a toast and gives the focus back to the status. The other copies of the field of
 * the same element (the table of a list renders a cell twice: its line and its card, for the phones) are replaced
 * too. The field carries no id (its Dropdown has none), so a copy is the same HTML. On an error, the status stays as
 * it was and the message of the server (or the generic one, error-value) is shown in a toast that stays.
 */
export default class extends Controller {
    static targets = ['trigger'];
    static values = {url: String, token: String, name: String, field: String, error: String};

    async apply(event) {
        const transition = event.target.dataset.transition;
        if (!transition || this.busy) {
            return;
        }
        this.busy = true;
        const trigger = this.hasTriggerTarget ? this.triggerTarget : null;
        trigger?.setAttribute('aria-busy', 'true');
        trigger?.focus();

        const body = new FormData();
        body.append('_token', this.tokenValue);
        body.append('transition', transition);
        body.append('name', this.nameValue);
        body.append('field', this.fieldValue);

        let data = null;
        let ok = false;
        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            });
            data = await response.json();
            ok = response.ok && data.status === 'ok' && typeof data.html === 'string';
        } catch {
            data = null;
        }

        if (ok) {
            const template = document.createElement('template');
            template.innerHTML = data.html.trim();
            const field = template.content.firstElementChild;
            if (field !== null) {
                this.copies().forEach((copy) => copy.replaceWith(field.cloneNode(true)));
                this.element.replaceWith(field);
                this.toast(data.message, 'success');
                // The status of the new field: its button, else the field itself (no transition left).
                const target = field.querySelector('[data-2lenet--crudit-bundle--workflow-target="trigger"]');
                if (target !== null) {
                    target.focus();
                } else {
                    field.setAttribute('tabindex', '-1');
                    field.focus();
                }

                return;
            }
        }

        this.busy = false;
        trigger?.removeAttribute('aria-busy');
        this.toast(data?.message || this.errorValue, 'danger');
    }

    // The other fields of the same element in the page (same transition address, same field)
    copies() {
        const url = CSS.escape(this.urlValue);
        const field = CSS.escape(this.fieldValue);
        const selector = `[data-2lenet--crudit-bundle--workflow-url-value="${url}"][data-2lenet--crudit-bundle--workflow-field-value="${field}"]`;

        return [...document.querySelectorAll(selector)].filter((element) => element !== this.element);
    }

    toast(message, tone) {
        if (message) {
            document.dispatchEvent(new CustomEvent('crudit-ui:toast', {detail: {message, tone}}));
        }
    }
}
