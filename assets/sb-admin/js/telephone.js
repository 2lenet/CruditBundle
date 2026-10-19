'use strict';

// TelephoneType: copies the calling code of the selected country into the compact box.
// Delegated on document so that fields added later (collections) are covered too.
document.addEventListener('change', (event) => {
    if (!event.target.matches('.crudit-telephone-country')) {
        return;
    }

    const code = event.target.closest('.crudit-telephone').querySelector('.crudit-telephone-code');
    code.textContent = event.target.selectedOptions[0]?.dataset.code ?? '';
});
