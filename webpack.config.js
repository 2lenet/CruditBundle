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
 * Config for default layout
 */
basic('adminlte', Encore)
    .addEntry("app", "./assets/admin-lte/js/app.js")
    .copyFiles({
        from: './node_modules/admin-lte/dist/img/',
        to: 'images/[name].[ext]',
        pattern: /\.(jpg|png)$/
    })
;
const def = Encore.getWebpackConfig();
Encore.reset();

/**
 * Config for tweden layout
 */
basic('tweden', Encore)
    .addStyleEntry("app", "./assets/tweden/css/app.scss")
;
const tweden = Encore.getWebpackConfig();


module.exports = [def, tweden]
