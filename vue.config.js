module.exports = {
    productionSourceMap: false,
    baseUrl: '',

    pluginOptions: {
        proxy: {
            context: '/api',
            options: {
                target: 'http://localhost:8000/',
                pathRewrite: { '^/api/': '/api.php/api/' },
            },
        },
    },
};
