'use strict';

window.addEventListener('load', function () {

    document.querySelectorAll(".crudit-eip").forEach(eip_elem => {
        let eip_val = eip_elem.querySelector(".crudit-eip-value");
        if (eip_val) {
            eip_val.addEventListener('click', () => {
                eip_val.classList.toggle('d-none');
                eip_elem.querySelector('form').classList.toggle('d-none');
            });
        }

        let eip_cancel = eip_elem.querySelector(".crudit-eip-cancel");
        if (eip_cancel && eip_val) {
            eip_cancel.addEventListener('click', () => {
                eip_val.classList.toggle('d-none');
                eip_elem.querySelector('form').classList.toggle('d-none');
            });
        }

        let eip_submit = eip_elem.querySelector(".crudit-eip-submit");
        eip_submit.addEventListener('click', () => {
            let input = eip_elem.querySelector(".crudit-eip-input");

            let url = eip_elem.dataset.edit_url;
            let formData = new FormData();

            let value = input.type === "checkbox" ? input.checked : input.value;
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

                if (input.type === "date") {
                    eip_val.textContent = new Date(input.value).toLocaleDateString();
                } else {
                    eip_val.textContent = input.value;
                }

                eip_val.classList.toggle('d-none');
            }

            let form = eip_elem.querySelector('form');
            if (form) {
                form.classList.toggle('d-none');
            }
        });
    });

});
