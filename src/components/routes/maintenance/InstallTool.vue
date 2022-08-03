<template>
    <message-overlay :message="overlayMessage" :active="safeMode || isSupported === false">
        <section class="maintenance">
            <div class="maintenance__inside">
                <figure class="maintenance__image"><img src="../../../assets/images/logo.svg" alt="" /></figure>

                <div class="maintenance__about">
                    <h1>{{ $t('ui.maintenance.installTool.title') }}</h1>
                    <p>{{ $t('ui.maintenance.installTool.description') }}</p>
                </div>

                <fieldset class="maintenance__actions" v-if="!safeMode && isSupported !== false">
                    <loader class="maintenance__loader" v-if="isLocked === null"/>
                    <loading-button class="widget-button widget-button--primary widget-button--unlock" :loading="loading" :disabled="!isSupported" v-else-if="isLocked" @click="unlock">{{ $t('ui.maintenance.installTool.unlock') }}</loading-button>
                    <loading-button class="widget-button widget-button--primary widget-button--lock" :loading="loading" :disabled="!isSupported" v-else @click="lock">{{ $t('ui.maintenance.installTool.lock') }}</loading-button>
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
            loading: false,
        }),

        computed: {
            ...mapState(['safeMode']),
            ...mapState('contao/install-tool', ['isLocked', 'isSupported']),
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
            this.$store.dispatch('contao/install-tool/fetch');
        },
    };
</script>
