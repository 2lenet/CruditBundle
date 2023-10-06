'use strict';

import {ClassicEditor} from '@ckeditor/ckeditor5-editor-classic';
import {Autoformat} from '@ckeditor/ckeditor5-autoformat';
import {BlockQuote} from '@ckeditor/ckeditor5-block-quote';
import {Bold, Italic} from '@ckeditor/ckeditor5-basic-styles';
import {CloudServices} from '@ckeditor/ckeditor5-cloud-services';
import {Essentials} from '@ckeditor/ckeditor5-essentials';
import {Heading} from '@ckeditor/ckeditor5-heading';
import {HtmlEmbed} from '@ckeditor/ckeditor5-html-embed';
import {Image, ImageCaption, ImageStyle, ImageToolbar, ImageUpload} from '@ckeditor/ckeditor5-image';
import {Indent} from '@ckeditor/ckeditor5-indent';
import {Link} from '@ckeditor/ckeditor5-link';
import {List} from '@ckeditor/ckeditor5-list';
import {MediaEmbed} from '@ckeditor/ckeditor5-media-embed';
import {Paragraph} from '@ckeditor/ckeditor5-paragraph';
import {PasteFromOffice} from '@ckeditor/ckeditor5-paste-from-office';
import {Table, TableToolbar} from '@ckeditor/ckeditor5-table';
import {TextTransformation} from '@ckeditor/ckeditor5-typing';

class Editor extends ClassicEditor {}

Editor.builtinPlugins = [
    Autoformat,
    BlockQuote,
    Bold,
    CloudServices,
    Essentials,
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
    TextTransformation,
];

Editor.defaultConfig = {
    toolbar: {
        item: [
            'heading',
            '|',
            'bold',
            'italic',
            'link',
            'bulletedList',
            'numberedList',
            '|',
            'outdent',
            'indent',
            '|',
            'imageUpload',
            'blockQuote',
            'insertTable',
            'mediaEmbed',
            'undo',
            'redo',
            '|',
            'htmlEmbed',
        ],
    },
    language: 'fr',
    image: {
        toolbar: [
            'imageTextAlternative',
            'toggleImageCaption',
            'imageStyle:inline',
            'imageStyle:block',
            'imageStyle:side'
        ],
    },
    table: {
        contentToolbar: [
            'tableColumn',
            'tableRow',
            'mergeTableCells',
        ],
    },
};

export default Editor;

window.addEventListener('load', function () {
    Editor
        .create(document.querySelector('#html_editor'))
        .then(editor => {
            window.editor = editor;
        })
        .catch(error => {
            console.error(error);
        });
});
