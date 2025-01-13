function initConfirmModals() {
    const confirmModal = document.getElementById('modal-confirm');
    const confirmModalText = document.getElementById('modal-confirm-text');
    const confirmModalLink = document.getElementById('modal-confirm-link');

    if (confirmModal && confirmModalLink) {
        confirmModal.addEventListener('show.bs.modal', function (event) {
            const text = event.relatedTarget.getAttribute('data-confirm-text');
            if (text) {
                confirmModalText.innerText = text;
            }

            const href = event.relatedTarget.getAttribute('data-confirm-link');
            confirmModalLink.setAttribute('href', href);
        });
    }
}

// Avoid double clic on actions
function addTimeoutOnCruditActions() {
    document.querySelectorAll('.crudit-action').forEach(choice => {
        choice.addEventListener('click', () => {
            choice.classList.add('disabled');
        });
    });
}

window.addEventListener('load', function () {
    initConfirmModals();
    addTimeoutOnCruditActions()
});
