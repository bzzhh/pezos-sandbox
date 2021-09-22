const Encore = require('@symfony/webpack-encore');
const NodePolyfillPlugin = require('node-polyfill-webpack-plugin');
const PurgeCssPlugin = require('purgecss-webpack-plugin');
const glob = require('glob-all');
const path = require('path');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/js/app.js')

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/js/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    .enablePostCssLoader()
    .configureDevServerOptions((options) => {
        // hotfix for webpack-dev-server 4.0.0rc0
        // @see: https://github.com/symfony/webpack-encore/issues/951#issuecomment-840719271

        delete options.client;
    })
    // enables Sass/SCSS support
    // .enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()
    .addPlugin(new NodePolyfillPlugin());

if (Encore.isProduction()) {
    Encore.addPlugin(
        new PurgeCssPlugin({
            paths: glob.sync([
                path.join(__dirname, 'templates/**/*.html.twig'),
            ]),
            content: ['**/*.twig'],
            defaultExtractor: (content) => {
                return content.match(/[\w-/:]+(?<!:)/g) || [];
            },
            safelist: {
                // https://github.com/tailwindlabs/tailwindcss-forms/blob/master/src/index.js
                standard: [
                    /type/,
                    /textarea/,
                    /select/,
                    /hidden/,
                    /is-active/,
                    /text-red-700/,
                    /required/,
                    /block/,
                    /text-gray-800/,
                    /inactive/,
                ],
                deep: [/^choices/, /^custom-radio-buttons/],
            },
        })
    );
}
module.exports = Encore.getWebpackConfig();
