window.addEventListener('load', function () {
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
});
