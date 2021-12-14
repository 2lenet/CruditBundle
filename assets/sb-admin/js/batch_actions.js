'use strict';

window.addEventListener('load', function () {
    let ids = [];

    const batchCheckAll = document.getElementById('crudit-batch-check-all');
    const checkboxes = document.querySelectorAll('.crudit-batch-check');

    // TODO: When we refresh the page with checked checkboxes, the button to validate the batch action isn't show

    // Check or uncheck all checkboxes
    batchCheckAll.addEventListener('change', (event) => {
        for (let checkbox of checkboxes) {
            checkbox.checked = batchCheckAll.checked;

            ids = saveCheckedId(event.currentTarget, ids, checkbox);
        }

        manageClassButton(ids);
    });

    // On change event on each checkboxes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', (event) => {
            ids = saveCheckedId(event.currentTarget, ids, checkbox);
            manageClassButton(ids);

            batchCheckAll.checked = true;
            for (let checkbox of checkboxes) {
                if (!checkbox.checked) {
                    batchCheckAll.checked = false;
                    break;
                }
            }
        })
    });

    // Rewrite url
    document.querySelectorAll(".crudit-batch-button").forEach(button_elem => {
        button_elem.addEventListener('click', (event) => {
            event.currentTarget.href = event.currentTarget.href+'?ids='+ids.join(',');
        });
    });
});

// Save ids into array
function saveCheckedId(target, ids, checkbox) {
    if (target.checked) {
        ids.push(checkbox.dataset.id);
    } else {
        const index = ids.indexOf(checkbox.dataset.id);
        if (index > -1) {
            ids.splice(index, 1);
        }
    }

    return ids;
}

// Modify class of button
function manageClassButton(ids) {
    const batchlist = document.querySelector(".crudit-batch-list");

    if (ids.length > 0) {
        batchlist.classList.remove('d-none');
    } else {
        batchlist.classList.add('d-none');
    }
}