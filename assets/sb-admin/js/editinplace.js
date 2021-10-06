'use strict';

window.addEventListener('load', function () {

    document.querySelectorAll(".crudit-eip").forEach(eip_elem => {
        let eip_val = eip_elem.querySelector(".crudit-eip-value");
        let eip_submit = eip_elem.querySelector(".crudit-eip-submit");
        let eip_input = eip_elem.querySelector(".crudit-eip-input");
        let eip_cancel = eip_elem.querySelector(".crudit-eip-cancel");
        let eip_form = eip_elem.querySelector("form");

        if (eip_val) {
            eip_val.addEventListener('click', () => {
                eip_val.classList.toggle('d-none');
                if (eip_form) {
                    eip_form.classList.toggle('d-none');
                }

                // put user cursor in the text input
                if (eip_input.type === "text" || eip_input.tagName === "TEXTAREA") {
                    eip_input.focus();
                    setTimeout(function () {
                        eip_input.selectionStart = eip_input.selectionEnd = 10000;
                    }, 0);
                }
            });
        }

        if (eip_cancel && eip_val) {
            eip_cancel.addEventListener('click', () => {
                eip_val.classList.toggle('d-none');
                eip_elem.querySelector('form').classList.toggle('d-none');
            });
        }

        if (eip_form) {
            eip_form.addEventListener("submit", (e) => {
                // disable submit, because the save is async
                 e.preventDefault();
                submitEIP(eip_elem, eip_input, eip_val);
            });
        }

        eip_submit.addEventListener("click", () => submitEIP(eip_elem, eip_input, eip_val));
    });

    function submitEIP(eip_elem, eip_input, eip_val) {
        eip_input = eip_elem.querySelector(".crudit-eip-input");

        let url = eip_elem.dataset.edit_url;
        let formData = new FormData();

        let value = eip_input.type === "checkbox" ? eip_input.checked : eip_input.value;
        let data = {
            [eip_elem.dataset.field]: value,
        }
        formData.append("data", JSON.stringify(data));
        fetch(url,
            {
                body: formData,
                method: "post"
            }
        );

        if (eip_val) {

            if (eip_input.type === "date") {
                eip_val.textContent = new Date(eip_input.value).toLocaleDateString();
            } else {
                eip_val.textContent = eip_input.value;
            }

            eip_val.classList.toggle('d-none');
        }

        let form = eip_elem.querySelector('form');
        if (form) {
            form.classList.toggle('d-none');
        }
    }
});
