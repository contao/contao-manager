<template>
    <boot-check :progress="bootState" :title="$t('ui.server.selfUpdate.title')" :description="bootDescription">
        <button class="widget-button widget-button--warning" v-if="!isSupported && bootState === 'action'" @click="next" :disabled="processing">{{ $t('ui.server.selfUpdate.continue') }}</button>
        <button class="widget-button widget-button--alert" v-else-if="hasUpdate" @click="update" :disabled="processing">{{ $t('ui.server.selfUpdate.button') }}</button>
    </boot-check>
</template>

<script>
    import boot from '../../mixins/boot';

    import BootCheck from '../fragments/BootCheck';

    export default {
        mixins: [boot],
        components: { BootCheck },

        data: () => ({
            processing: false,
            hasUpdate: false,
            isSupported: true,
        }),

        methods: {
            async boot() {
                this.bootDescription = this.$t('ui.server.running');
                let result;

                try {
                    result = await this.$store.dispatch('server/self-update/get');
                } catch (err) {
                    this.emitState('error', this.$t('ui.server.error'));
                    return;
                }

                const context = { current: result.current_version, latest: result.latest_version };

                if (result.latest_version === null) {
                    this.emitState('info', this.$t('ui.server.selfUpdate.dev'));
                    return;
                }

                if (result.error) {
                    if (result.channel === 'dev') {
                        this.emitState('warning', result.error);
                        return;
                    }

                    try {
                        const latest = await this.$store.dispatch('server/self-update/latest');

                        if (latest === result.current_version) {
                            this.emitState('success', this.$t('ui.server.selfUpdate.latest', context));
                        } else {
                            this.emitState('error', this.$t('ui.server.selfUpdate.manualUpdate', {
                                latest,
                                download: `<a href="${this.$t('ui.server.selfUpdate.manualUpdateUrl')}" target="_blank" rel="noreferrer noopener">${this.$t('ui.server.selfUpdate.manualUpdateUrl')}</a>`
                            }));
                        }
                    } catch (err) {
                        this.emitState('warning', result.error);
                    }
                    return;
                }

                if (result.current_version === result.latest_version) {
                    this.emitState('success', this.$t('ui.server.selfUpdate.latest', context));
                    return;
                }

                if (!result.supported) {
                    this.isSupported = false;
                    this.emitState('action', this.$t('ui.server.selfUpdate.unsupported', context));
                    return;
                }

                if (result.channel === 'dev') {
                    this.hasUpdate = true;
                    this.emitState('warning', this.$t('ui.server.selfUpdate.update', context));
                    return;
                }

                this.hasUpdate = true;
                this.emitState('error', this.$t('ui.server.selfUpdate.update', context));
            },

            update() {
                const reload = () => {
                    this.$store.dispatch('tasks/setDeleting', true);
                    setTimeout(() => {
                        window.location.reload(true);
                    }, 3000);
                };

                this.$store.dispatch('tasks/execute', { name: 'manager/self-update' }).then(reload, reload);
            },

            next() {
                this.bootState = 'info';
                this.$emit('result', 'SelfUpdate', this.bootState);
            },

            emitState(state, description) {
                this.bootState = state;
                this.bootDescription = description;
                this.$emit('result', 'SelfUpdate', state);
            },
        },
    };
</script>
