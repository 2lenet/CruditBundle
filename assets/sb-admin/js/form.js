'use strict';

import TomSelect from "tom-select";

export function initChoiceTomSelect() {
    document.querySelectorAll('select.choice-tom-select:not(.tomselected)').forEach(select => {
        new TomSelect('#' + select.id,
            {
                plugins: [
                    'remove_button',
                ],
                onItemAdd() {
                    select.parentElement.querySelector('.ts-control > input').value = '';
                    select.parentElement.querySelector('.ts-dropdown').style.display = 'none';
                },
            },
        );
    });
}

window.addEventListener('load', function () {

    /**
     * Client side form validation with Bootstrap
     */
    let forms = document.querySelectorAll('.needs-validation');

    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');
            }, false);
        });

    /**
     * Apply TomSelect on choice types forms
     */
    initChoiceTomSelect();
});
