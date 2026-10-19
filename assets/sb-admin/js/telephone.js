'use strict';

// TelephoneType: shows the calling code of the selected country in the compact box.
function syncCallingCode(select) {
    const code = select.closest('.crudit-telephone')?.querySelector('.crudit-telephone-code');
    const option = select.selectedOptions[0];
    if (code && option) {
        // The "+ ?" placeholder of a legacy value has no data-code: its label is shown instead
        code.textContent = option.dataset.code ?? option.text;
    }
}

function syncAll(root) {
    root.querySelectorAll('.crudit-telephone-country').forEach(syncCallingCode);
}

// Delegated on document so that fields added later (collections) are covered too
document.addEventListener('change', (event) => {
    if (event.target.matches('.crudit-telephone-country')) {
        syncCallingCode(event.target);
    }
});

// After a back navigation (bfcache) or a form reset, the select may no longer match the box
window.addEventListener('pageshow', () => syncAll(document));
document.addEventListener('reset', (event) => {
    // The reset event fires before the values are restored
    setTimeout(() => syncAll(event.target));
});
