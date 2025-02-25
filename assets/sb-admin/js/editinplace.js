'use strict';

//called when the DOM tree is built (before load)
//avoid checkbox to be checked/unchecked before the click event is set
window.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.crudit-eip-input[type=\'checkbox\']').forEach(eip_checkbox => {
        if (eip_checkbox.disabled) {
            //save the initial disabled value
            eip_checkbox.setAttribute('initial-disabled', eip_checkbox.getAttribute('disabled'));
        }

        eip_checkbox.disabled = true;
    });
});

window.addEventListener('load', function () {
    document.querySelectorAll('.crudit-eip').forEach(eip_elem => {
        createEipField(eip_elem);
    });
});

function createEipField(eip_elem) {
    let eip_val = eip_elem.querySelector('.crudit-eip-value');
    let eip_submit = eip_elem.querySelector('.crudit-eip-submit');
    let eip_input = eip_elem.querySelector('.crudit-eip-input');
    let eip_cancel = eip_elem.querySelector('.crudit-eip-cancel');
    let eip_form = eip_elem.querySelector('form');

    //if input type is checkbox retrieve initial disabled value
    if (eip_input.type === 'checkbox') {
        if (eip_input.hasAttribute('initial-disabled')) {
            eip_input.setAttribute('disabled', eip_input.getAttribute('initial-disabled'));
            eip_input.removeAttribute('initial-disabled');
        } else {
            eip_input.disabled = false;
        }
    }

    if (eip_val) {
        eip_val.addEventListener('click', () => {
            eip_val.classList.toggle('d-none');
            if (eip_form) {
                eip_form.classList.toggle('d-none');
            }

            // put user cursor in the text input
            if (eip_input.type === 'text' || eip_input.tagName === 'TEXTAREA') {
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
        eip_form.addEventListener('submit', (e) => {
            // disable submit, because the save is async
            e.preventDefault();
            submitEIP(eip_elem, eip_input, eip_val);
        });
    }

    eip_submit.addEventListener('click', () => submitEIP(eip_elem, eip_input, eip_val));
}

function submitEIP(eip_elem, eip_input, eip_val) {
    eip_input = eip_elem.querySelector('.crudit-eip-input');

    let url = eip_elem.dataset.edit_url;
    let formData = new FormData();

    let value = eip_input.type === 'checkbox' ? eip_input.checked : eip_input.value;
    let data = {
        [eip_elem.dataset.field]: value,
    };

    formData.append('data', JSON.stringify(data));
    fetch(url,
        {
            body: formData,
            method: 'post',
        },
    ).then((response) => {
        if (response.status === 200) {
            response.json().then((json) => {
                Object.entries(json.fieldsToUpdate).forEach(([key, html]) => {
                    let eipElement = document.getElementById(key);
                    if (eipElement) {
                        eipElement.innerHTML = html;
                    }
                });

                if ('eipToUpdate' in json) {
                    json.eipToUpdate.forEach(field => {
                        let eip_elem = document.getElementById(field);
                        if (eip_elem) {
                            let cruditEipElem = eip_elem.querySelector('.crudit-eip');
                            if (cruditEipElem) {
                                createEipField(cruditEipElem);
                            }
                        }
                    });
                }
            });
        } else if (response.status >= 400) {
            // error, tell the user
            response.json().then((json) => {
                addFlash(json.message || 'Error while saving EIP.', 'danger');
            }).catch(() => {
                addFlash('Unknown error, please contact an administrator.', 'danger');
            });
        }
    });

    if (eip_val) {
        if (eip_input.type === 'date') {
            eip_val.textContent = new Date(eip_input.value).toLocaleDateString();
        } else if (eip_input.tomselect !== undefined) {
            let entityId = eip_input.tomselect.getValue();
            let entity = eip_input.tomselect.options[entityId];

            if (entity) {
                eip_val.textContent = entity.text;
            } else {
                eip_val.textContent = '';
            }
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

export function addFlash(message, type = 'success') {
    let flash = document.createElement('div');
    flash.classList.add('alert', 'alert-dismissible');
    flash.classList.add('alert-' + type);
    flash.setAttribute('role', 'alert');
    flash.innerText = message;

    let closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.classList.add('btn-close');
    closeButton.setAttribute('data-bs-dismiss', 'alert');

    flash.append(closeButton);

    document.querySelector('#crudit-flash').append(flash);
}
