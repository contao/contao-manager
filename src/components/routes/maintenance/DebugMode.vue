<template>
    <section class="maintenance">
        <div class="maintenance__hint" v-if="apiVersion < 1">
            <p>{{ 'ui.maintenance.debugMode.version' | translate }}</p>
        </div>
        <div class="maintenance__inside">
            <div class="maintenance__about">
                <h1>{{ 'ui.maintenance.debugMode.title' | translate }}</h1>
                <p v-html="$t('ui.maintenance.debugMode.description')"></p>
            </div>
            <fieldset class="maintenance__actions" v-if="loading || hasAccessKey === null">
                <loader class="maintenance__loader"/>
            </fieldset>
            <fieldset class="maintenance__actions" v-else>
                <button class="widget-button widget-button--primary widget-button--show" v-if="!hasAccessKey" :disabled="apiVersion < 1" @click="setAccessKey">{{ 'ui.maintenance.debugMode.activate' | translate }}</button>
                <button class="widget-button widget-button--alert widget-button--hide" v-if="hasAccessKey" @click="removeAccessKey">{{ 'ui.maintenance.debugMode.deactivate' | translate }}</button>
                <button class="widget-button widget-button--edit" v-if="hasAccessKey" @click="setAccessKey">{{ 'ui.maintenance.debugMode.credentials' | translate }}</button>
            </fieldset>
        </div>
    </section>
</template>

<script>
    import api from '../../../api';

    import Loader from '../../fragments/Loader';

    export default {
        components: { Loader },

        data: () => ({
            loading: true,
            apiVersion: 0,
        }),

        computed: {
            hasAccessKey() {
                return this.$store.state.debugMode;
            },
        },

        methods: {
            setAccessKey() {
                const user = prompt(this.$t('ui.maintenance.debugMode.user'));

                if (!user) {
                    return;
                }

                const password = prompt(this.$t('ui.maintenance.debugMode.password'));

                if (!password) {
                    return;
                }

                this.loading = true;

                api.contao.accessKey.set(user, password).then((accessKey) => {
                    this.$store.commit('setDebugMode', accessKey !== '');
                    this.loading = false;
                });
            },

            removeAccessKey() {
                this.loading = true;

                api.contao.accessKey.remove().then((accessKey) => {
                    this.$store.commit('setDebugMode', accessKey !== '');
                    this.loading = false;
                });
            },
        },

        mounted() {
            if (this.$store.state.debugMode === null) {
                this.$store.dispatch('refreshDebugMode');
            }

            api.system.contao().then((result) => {
                this.apiVersion = result.api;

                this.loading = false;
            });
        },
    };
</script>
