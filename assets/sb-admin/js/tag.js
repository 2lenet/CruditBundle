'use strict';

import {addFlash} from './editinplace';

window.addEventListener('load', function () {
    let tagsElement = document.querySelectorAll('.crudit-tag');
    if (tagsElement) {
        tagsElement.forEach((tagElement) => {
            tagElement.addEventListener('click', () => {
                let editRoute = tagElement.dataset.editRoute;
                fetch(editRoute)
                    .then((response) => {
                        if (response.status === 200) {
                            changeTagDesign(tagElement);
                        } else if (response.status >= 400) {
                            response.json().then((json) => {
                                addFlash(json.message || 'Error while saving data.', 'danger');
                            }).catch(() => {
                                addFlash('Unknown error, please contact an administrator.', 'danger');
                            });
                        }
                    });
            });
        });
    }
});

function changeTagDesign(tagElement) {
    let spanElement = tagElement.querySelector('span');
    if (spanElement) {
        if (spanElement.classList.contains('bg-secondary')) {
            spanElement.classList.remove('bg-secondary');
            spanElement.classList.add('bg-primary');
        } else {
            spanElement.classList.remove('bg-primary');
            spanElement.classList.add('bg-secondary');
        }

        let icon = tagElement.querySelector('i');
        if (icon) {
            if (icon.classList.contains('fa-square')) {
                icon.classList.remove('fas', 'fa-square');
                icon.classList.add('fas', 'fa-check-square');
            } else {
                icon.classList.remove('fas', 'fa-check-square');
                icon.classList.add('fas', 'fa-square');
            }
        }
    }
}
