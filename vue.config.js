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
};
