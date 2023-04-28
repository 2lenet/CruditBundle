var Encore = require('@symfony/webpack-encore');
const WebpackRTLPlugin = require('webpack-rtl-plugin');
const path = require( 'path' );
const webpack = require( 'webpack' );
const { bundler, styles } = require( '@ckeditor/ckeditor5-dev-utils' );
const { CKEditorTranslationsPlugin } = require( '@ckeditor/ckeditor5-dev-translations' );
const TerserWebpackPlugin = require( 'terser-webpack-plugin' );

function basic(path, Encore){
    return Encore
        .setOutputPath('./src/Resources/public/' + path)
        .setPublicPath('/')
        .setManifestKeyPrefix('bundles/crudit')
        .cleanupOutputBeforeBuild()
        .enableEslintLoader()
        .enableSassLoader()
        .enableSourceMaps(false)
        .enableVersioning(false)
        .disableSingleRuntimeChunk()
        .autoProvidejQuery()
}

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


