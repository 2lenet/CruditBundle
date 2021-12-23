'use strict';

window.addEventListener('load', function () {
    const dropdownItems = document.querySelectorAll('.crudit-batch-dropdown-item');
    const batchCheckAll = document.getElementById('crudit-batch-check-all');
    const checkboxes = document.querySelectorAll('.crudit-batch-check');

    let ids = [];

    // Check or uncheck all checkboxes
    batchCheckAll.addEventListener('change', (event) => {
        checkboxes.forEach(checkbox => {
            checkbox.checked = batchCheckAll.checked;
            ids = saveCheckedId(event.currentTarget, ids, checkbox);
        });

        showBatchList(ids);
    });

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', (event) => {
            ids = saveCheckedId(event.currentTarget, ids, checkbox);

            batchCheckAll.checked = true;
            checkboxes.forEach(checkbox => {
                if (!checkbox.checked) {
                    batchCheckAll.checked = false;
                }
            });

            showBatchList(ids);
        })
    });


    // Hide all forms
    hideBatchForms();

    // Display the form if batch action contains one
    dropdownItems.forEach(dropdownItem => {
        dropdownItem.addEventListener('click', () => {
            if (dropdownItem.dataset.form) {
                document.querySelectorAll('.batch_action_form').forEach(form => {
                    hideBatchForms();

                    document.getElementById(dropdownItem.dataset.form).classList.remove('d-none');
                });
            } else {
                event.currentTarget.href = event.currentTarget.href + '?ids' + ids.join(',');
            }
        });
    });
});

function showBatchList(ids) {
    const batchList = document.querySelector('.crudit-batch-list');

    if (ids.length > 0) {
        batchList.classList.remove('d-none');
    } else {
        batchList.classList.add('d-none');
    }
}

function hideBatchForms() {
    document.querySelectorAll('.batch_action_form').forEach(form => {
        form.classList.add('d-none');
    });
}

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