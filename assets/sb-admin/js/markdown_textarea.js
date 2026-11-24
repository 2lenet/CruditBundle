'use strict';

// EasyMDE (and CodeMirror) is only downloaded when the page has a markdown field.
document.addEventListener('DOMContentLoaded', function () {
    const textareas = document.querySelectorAll('textarea.markdown-editor');
    if (textareas.length === 0) {
        return;
    }

    import(/* webpackChunkName: "easymde" */ 'easymde').then(({ default: EasyMDE }) => {
        textareas.forEach(textarea => {
            new EasyMDE({
                element: textarea,
                toolbar: ['bold', 'italic', '|', 'heading-smaller', 'heading-bigger', '|', 'unordered-list', 'ordered-list', '|', 'link', 'quote', 'code', '|', 'preview'],
                inputStyle: 'contenteditable',
                spellChecker: false,
                nativeSpellcheck: true,
            });
        });
    }).catch(error => console.error('EasyMDE could not be loaded', error));
});
