'use strict';

window.addEventListener('load', function () {
    const batchCheckAll = document.getElementById('crudit-batch-check-all');
    const checkboxes = document.querySelectorAll('.crudit-batch-check');
    const dropdownItems = document.querySelectorAll('.crudit-batch-dropdown-item');
    const batchActionPage = document.getElementById('batch-action-page');

    let ids = [];

    // Check/uncheck all checkboxes
    // Show batch list if all checkboxes are checked
    if (batchCheckAll) {
        batchCheckAll.addEventListener('change', () => {
            checkboxes.forEach(checkbox => {
                checkbox.checked = batchCheckAll.checked;
            });

            // Show page form (this page / all page) if all checkboxes are checked
            showBatchActionPage(batchActionPage, batchCheckAll);

            ids = saveIds();
            showBatchList(batchCheckAll.checked);
        });
    }

    // Show batch list if at least 1 checkbox is checked
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            ids = saveIds();
            countChecked();
        });
    });

    // Modify url or request when we validate the batch action
    dropdownItems.forEach(dropdownItem => {
        dropdownItem.addEventListener('click', (event) => {
            if (dropdownItem.dataset.form) {
                let form = document.getElementById(dropdownItem.dataset.form);

                showForm(dropdownItem.dataset.form);

                form.addEventListener('submit', () => {
                    document.getElementById(form.name + '_ids').value = ids;

                    const formAllPage = document.getElementById(form.name + '_all_page');
                    if (formAllPage) {
                        formAllPage.value = getAllPageValue(batchActionPage, batchCheckAll);
                    }
                });
            } else {
                let url = new URL(event.currentTarget.href);
                url.searchParams.set('ids', ids);
                url.searchParams.set('all_page', getAllPageValue(batchActionPage, batchCheckAll));

                event.currentTarget.href = url.toString();
            }
        });
    });
});

function countChecked() {
    const batchCheckAll = document.getElementById('crudit-batch-check-all');
    const checkboxes = document.querySelectorAll('.crudit-batch-check');
    const batchActionPage = document.getElementById('batch-action-page');

    let checked = 0;

    // Count how many checkboxes are checked
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checked++;
        }
    });

    // Check/uncheck button batchCheckAll
    batchCheckAll.checked = (checked == checkboxes.length);

    // Show page form (this page / all page) if all checkboxes are checked
    showBatchActionPage(batchActionPage, batchCheckAll);

    // Show batch list if at least 1 checkbox is checked
    showBatchList((checked > 0));
}

// Show batch list
function showBatchList(showBatchList) {
    const batchList = document.querySelector('.crudit-batch-list');

    // Hide all forms
    hideForms();

    if (showBatchList) {
        batchList.classList.remove('d-none');
    } else {
        batchList.classList.add('d-none');
    }
}

// Hide all forms
function hideForms() {
    const forms = document.querySelectorAll('.batch-action-form');

    forms.forEach(form => {
        form.classList.add('d-none');
    });
}

// Show the requested form
function showForm(formId) {
    hideForms();

    document.getElementById(formId).classList.remove('d-none');
}

function saveIds() {
    const checkboxes = document.querySelectorAll('.crudit-batch-check');

    let ids = [];

    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            ids.push(checkbox.dataset.id);
        }
    });

    return ids = ids.join(',');
}

// Show page form (this page / all page) if all checkboxes are checked
function showBatchActionPage(batchActionPage, batchCheckAll) {
    if (batchActionPage && batchCheckAll.checked) {
        batchActionPage.classList.remove('d-none');
    } else if (batchActionPage && !batchCheckAll.checked) {
        batchActionPage.classList.add('d-none');
    }
}

function getAllPageValue(batchActionPage, batchCheckAll) {
    let allPage = 0;
    if (batchCheckAll.checked && batchActionPage) {
        if (document.querySelector('input[name="batch-action-page-form"]:checked').value === '1') {
            allPage = 1;
        }
    }

    return allPage;
}
