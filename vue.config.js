module.exports = {
    productionSourceMap: false,
    baseUrl: '',

    devServer: {
        proxy: {
            '/api': {
                target: 'http://localhost:8000/',
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

            module: {
                rules: [
                    {
                        test: /(site\.webmanifest|browserconfig\.xml)$/,
                        use: [
                            {
                                loader: "file-loader",
                                options: {
                                    name: "icons/[name].[hash:8].[ext]",
                                },
                            },
                            {
                                loader: "app-manifest-loader",
                            },
                        ]
                    },
                    {
                        test: /icons\/[^/]+.(png|jpe?g|gif|webp|svg|ico)(\?.*)?$/,
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
            .test(/images\/[^/]+.(png|jpe?g|gif|webp)(\?.*)?$/)
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
