'use strict';

window.addEventListener('load', function () {
    let ids = [];
    let batchlist = document.querySelector(".crudit-batch-list");

    document.querySelectorAll(".crudit-batch-check").forEach(check_elem => {
        check_elem.addEventListener('change', (event) => {
            if (event.currentTarget.checked) {
                ids.push(check_elem.dataset.id);
            } else {
                const index = ids.indexOf(check_elem.dataset.id);
                if (index > -1) {
                    ids.splice(index, 1);
                }
            }
            if (ids.length > 0) {
                batchlist.classList.remove('d-none');
            } else {
                batchlist.classList.add('d-none');
            }
        })

    });
    document.querySelectorAll(".crudit-batch-button").forEach(button_elem => {
        button_elem.addEventListener('click', (event) => {
            event.currentTarget.href = event.currentTarget.href+'?ids='+ids.join(',');
        });
    });
});
