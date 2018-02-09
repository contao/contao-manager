<template>
    <boot-check v-else :progress="bootState" :title="$t('ui.server.composer.title')" :description="bootDescription">
        <button v-if="bootState === 'info'" @click="install" class="widget-button widget-button--primary widget-button--run">{{ 'ui.server.composer.button' | translate }}</button>
    </boot-check>
</template>

<script>
    import BootCheck from '../fragments/BootCheck';
    import BoxedLayout from '../layouts/Boxed';

    export default {
        components: { BootCheck, BoxedLayout },

        props: {
            current: Boolean,
        },

        data: () => ({
            bootState: 'loading',
            bootDescription: '',
            consoleOutput: null,
        }),

        methods: {
            install() {
                this.$store.dispatch('tasks/execute', { name: 'composer/install' }).then(() => {
                    window.location.reload();
                });
            },
        },

        created() {
            this.bootDescription = this.$t('ui.server.running');

            this.$store.dispatch('server/composer/get').then((result) => {
                if (result.json.found && !result.vendor.found) {
                    this.bootState = 'info';
                    this.bootDescription = this.$t('ui.server.composer.install');
                } else {
                    this.bootState = 'success';
                    this.bootDescription = this.$t('ui.server.composer.success');
                    this.$emit('success', 'Composer');
                }
            }).catch((response) => {
                if (response.status === 503) {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.prerequisite');
                } else {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.error');
                }

                this.$emit('error', 'Composer');
            });
        },
    };
</script>
