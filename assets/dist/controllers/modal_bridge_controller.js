import { Controller } from '@hotwired/stimulus';

/*
 * Bridge for the modals of 1.x (on <body>, layout/base.html.twig): the modals are native <dialog> now, but the
 * triggers of Crudit and of the projects are links with data-bs-toggle="modal" data-bs-target="#<id>", which a button
 * with commandfor can not replace on a <a>. A click on such a trigger opens the dialog (showModal()), a click on
 * data-bs-dismiss="modal" closes the one it is in. A disabled trigger does nothing.
 *
 * Until lot 3.1: the triggers become <button commandfor="<id>" command="show-modal"> and this controller goes away.
 */
export default class extends Controller {
    connect() {
        this.onClick = (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            const dismiss = event.target.closest('[data-bs-dismiss="modal"]');
            if (dismiss !== null) {
                dismiss.closest('dialog')?.close();
                return;
            }

            const trigger = event.target.closest('[data-bs-toggle="modal"]');
            if (trigger === null || trigger.matches('.disabled, [aria-disabled="true"]')) {
                return;
            }

            const target = trigger.getAttribute('data-bs-target') ?? trigger.getAttribute('href');
            const dialog = target?.startsWith('#') ? document.getElementById(target.slice(1)) : null;
            if (dialog instanceof HTMLDialogElement) {
                event.preventDefault();
                dialog.showModal();
            }
        };

        document.addEventListener('click', this.onClick);
    }

    disconnect() {
        document.removeEventListener('click', this.onClick);
    }
}
