import Vue from 'vue';

export default {
    selfUpdate() {
        return Vue.http.get('api/server/self-update').then(
            response => response.body,
            (response) => {
                if (response.status === 501) {
                    return { has_update: false };
                }

                throw response;
            },
        );
    },

    getConfig() {
        return Vue.http.get('api/server/config').then(
            response => response.body,
        );
    },

    setConfig(server, phpCli) {
        const config = { server };

        if (server === 'custom') {
            config.php_cli = phpCli;
        }

        return Vue.http.put('api/server/config', config);
    },

    phpWeb() {
        return Vue.http.get('api/server/php-web').then(
            response => response.body,
        );
    },

    phpCli() {
        return Vue.http.get('api/server/php-cli').then(
            response => response.body,
        );
    },

    composer() {
        return Vue.http.get('api/server/composer').then(
            response => response.body,
        );
    },

    contao() {
        return Vue.http.get('api/server/contao').then(
            response => response.body,
        );
    },
};
