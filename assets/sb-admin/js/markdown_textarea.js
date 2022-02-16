'use strict';
import * as EasyMDE from 'easymde';

window.addEventListener('load', function () {

    document.querySelectorAll("textarea.markdown-editor").forEach(textarea => {
        new EasyMDE({
            element: textarea,
            toolbar: ["bold", "italic", "|", "heading-smaller", "heading-bigger", "|", "unordered-list", "ordered-list", "|", "link", "quote", "code", "|", "preview"],
            inputStyle: 'contenteditable',
            spellChecker: false,
            nativeSpellcheck: true
        });
    });

});