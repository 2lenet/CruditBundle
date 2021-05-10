var Encore = require('@symfony/webpack-encore');
const WebpackRTLPlugin = require('webpack-rtl-plugin');

function basic(path, Encore){
    return Encore
        .setOutputPath('./src/Resources/public/' + path)
        .setPublicPath('./')
        .setManifestKeyPrefix('bundles/crudit')
        .cleanupOutputBeforeBuild()
        .enableEslintLoader()
        .enableSassLoader()
        .enableSourceMaps(false)
        .enableVersioning(false)
        .disableSingleRuntimeChunk()
        .autoProvidejQuery()
        .addPlugin(new WebpackRTLPlugin({
            test: '^((?!(app-custom-rtl.css)).)*$',
            diffOnly: true,
        }))
}

/**
 * Config for adminlte layout
 */
basic('adminlte', Encore)
    .addEntry("app", "./assets/admin-lte/js/app.js")
    .copyFiles({
        from: './node_modules/admin-lte/dist/img/',
        to: 'images/[name].[ext]',
        pattern: /\.(jpg|png)$/
    })
;
const adminlte = Encore.getWebpackConfig();
Encore.reset();

/**
 * Config for sb-admin layout
 */
basic('sbadmin', Encore)
    .addEntry("app", "./assets/sb-admin/js/app.js")
    .copyFiles({
        from: './node_modules/startbootstrap-sb-admin-2/img/',
        to: 'images/[name].[ext]',
        pattern: /\.(jpg|png|svg)$/
    })
;
const sbadmin = Encore.getWebpackConfig();
Encore.reset();

module.exports = [sbadmin]
