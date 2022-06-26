const WebpackPwaManifest = require('webpack-pwa-manifest')
const path = require('path');

module.exports = {
    productionSourceMap: false,
    baseUrl: '',

    devServer: {
        proxy: {
            '/api': {
                target: 'http://127.0.0.1:8000/',
                pathRewrite: { '^/api/': '/api.php/api/' },
            },
        },
    },

    pluginOptions: {
        webpackBundleAnalyzer: {
            analyzerMode: 'disabled',
            openAnalyzer: false,
        },
    },

    configureWebpack: () => {
        return {
            output: {
                crossOriginLoading: 'anonymous',
            },

            plugins: [
                new WebpackPwaManifest({
                    name: 'Contao Manager',
                    background_color: '#ffffff',
                    theme_color: '#ffffff',
                    orientation: 'omit',
                    publicPath: '.',
                    icons: [
                        {
                            src: path.resolve('node_modules/contao-package-list/src/assets/icons/android-chrome-192x192.png'),
                            size: '192x192',
                            destination: 'icons',
                        },
                        {
                            src: path.resolve('node_modules/contao-package-list/src/assets/icons/android-chrome-512x512.png'),
                            size: '512x512',
                            destination: 'icons',
                        },
                    ],
                }),
            ],

            module: {
                rules: [
                    {
                        test: /icons[\\/][^\\/]+\.(png|jpe?g|gif|webp|svg|ico)(\?.*)?$/,
                        use: [
                            {
                                loader: "file-loader",
                                options: {
                                    name: "icons/[name].[hash:8].[ext]",
                                },
                            },
                            {
                                loader: "image-webpack-loader",
                            },
                        ]
                    },
                ],
            },
        };
    },

    chainWebpack: config => {
        config.module
            .rule('images')
            .test(/images[\\/][^\\/]+\.(png|jpe?g|gif|webp)(\?.*)?$/)
            .use('image-webpack-loader')
            .loader('image-webpack-loader')
        ;

        config.module
            .rule('svg')
            .use('image-webpack-loader')
            .loader('image-webpack-loader')
        ;
    }
};
