import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

window.addEventListener('load', () => {
    document.querySelectorAll('.ck-editor').forEach(editor => {
        ClassicEditor.create(editor, {
            toolbar: {
                items: [
                    'undo', 'redo',
                    '|', 'heading',
                    '|', 'bold', 'italic',
                    '|', 'link', 'blockQuote',
                    '|', 'bulletedList', 'numberedList', 'outdent', 'indent',
                ],
            },
        });
    });
});
