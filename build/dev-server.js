require('./check-versions')();

const config = require('./config');
if (!process.env.NODE_ENV) {
    process.env.NODE_ENV = config.dev.env.NODE_ENV
}

const opn = require('opn');
const path = require('path');
const express = require('express');
const webpack = require('webpack');
const merge = require('webpack-merge');
const proxyMiddleware = require('http-proxy-middleware');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const FriendlyErrorsPlugin = require('friendly-errors-webpack-plugin');
const utils = require('./utils');

let webpackConfig = require('./webpack.config');

// add hot-reload related code to entry chunks
Object.keys(webpackConfig.entry).forEach(function (name) {
    webpackConfig.entry[name] = ['./build/dev-client'].concat(webpackConfig.entry[name])
});

webpackConfig = merge(webpackConfig, {
    module: {
        loaders: utils.styleLoaders({ sourceMap: config.dev.cssSourceMap })
    },
    // eval-source-map is faster for development
    devtool: '#eval-source-map',
    plugins: [
        new webpack.DefinePlugin({
            'process.env': config.dev.env
        }),
        new webpack.HotModuleReplacementPlugin(),
        new webpack.optimize.OccurrenceOrderPlugin(),
        new webpack.NoEmitOnErrorsPlugin(),
        new HtmlWebpackPlugin({
            template: 'src/index.html',
            filename: 'index.html',
            inject: true
        }),
        new FriendlyErrorsPlugin()
    ]
});

const port = process.env.PORT || config.dev.port;
const proxyTable = config.dev.proxyTable;

const app = express();
const compiler = webpack(webpackConfig);

const devMiddleware = require('webpack-dev-middleware')(compiler, {
    publicPath: webpackConfig.output.publicPath,
    quiet: false
});

const hotMiddleware = require('webpack-hot-middleware')(compiler, {
    log: () => {}
});

// force page reload when html-webpack-plugin template changes
compiler.plugin('compilation', function (compilation) {
    compilation.plugin('html-webpack-plugin-after-emit', function (data, cb) {
        hotMiddleware.publish({ action: 'reload' });
        cb()
    })
});

// proxy api requests
Object.keys(proxyTable).forEach(function (context) {
    let options = proxyTable[context];
    if (typeof options === 'string') {
        options = { target: options }
    }
    app.use(proxyMiddleware(context, options))
});

// handle fallback for HTML5 history API
app.use(require('connect-history-api-fallback')());

// serve webpack bundle output
app.use(devMiddleware);

// enable hot-reload and state-preserving
// compilation error display
app.use(hotMiddleware);

// serve pure static assets
const staticPath = path.posix.join(config.dev.assetsPublicPath, config.dev.assetsSubDirectory);
app.use(staticPath, express.static('./src/assets'));

const uri = 'http://localhost:' + port;

devMiddleware.waitUntilValid(function () {
    console.log('> Listening at ' + uri + '\n')
});

module.exports = app.listen(port, function (err) {
    if (err) {
        console.log(err);
        return;
    }

    opn(uri);
});
