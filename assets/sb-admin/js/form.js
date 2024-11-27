'use strict';

import TomSelect from "tom-select";

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
    document.querySelectorAll('.form-select').forEach(select => {
        new TomSelect('#' + select.id,
            {
                plugins: [
                    'remove_button',
                ],
                onItemAdd() {
                    select.parentElement.querySelector('.ts-input > input').value = '';
                    select.parentElement.querySelector('.ts-dropdown').style.display = 'none';
                },
            },
        );
    });
});
