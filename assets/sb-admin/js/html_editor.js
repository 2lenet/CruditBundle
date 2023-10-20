'use strict';

window.addEventListener('load', function () {
    let previews = document.querySelectorAll('.crudit_html_editor_preview');

    previews.forEach(preview => {
        preview.addEventListener('click', function () {
            let ckeditor = CKEDITOR.instances[preview.dataset.inputId];
            if (ckeditor) {
                let content = parseHtml(ckeditor.getData()).textContent;

                let modal = document.getElementById(preview.dataset.inputId + '_modal');
                let modalBody = modal.querySelector('.modal-body');

                modalBody.innerHTML = content;
            }
        });
    });
});

function parseHtml(html) {
    let template = document.createElement('template');
    template.innerHTML = html;

    return template.content;
}