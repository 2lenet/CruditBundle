'use strict';

// Lazy chunks (EasyMDE) are served next to app.js, wherever assets:install put it.
// Resolved at runtime rather than through `output.publicPath = 'auto'`, which would also
// leak an `auto` prefix into manifest.json and entrypoints.json.
// No currentScript (app.js loaded as a module or injected): keep the default path rather
// than crash the whole bundle.
if (document.currentScript) {
    // eslint-disable-next-line no-undef
    __webpack_public_path__ = document.currentScript.src.replace(/[^/]*$/, '');
}
