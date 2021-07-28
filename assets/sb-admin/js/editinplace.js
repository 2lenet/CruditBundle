'use strict';

window.addEventListener('load', function () {

    document.querySelectorAll(".crudit-eip").forEach(eip_elem => {
        let eip_val = eip_elem.querySelector(".crudit-eip-value");
        eip_val.addEventListener('click', () => {
            eip_val.classList.toggle('d-none');
            eip_elem.querySelector('form').classList.toggle('d-none');
        });

        let eip_cancel = eip_elem.querySelector(".crudit-eip-cancel");
        eip_cancel.addEventListener('click', () => {
            eip_val.classList.toggle('d-none');
            eip_elem.querySelector('form').classList.toggle('d-none');
        })

        let eip_submit = eip_elem.querySelector(".crudit-eip-submit");
        eip_submit.addEventListener('click', (event) => {
            let input = eip_elem.querySelector("input");

            let url = eip_elem.dataset.edit_url;
            let formData = new FormData();

            let data = {
                [eip_elem.dataset.field]: input.value,
            }
            formData.append("data", JSON.stringify(data));
            fetch(url,
                {
                    body: formData,
                    method: "post"
                }
            );

            eip_val.textContent = input.value;
            eip_val.classList.toggle('d-none');
            eip_elem.querySelector('form').classList.toggle('d-none');
            event.preventDefault();
        })
    });

});
