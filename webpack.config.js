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
Encore.reset();

module.exports = [sbadmin];
