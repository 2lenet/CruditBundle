import './bootstrap.js';
// The stylesheets of the bundles are imported explicitly, never by the autoimport of a controller: disabling a
// controller (the only way to override one with Encore) would remove its CSS.
import '@2lenet/crudit-ui-bundle/dist/ui.css';
import '@2lenet/crudit-bundle/dist/crudit.css';
import './styles/app.css';
