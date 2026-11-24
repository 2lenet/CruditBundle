var Encore = require('@symfony/webpack-encore');
const WebpackRTLPlugin = require('webpack-rtl-plugin');

function basic(path, Encore) {
    return Encore
        .setOutputPath('./src/Resources/public/' + path)
        .setPublicPath('/')
        .setManifestKeyPrefix('bundles/crudit')
        .cleanupOutputBeforeBuild()
        .enableEslintPlugin()
        .enableSassLoader()
        .enableSourceMaps(false)
        .enableVersioning(false)
        .disableSingleRuntimeChunk()
        .autoProvidejQuery()
        .addPlugin(new WebpackRTLPlugin({
            test: '^((?!(app-custom-rtl.css)).)*$',
            diffOnly: true,
        }));
}

/**
 * Config for sb-admin layout
 */
basic('sbadmin', Encore)
    .addEntry('app', './assets/sb-admin/js/app.js')
;
const sbadmin = Encore.getWebpackConfig();
// A single, stably named lazy chunk (easymde.js): a numeric vendor id could change between
// builds and 404 for a browser that still caches the previous app.js.
sbadmin.optimization.splitChunks = false;
// The host application's Encore build also registers its chunks in `self.webpackChunk`:
// sharing that global would let one runtime swallow the other's chunks.
sbadmin.output.uniqueName = 'lleCrudit';
Encore.reset();

module.exports = [sbadmin];
