const FaviconsWebpackPlugin = require('favicons-webpack-plugin');

module.exports = {
    productionSourceMap: false,
    publicPath: '',

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
                new FaviconsWebpackPlugin({
                    logo: './node_modules/contao-package-list/src/assets/images/logo.svg',
                    favicons: {
                        appName: 'Contao Manager',
                        appDescription: 'The official tool to manage a Contao Open Source CMS installation.',
                        background: '#ffffff',
                        theme_color: '#ffffff',
                        lang: null,
                        start_url: '/',
                    }
                }),
            ],
        };
    },

    chainWebpack: config => {
        config
            .module
            .rule('images')
            .test(/images[\\/][^\\/]+\.(png|jpe?g|gif|webp)(\?.*)?$/)
            .use('image-webpack-loader')
            .loader('image-webpack-loader')
        ;

        config
            .module
            .rule('svg')
            .use('image-webpack-loader')
            .loader('image-webpack-loader')
        ;
    }
};
