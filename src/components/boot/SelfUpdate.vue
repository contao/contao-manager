<template>
    <boot-check :progress="bootState" :title="$t('ui.server.selfUpdate.title')" :description="bootDescription">
        <button class="widget-button widget-button--alert" v-if="bootState === 'error'" @click="update" :disabled="processing">{{ 'ui.server.selfUpdate.button' | translate }}</button>
    </boot-check>
</template>

<script>
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
            this.bootDescription = this.$t('ui.server.running');

            this.$store.dispatch('server/self-update/get').then((result) => {
                const context = { current: result.current_version, latest: result.latest_version };

                if (result.current_version === result.latest_version) {
                    this.bootState = 'success';
                    this.bootDescription = this.$t('ui.server.selfUpdate.latest', context);
                } else if (result.latest_version === null) {
                    this.bootState = 'info';
                    this.bootDescription = this.$t('ui.server.selfUpdate.dev');
                } else {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.selfUpdate.update', context);
                }
            }).catch(() => {
                this.bootState = 'error';
                this.bootDescription = this.$t('ui.server.error');
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
