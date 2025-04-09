// Handle CollectionType client side
// https://symfony.com/doc/current/form/form_collections.html

import {initTomSelect} from './filters';
import {initChoiceTomSelect} from './form';

let onLoad = (callback) => {
    if (document.readyState !== 'loading') {
        callback();
    } else {
        document.addEventListener('DOMContentLoaded', callback);
    }
}

onLoad(() => {
    document.querySelectorAll('.crudit-collection-add')
        .forEach(btn => {
            // add the remove buttons
            document.querySelectorAll(btn.dataset.target + ' > div')
                .forEach((tag) => {
                    if (tag.classList.contains('crudit-collection-ignore')) {
                        return;
                    }
                    addTagFormDeleteLink(tag);
                });
            btn.addEventListener('click', addFormToCollection);
        });
});

function addFormToCollection(e) {
    let collectionHolder = document.querySelector(e.target.dataset.target);
    let item = document.createElement('div');
    let name = collectionHolder.dataset.name;

    // The prototype is in an HTML attribute, so it's escaped
    // We use a textarea to decode it and be able to use it as HTML
    let txtArea = document.createElement('textarea');
    txtArea.innerHTML = collectionHolder
        .dataset
        .prototype;

    item.innerHTML = txtArea.value
        .replace(
            new RegExp(String.raw`${name}`, 'g'),
            collectionHolder.dataset.index,
        );
    item.querySelectorAll('.crudit-collection-add').forEach(btn => {
        btn.addEventListener('click', addFormToCollection);
    });

    if (collectionHolder.childElementCount > 1) {
        // > 1 because there's the labels row
        collectionHolder.appendChild(document.createElement('hr'));
    }
    collectionHolder.appendChild(item);
    addTagFormDeleteLink(collectionHolder.lastChild);
    collectionHolder.dataset.index++;

    initChoiceTomSelect();
    initTomSelect();
}

function addTagFormDeleteLink(item) {
    let removeFormButton = document.createElement('button');
    let icon = document.createElement('i');
    icon.classList = 'fa fa-trash pe-none';

    removeFormButton.appendChild(icon);
    removeFormButton.classList = 'btn btn-sm btn-link text-danger px-2 me-3 mt-1';

    let removeFormButtonWrapper = document.createElement('div');
    removeFormButtonWrapper.classList.add('col-1');
    removeFormButtonWrapper.classList.add('d-flex');
    removeFormButtonWrapper.classList.add('align-items-center');
    removeFormButtonWrapper.appendChild(removeFormButton);
    item.parentNode.insertBefore(removeFormButtonWrapper, item.nextSibling);

    removeFormButton.addEventListener('click', (e) => {
        e.preventDefault();
        item.remove();
        if (removeFormButtonWrapper.previousElementSibling.tagName === 'HR') {
            removeFormButtonWrapper.previousElementSibling.remove();
        }
        removeFormButtonWrapper.remove();
    });
}
