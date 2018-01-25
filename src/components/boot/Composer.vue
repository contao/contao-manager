<template>
    <boot-check v-else :progress="bootState" :title="$t('ui.system.composer.title')" :description="bootDescription">
        <button v-if="bootState === 'info'" @click="install" class="widget-button widget-button--primary widget-button--run">{{ 'ui.system.composer.button' | translate }}</button>
    </boot-check>
</template>

<script>
    import api from '../../api';

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
            update: false,
        }),

        methods: {
            install() {
                let task;

                if (this.update) {
                    task = {
                        type: 'upgrade',
                    };
                } else {
                    task = {
                        type: 'composer-install',
                    };
                }

                this.$store.dispatch('tasks/execute', task).then(() => {
                    window.location.reload();
                });
            },
        },

        created() {
            this.bootDescription = this.$t('ui.system.running');

            api.system.composer().then((result) => {
                if (result.json.found && !result.vendor.found) {
                    if (result.lock.found) {
                        this.bootState = 'info';
                        this.bootDescription = this.$t('ui.system.composer.install');
                    } else {
                        this.bootState = 'info';
                        this.bootDescription = this.$t('ui.system.composer.update');
                        this.update = true;
                    }
                } else {
                    this.bootState = 'success';
                    this.bootDescription = this.$t('ui.system.composer.success');
                    this.$emit('success', 'Composer');
                }
            }).catch((response) => {
                if (response.status === 503) {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.system.prerequisite');
                } else {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.system.error');
                }

                this.$emit('error', 'Composer');
            });
        },
    };
</script>
