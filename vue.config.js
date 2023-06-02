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
};
