<template>
    <message-overlay :message="overlayMessage" :active="safeMode || (!loading && !supportsJwtCookie && !supportsAccessKey)">
        <section class="maintenance">
            <div class="maintenance__inside">
                <figure class="maintenance__image"><img src="../../../assets/images/logo.svg" alt="" /></figure>
                <div class="maintenance__about">
                    <h1>{{ $t('ui.maintenance.debugMode.title') }}</h1>
                    <p v-html="$t('ui.maintenance.debugMode.description')"></p>
                </div>
                <fieldset class="maintenance__actions" v-if="loading && !supportsJwtCookie && !supportsAccessKey">
                    <loader class="maintenance__loader"/>
                </fieldset>
                <fieldset class="maintenance__actions" v-else-if="supportsJwtCookie">
                    <loading-button class="widget-button widget-button--primary widget-button--show" :loading="loading" v-if="!hasJwtDebug" @click="enableJwtDebugMode">{{ $t('ui.maintenance.debugMode.activate') }}</loading-button>
                    <loading-button class="widget-button widget-button--alert widget-button--hide" :loading="loading" v-if="hasJwtDebug" @click="removeJwtCookie">{{ $t('ui.maintenance.debugMode.deactivate') }}</loading-button>
                </fieldset>
                <fieldset class="maintenance__actions" v-else-if="supportsAccessKey">
                    <loading-button class="widget-button widget-button--primary widget-button--show" :loading="loading" v-if="!hasAccessKey" @click="setAccessKey">{{ $t('ui.maintenance.debugMode.activate') }}</loading-button>
                    <loading-button class="widget-button widget-button--alert widget-button--hide" :loading="loading" v-if="hasAccessKey" @click="removeAccessKey">{{ $t('ui.maintenance.debugMode.deactivate') }}</loading-button>
                    <loading-button class="widget-button widget-button--edit" v-if="hasAccessKey" :loading="loading" @click="setAccessKey">{{ 'ui.maintenance.debugMode.credentials' | translate }}</loading-button>
                </fieldset>
            </div>
        </section>
    </message-overlay>
</template>

<script>
    import { mapState } from 'vuex';

    import MessageOverlay from '../../fragments/MessageOverlay';
    import Loader from 'contao-package-list/src/components/fragments/Loader';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { MessageOverlay, Loader, LoadingButton },

        data: () => ({
            supportsJwtCookie: false,
            supportsAccessKey: false,
            loading: true,
        }),

        computed: {
            ...mapState(['safeMode']),
            ...mapState('contao/access-key', { hasAccessKey: 'isEnabled' }),
            ...mapState('contao/jwt-cookie', { hasJwtDebug: 'isDebugEnabled' }),
            overlayMessage: vm => vm.safeMode ? vm.$t('ui.maintenance.safeMode') : vm.$t('ui.maintenance.unsupported'),
        },

        methods: {
            async enableJwtDebugMode() {
                this.loading = true;
                await this.$store.dispatch('contao/jwt-cookie/enableDebug');
                this.loading = false;
            },

            async removeJwtCookie() {
                this.loading = true;
                await this.$store.dispatch('contao/jwt-cookie/delete');
                this.loading = false;
            },

            async setAccessKey() {
                const user = prompt(this.$t('ui.maintenance.debugMode.user'));

                if (!user) {
                    return;
                }

                const password = prompt(this.$t('ui.maintenance.debugMode.password'));

                if (!password) {
                    return;
                }

                this.loading = true;
                await this.$store.dispatch('contao/access-key/set', { user, password });
                this.loading = false;
            },

            async removeAccessKey() {
                this.loading = true;
                await this.$store.dispatch('contao/access-key/delete');
                this.loading = false;
            },
        },

        mounted() {
            this.$store.dispatch('contao/jwt-cookie/get').then(
                () => {
                    this.supportsJwtCookie = true;
                    this.supportsAccessKey = false;
                    this.loading = false;
                },
                () => this.$store.dispatch('contao/access-key/get').then(
                    () => {
                        this.supportsJwtCookie = false;
                        this.supportsAccessKey = true;
                        this.loading = false;
                    },
                    () => {
                        this.supportsJwtCookie = false;
                        this.supportsAccessKey = false;
                        this.loading = false;
                    },
                )
            );
        },
    };
</script>
