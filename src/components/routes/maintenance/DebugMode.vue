<template>
    <message-overlay :message="overlayMessage" :active="safeMode || (!loading && !supported)">
        <section class="maintenance">
            <div class="maintenance__inside">
                <figure class="maintenance__image"><img src="../../../assets/images/logo.svg" /></figure>
                <div class="maintenance__about">
                    <h1>{{ 'ui.maintenance.debugMode.title' | translate }}</h1>
                    <p v-html="$t('ui.maintenance.debugMode.description')"></p>
                </div>
                <fieldset class="maintenance__actions" v-if="loading">
                    <loader class="maintenance__loader"/>
                </fieldset>
                <fieldset class="maintenance__actions" v-else>
                    <button class="widget-button widget-button--primary widget-button--show" :disabled="!supported" v-if="!hasAccessKey" @click="setAccessKey">{{ 'ui.maintenance.debugMode.activate' | translate }}</button>
                    <button class="widget-button widget-button--alert widget-button--hide" v-if="hasAccessKey" @click="removeAccessKey">{{ 'ui.maintenance.debugMode.deactivate' | translate }}</button>
                    <button class="widget-button widget-button--edit" v-if="hasAccessKey" @click="setAccessKey">{{ 'ui.maintenance.debugMode.credentials' | translate }}</button>
                </fieldset>
            </div>
        </section>
    </message-overlay>
</template>

<script>
    import { mapState } from 'vuex';

    import MessageOverlay from '../../fragments/MessageOverlay';
    import Loader from '../../fragments/Loader';

    export default {
        components: { MessageOverlay, Loader },

        data: () => ({
            supported: false,
            loading: true,
        }),

        computed: {
            ...mapState(['safeMode']),
            ...mapState('contao/access-key', { hasAccessKey: 'isEnabled' }),
            overlayMessage: vm => vm.safeMode ? vm.$t('ui.maintenance.safeMode') : vm.$t('ui.maintenance.unsupported'),
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
            this.$store.dispatch('contao/access-key/get').then(
                () => {
                    this.supported = true;
                    this.loading = false;
                },
                () => {
                    this.supported = false;
                    this.loading = false;
                },
            );
        },
    };
</script>
