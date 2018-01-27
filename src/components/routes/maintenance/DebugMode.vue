<template>
    <section class="maintenance" v-if="$store.state.apiVersion >= 1">
        <div class="maintenance__inside">
            <figure class="maintenance__image"><img src="../../../assets/images/logo.svg" /></figure>
            <div class="maintenance__about">
                <h1>{{ 'ui.maintenance.debugMode.title' | translate }}</h1>
                <p v-html="$t('ui.maintenance.debugMode.description')"></p>
            </div>
            <fieldset class="maintenance__actions" v-if="loading || hasAccessKey === null">
                <loader class="maintenance__loader"/>
            </fieldset>
            <fieldset class="maintenance__actions" v-else>
                <button class="widget-button widget-button--primary widget-button--show" v-if="!hasAccessKey" @click="setAccessKey">{{ 'ui.maintenance.debugMode.activate' | translate }}</button>
                <button class="widget-button widget-button--alert widget-button--hide" v-if="hasAccessKey" @click="removeAccessKey">{{ 'ui.maintenance.debugMode.deactivate' | translate }}</button>
                <button class="widget-button widget-button--edit" v-if="hasAccessKey" @click="setAccessKey">{{ 'ui.maintenance.debugMode.credentials' | translate }}</button>
            </fieldset>
        </div>
    </section>
</template>

<script>
    import Loader from '../../fragments/Loader';

    export default {
        components: { Loader },

        data: () => ({
            loading: true,
        }),

        computed: {
            hasAccessKey() {
                return this.$store.state.contao['access-key'].isEnabled;
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

                this.$store.dispatch('contao/access-key/set', { user, password }).then(() => {
                    this.loading = false;
                });
            },

            removeAccessKey() {
                this.loading = true;

                this.$store.dispatch('contao/access-key/delete').then(() => {
                    this.loading = false;
                });
            },
        },

        mounted() {
            if (this.$store.apiVersion < 1) {
                return;
            }

            this.$store.dispatch('contao/access-key/get').then(() => {
                this.loading = false;
            });
        },
    };
</script>
