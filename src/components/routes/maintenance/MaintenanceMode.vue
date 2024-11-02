<template>
    <message-overlay :message="overlayMessage" :active="safeMode || (!loading && !supported)">
        <section class="maintenance">
            <div class="maintenance__inside">
                <figure class="maintenance__image"><img src="../../../assets/images/logo.svg" alt="" /></figure>
                <div class="maintenance__about">
                    <h1>{{ $t('ui.maintenance.maintenanceMode.title') }}</h1>
                    <p>{{ $t('ui.maintenance.maintenanceMode.description') }}</p>
                </div>
                <fieldset class="maintenance__actions">
                    <loading-spinner class="maintenance__loader" v-if="loading && !supported"/>
                    <loading-button class="widget-button widget-button--primary widget-button--maintenance" :loading="loading" :disabled="!supported" v-else-if="!enabled" @click="enableMaintenanceMode">{{ $t('ui.maintenance.maintenanceMode.enable') }}</loading-button>
                    <loading-button class="widget-button widget-button--alert widget-button--maintenance" :loading="loading" :disabled="!supported" v-else @click="disableMaintenanceMode">{{ $t('ui.maintenance.maintenanceMode.disable') }}</loading-button>
                </fieldset>
            </div>
        </section>
    </message-overlay>
</template>

<script>
    import { mapState } from 'vuex';

    import MessageOverlay from '../../fragments/MessageOverlay';
    import LoadingSpinner from 'contao-package-list/src/components/fragments/LoadingSpinner';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { MessageOverlay, LoadingSpinner, LoadingButton },

        data: () => ({
            loading: true,
            supported: false,
            enabled: false,
        }),

        computed: {
            ...mapState(['safeMode']),
            overlayMessage: vm => vm.safeMode ? vm.$t('ui.maintenance.safeMode') : vm.$t('ui.maintenance.unsupported'),
        },

        methods: {
            async enableMaintenanceMode() {
                this.loading = true;
                this.enabled = await this.$store.dispatch('contao/maintenance-mode/enable');
                this.loading = false;
            },

            async disableMaintenanceMode() {
                this.loading = true;
                this.enabled = await this.$store.dispatch('contao/maintenance-mode/disable');
                this.loading = false;
            },
        },

        async mounted() {
            const response = await this.$store.dispatch('server/contao/get');

            if (response.status === 200) {
                const commands = Object.keys(response.data?.cli?.commands);

                this.supported = commands.includes('contao:maintenance-mode') ||
                    (commands.includes('lexik:maintenance:lock') && commands.includes('lexik:maintenance:unlock'))

                if (this.supported) {
                    this.enabled = await this.$store.dispatch('contao/maintenance-mode/isEnabled')
                }
            }

            this.loading = false
        },
    };
</script>
