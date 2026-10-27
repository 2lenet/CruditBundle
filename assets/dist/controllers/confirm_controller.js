import { Controller } from '@hotwired/stimulus';

/*
 * Confirmation of an action (modal/_confirm.html.twig): one dialog of the layout (component Dialog of CruditUiBundle,
 * id crudit-confirm) for every action of the page with a confirmation (Action::setConfirmModal()). The element that
 * asks for it gives:
 *
 * - data-confirm-link: the address of the action, given to the link "Confirm";
 * - data-confirm-text: the question, the title of the dialog, which names the element concerned ("Delete the visit
 *   V-0412?"). Without it, the default question;
 * - data-confirm-label: the label of the button that confirms (the action: "Delete"), else "Confirm";
 * - data-confirm-danger: present for a destructive action, the button in danger, else in primary.
 *
 * A button opens the dialog by itself (commandfor="crudit-confirm" command="show-modal"): the controller reads the
 * button from the event "command". Any other element with data-confirm-link (the links of the actions, until lot
 * 3.1) opens it on a click, which the controller receives on the document.
 */
export default class extends Controller {
    static targets = ['link'];

    connect() {
        // The title of the Dialog, which names it (<id>-title)
        this.title = document.getElementById(`${this.element.id}-title`);
        this.defaultText = this.title.textContent;
        this.defaultLabel = this.linkTarget.textContent;

        this.onCommand = (event) => {
            if (event.command === 'show-modal' && event.source) {
                this.fill(event.source);
            }
        };
        this.onClick = (event) => {
            const trigger = event.target instanceof Element ? event.target.closest('[data-confirm-link]') : null;
            // A button with commandfor is read from its command; a disabled action is not confirmed.
            if (trigger === null || this.element.contains(trigger) || trigger.hasAttribute('commandfor')
                || trigger.matches('.disabled, [aria-disabled="true"]')) {
                return;
            }
            event.preventDefault();
            this.fill(trigger);
            this.element.showModal();
        };

        this.element.addEventListener('command', this.onCommand);
        document.addEventListener('click', this.onClick);
    }

    disconnect() {
        this.element.removeEventListener('command', this.onCommand);
        document.removeEventListener('click', this.onClick);
    }

    fill(trigger) {
        this.title.textContent = trigger.dataset.confirmText || this.defaultText;
        this.linkTarget.href = trigger.dataset.confirmLink ?? '#';
        this.linkTarget.textContent = trigger.dataset.confirmLabel || this.defaultLabel;
        const danger = trigger.dataset.confirmDanger !== undefined;
        this.linkTarget.classList.toggle('crudit-ui-btn--danger', danger);
        this.linkTarget.classList.toggle('crudit-ui-btn--primary', !danger);
    }
}
