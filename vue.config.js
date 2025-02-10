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
        config.plugin('define').tap((definitions) => {
            Object.assign(definitions[0], {
                __VUE_OPTIONS_API__: 'true',
                __VUE_PROD_DEVTOOLS__: 'false',
                __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false'
            })
            return definitions
        })

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


        config.resolve.alias.set('vue', '@vue/compat');

        config.module
            .rule('vue')
            .use('vue-loader')
            .tap((options) => {
                return {
                    ...options,
                    compilerOptions: {
                        compatConfig: {
                            MODE: 3
                        }
                    }
                }
            })
        ;
    }
};
