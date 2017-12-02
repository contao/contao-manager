import Vue from 'vue';

let cache = {};

const fetch = (uri) => {
    if (cache[uri]) {
        return new Promise((resolve) => {
            resolve(cache[uri]);
        });
    }

    return Vue.http.get(uri).then((response) => {
        cache[uri] = response;

        return response;
    });
};

const purge = () => {
    cache = {};
};

export default {
    purgeCache: purge,

    selfUpdate() {
        return fetch('api/server/self-update').then(
            response => response.body,
            (response) => {
                if (response.status === 501) {
                    return {
                        current_version: null,
                        latest_version: null,
                    };
                }

                throw response;
            },
        );
    },

    getConfig() {
        return fetch('api/server/config').then(
            response => response.body,
        );
    },

    setConfig(server, phpCli) {
        purge();

        const config = { server };

        if (server === 'custom') {
            config.php_cli = phpCli;
        }

        return Vue.http.put('api/server/config', config);
    },

    phpWeb() {
        return fetch('api/server/php-web').then(
            response => response.body,
        );
    },

    phpCli() {
        return fetch('api/server/php-cli').then(
            response => response.body,
        );
    },

    composer() {
        return fetch('api/server/composer').then(
            response => response.body,
        );
    },

    contao() {
        return fetch('api/server/contao').then(
            response => response.body,
        );
    },
};
