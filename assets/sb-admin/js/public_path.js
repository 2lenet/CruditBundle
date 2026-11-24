'use strict';

// Lazy chunks (EasyMDE) are served next to app.js, wherever assets:install put it.
// Resolved at runtime rather than through `output.publicPath = 'auto'`, which would also
// leak an `auto` prefix into manifest.json and entrypoints.json.
// eslint-disable-next-line no-undef
__webpack_public_path__ = document.currentScript.src.replace(/[^/]*$/, '');
