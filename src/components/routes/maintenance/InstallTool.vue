<template>
    <message-overlay :message="overlayMessage" :active="safeMode || (!loading && !supported)">
        <section class="maintenance">
            <div class="maintenance__inside">
                <figure class="maintenance__image"><img src="../../../assets/images/logo.svg" alt="" /></figure>

                <div class="maintenance__about">
                    <h1>{{ 'ui.maintenance.installTool.title' | translate }}</h1>
                    <p>{{ 'ui.maintenance.installTool.description' | translate }}</p>
                </div>

                <fieldset class="maintenance__actions" v-if="!safeMode">
                    <loader class="maintenance__loader" v-if="isLocked === null"/>
                    <loading-button class="widget-button widget-button--primary widget-button--unlock" :loading="loading" :disabled="!supported" v-else-if="isLocked" @click="unlock">{{ $t('ui.maintenance.installTool.unlock') }}</loading-button>
                    <loading-button class="widget-button widget-button--primary widget-button--lock" :loading="loading" :disabled="!supported" v-else @click="lock">{{ $t('ui.maintenance.installTool.lock') }}</loading-button>
                </fieldset>
            </div>
        </section>
    </message-overlay>
</template>

<script>
    import { mapState } from 'vuex';

    import MessageOverlay from '../../fragments/MessageOverlay';
    import Loader from '../../fragments/Loader';
    import LoadingButton from '../../widgets/LoadingButton';

    export default {
        components: { MessageOverlay, Loader, LoadingButton },

        data: () => ({
            supported: false,
            loading: true,
        }),

        computed: {
            ...mapState(['safeMode']),
            ...mapState('contao/install-tool', ['isLocked']),
            overlayMessage: vm => vm.safeMode ? vm.$t('ui.maintenance.safeMode') : vm.$t('ui.maintenance.unsupported'),
        },

        methods: {
            async unlock() {
                this.loading = true;
                await this.$store.dispatch('contao/install-tool/unlock');
                this.loading = false;
            },

            async lock() {
                this.loading = true;
                await this.$store.dispatch('contao/install-tool/lock');
                this.loading = false;
            },
        },

        mounted() {
            if (this.safeMode) {
                return;
            }

            this.$store.dispatch('contao/install-tool/isLocked').then(
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
