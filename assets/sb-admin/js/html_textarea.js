'use strict';
/*import ClassicEd from '@ckeditor/ckeditor5-build-classic';*/
import ClassicEditor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor.js';
import Alignment from '@ckeditor/ckeditor5-alignment/src/alignment.js';
import Autoformat from '@ckeditor/ckeditor5-autoformat/src/autoformat.js';
import BlockQuote from '@ckeditor/ckeditor5-block-quote/src/blockquote.js';
import Bold from '@ckeditor/ckeditor5-basic-styles/src/bold.js';
import CloudServices from '@ckeditor/ckeditor5-cloud-services/src/cloudservices.js';
import Essentials from '@ckeditor/ckeditor5-essentials/src/essentials.js';
import FontSize from '@ckeditor/ckeditor5-font/src/fontsize.js';
import Heading from '@ckeditor/ckeditor5-heading/src/heading.js';
import HtmlEmbed from '@ckeditor/ckeditor5-html-embed/src/htmlembed.js';
import Image from '@ckeditor/ckeditor5-image/src/image.js';
import ImageCaption from '@ckeditor/ckeditor5-image/src/imagecaption.js';
import ImageStyle from '@ckeditor/ckeditor5-image/src/imagestyle.js';
import ImageToolbar from '@ckeditor/ckeditor5-image/src/imagetoolbar.js';
import ImageUpload from '@ckeditor/ckeditor5-image/src/imageupload.js';
import Indent from '@ckeditor/ckeditor5-indent/src/indent.js';
import Italic from '@ckeditor/ckeditor5-basic-styles/src/italic.js';
import Link from '@ckeditor/ckeditor5-link/src/link.js';
import List from '@ckeditor/ckeditor5-list/src/list.js';
import MediaEmbed from '@ckeditor/ckeditor5-media-embed/src/mediaembed.js';
import Paragraph from '@ckeditor/ckeditor5-paragraph/src/paragraph.js';
import PasteFromOffice from '@ckeditor/ckeditor5-paste-from-office/src/pastefromoffice.js';
import Table from '@ckeditor/ckeditor5-table/src/table.js';
import TableToolbar from '@ckeditor/ckeditor5-table/src/tabletoolbar.js';
import TextTransformation from '@ckeditor/ckeditor5-typing/src/texttransformation.js';

class Editor extends ClassicEditor {}

// Plugins to include in the build.
Editor.builtinPlugins = [
    Alignment,
    Autoformat,
    BlockQuote,
    Bold,
    CloudServices,
    Essentials,
    FontSize,
    Heading,
    HtmlEmbed,
    Image,
    ImageCaption,
    ImageStyle,
    ImageToolbar,
    ImageUpload,
    Indent,
    Italic,
    Link,
    List,
    MediaEmbed,
    Paragraph,
    PasteFromOffice,
    Table,
    TableToolbar,
    TextTransformation
];

// Editor configuration.
Editor.defaultConfig = {
    toolbar: {
        items: [
            'heading',
            '|',
            'fontSize',
            'bold',
            'italic',
            'link',
            'bulletedList',
            'numberedList',
            '|',
            'outdent',
            'indent',
            'alignment',
            '|',
            'htmlEmbed',
            'imageUpload',
            'blockQuote',
            'insertTable',
            'mediaEmbed',
            'undo',
            'redo'
        ]
    },
    language: 'fr',
    image: {
        toolbar: [
            'imageTextAlternative',
            'toggleImageCaption',
            'imageStyle:inline',
            'imageStyle:block',
            'imageStyle:side'
        ]
    },
    table: {
        contentToolbar: [
            'tableColumn',
            'tableRow',
            'mergeTableCells'
        ]
    }
};

export default Editor;
// this use the custom ck-editor build
// it works, only the css is not loaded correctly

window.addEventListener('load', function () {
    document.querySelectorAll(".html-ckeditor").forEach(htmlCkeditor => {
        let ckeditor = htmlCkeditor.querySelector('.ckeditor')
        Editor
            .create(htmlCkeditor.querySelector('#'+ckeditor.id))
            .then( editor => {
                window.editor = editor
                    document.querySelector('#modal_'+ckeditor.id).addEventListener('click', function() {
                        document.querySelector('#hidden_'+ckeditor.id).value = editor.ui.view.editable.element.innerHTML;
                    })
                    document.querySelector('form').addEventListener('submit', function() {
                        document.querySelector('#hidden_'+ckeditor.id).value = editor.ui.view.editable.element.innerHTML;
                    })
                }
            )
            .catch( error => {
                console.error( error );
            } );
    });
});

// this is the classic build give by ck-editor.
// it works fine but doesnt implement htmlEmbed for writing html in wysiwyg
// comment all the rest of this file if you want to try this build. (don't forget to import `ClassicEd`)

/*
window.addEventListener('load', function () {
    document.querySelectorAll(".html-ckeditor").forEach(htmlCkeditor => {
        let ckeditor = htmlCkeditor.querySelector('.ckeditor')
        ClassicEd
            .create(htmlCkeditor.querySelector('#'+ckeditor.id))
            .then( editor => {
                    window.editor = editor
                    document.querySelector('#modal_'+ckeditor.id).addEventListener('click', function() {
                        document.querySelector('#hidden_'+ckeditor.id).value = editor.ui.view.editable.element.innerHTML;
                    })
                    document.querySelector('form').addEventListener('submit', function() {
                        document.querySelector('#hidden_'+ckeditor.id).value = editor.ui.view.editable.element.innerHTML;
                    })
                }
            )
            .catch( error => {
                console.error( error );
            } );
    });
});*/
