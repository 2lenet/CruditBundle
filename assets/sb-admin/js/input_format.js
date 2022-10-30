'use strict';

window.addEventListener('load', function () {

    document.querySelectorAll(".crudit-ip-format").forEach(input_elem => {

        input_elem.addEventListener('keyup', () => {
            if (!input_elem.value.match(/^((\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$/)) {
                input_elem.classList.toggle('is-invalid', true);
                const nbPoints = input_elem.value.match(/\./);
                if (nbPoints && nbPoints<3) {
                    const lastnumber = input_elem.value.match(/[0-9]+$/);
                    if (lastnumber && parseInt(lastnumber) >= 26) {
                        input_elem.value = input_elem.value + "."
                    }
                }
            } else {
                input_elem.classList.toggle('is-invalid', false);
                input_elem.classList.toggle('is-valid', true);
            }
        })
    })
    document.querySelectorAll(".crudit-upper-format").forEach(input_elem => {
        input_elem.addEventListener('blur', () => {
            input_elem.value = input_elem.value.toUpperCase();
        });
    });
    document.querySelectorAll(".crudit-lower-format").forEach(input_elem => {
        input_elem.addEventListener('blur', () => {
            input_elem.value = input_elem.value.toLowerCase();
        });
    });
    document.querySelectorAll(".crudit-email-format").forEach(input_elem => {
        input_elem.addEventListener('keyup', () => {
            input_elem.value = input_elem.value.toLowerCase();
            const re = /\S+@\S+\.\S+/;
            if( re.test(input_elem.value)) {
                input_elem.classList.toggle('is-invalid', false);
                input_elem.classList.toggle('is-valid', true);
            } else {
                input_elem.classList.toggle('is-invalid', true);
                input_elem.classList.toggle('is-valid', false);
            }
        })
    });
})
;
