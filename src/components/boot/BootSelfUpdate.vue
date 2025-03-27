<template>
    <boot-check :progress="bootState" :title="$t('ui.server.selfUpdate.title')" :description="bootDescription">
        <template #description v-if="latestDownload">
            <i18n-t keypath="ui.server.selfUpdate.manualUpdate">
                <template #latest>{{ latestDownload }}</template>
                <template #download>
                    <a href="https://to.contao.org/download?lang=${this.$i18n.locale}" target="_blank" rel="noreferrer noopener">https://to.contao.org/download</a>
                </template>
            </i18n-t>
        </template>
        <button class="widget-button widget-button--warning" v-if="!isSupported && bootState === 'action'" @click="next">
            {{ $t('ui.server.selfUpdate.continue') }}
        </button>
        <button class="widget-button widget-button--alert" v-else-if="hasUpdate" @click="update">
            {{ $t('ui.server.selfUpdate.button') }}
        </button>
    </boot-check>
</template>

<script>
import boot from '../../mixins/boot';
import BootCheck from '../fragments/BootCheck';

export default {
    mixins: [boot],
    components: { BootCheck },

    data: () => ({
        hasUpdate: false,
        isSupported: true,
        latestDownload: null,
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
                        this.latestDownload = latest;
                        this.emitState(
                            'error',
                            this.$t('ui.server.selfUpdate.manualUpdate', {
                                latest,
                                download: `<a href="https://to.contao.org/download?lang=${this.$i18n.locale}" target="_blank" rel="noreferrer noopener">https://to.contao.org/download</a>`,
                            }),
                        );
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

        async update() {
            try {
                await this.$store.dispatch('tasks/execute', { name: 'manager/self-update', ignoreErrors: true });
            } catch (err) {
                // ignore error and reload
            }

            setTimeout(() => {
                window.location.reload(true);
            }, 3000);
        },

        next() {
            this.bootState = 'info';
            this.$emit('result', this.bootState);
        },

        emitState(state, description) {
            this.bootState = state;
            this.bootDescription = description;
            this.$emit('result', state);
        },
    },
};
</script>
