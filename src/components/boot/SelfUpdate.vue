<template>
    <boot-check :progress="bootState" :title="$t('ui.system.selfUpdate.title')" :description="bootDescription">
        <button class="widget-button widget-button--alert" v-if="bootState === 'error'" @click="update" :disabled="processing">{{ 'ui.system.selfUpdate.button' | translate }}</button>
    </boot-check>
</template>

<script>
    import api from '../../api';

    import BootCheck from '../fragments/BootCheck';

    export default {
        components: { BootCheck },

        props: {
            current: Boolean,
        },

        data: () => ({
            bootState: 'loading',
            bootDescription: '',
            processing: false,
        }),

        methods: {
            update() {
                this.$store.dispatch('tasks/execute', { type: 'self-update' }).then(
                    () => { window.location.reload(true); },
                    () => { window.location.reload(true); },
                );
            },
        },

        created() {
            this.bootDescription = this.$t('ui.system.running');

            api.system.selfUpdate().then((result) => {
                const context = { current: result.current_version, latest: result.latest_version };

                if (result.has_update === true) {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.system.selfUpdate.update', context);
                } else if (result.latest_version) {
                    this.bootState = 'success';
                    this.bootDescription = this.$t('ui.system.selfUpdate.latest', context);
                } else {
                    this.bootState = 'info';
                    this.bootDescription = this.$t('ui.system.selfUpdate.dev');
                }
            }).catch(() => {
                this.bootState = 'error';
                this.bootDescription = this.$t('ui.system.error');
            }).then(() => {
                if (this.bootState === 'error') {
                    this.$emit('error', 'SelfUpdate');
                } else {
                    this.$emit('success', 'SelfUpdate');
                }
            });
        },
    };
</script>
