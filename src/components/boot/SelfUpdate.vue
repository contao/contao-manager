<template>
    <boot-check :progress="bootState" :title="$t('ui.server.selfUpdate.title')" :description="bootDescription">
        <button class="widget-button widget-button--warning" v-if="!isSupported && bootState === 'action'" @click="next">{{ $t('ui.server.selfUpdate.continue') }}</button>
        <button class="widget-button widget-button--alert" v-else-if="hasUpdate" @click="update">{{ $t('ui.server.selfUpdate.button') }}</button>
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
        }),

        methods: {
            boot() {
                this.bootDescription = this.$t('ui.server.running');

                this.$store.dispatch('server/self-update/get').then((result) => {
                    const context = { current: result.current_version, latest: result.latest_version };

                    if (result.latest_version === null) {
                        this.bootState = 'info';
                        this.bootDescription = this.$t('ui.server.selfUpdate.dev');
                    } else if (result.current_version === result.latest_version) {
                        this.bootState = 'success';
                        this.bootDescription = this.$t('ui.server.selfUpdate.latest', context);
                    } else if (!result.supported) {
                        this.bootState = 'action';
                        this.bootDescription = this.$t('ui.server.selfUpdate.unsupported', context);
                        this.isSupported = false;
                    } else if (result.channel === 'dev') {
                        this.bootState = 'warning';
                        this.bootDescription = this.$t('ui.server.selfUpdate.update', context);
                        this.hasUpdate = true;
                    } else {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.selfUpdate.update', context);
                        this.hasUpdate = true;
                    }
                }).catch(() => {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.error');
                }).then(() => {
                    this.$emit('result', 'SelfUpdate', this.bootState);
                });
            },

            async update() {
                try {
                    await this.$store.dispatch('tasks/execute', { name: 'manager/self-update', await: true });
                } catch (err) {
                    // ignore error and reload
                }

                setTimeout(() => {
                    window.location.reload(true);
                }, 3000);
            },

            next() {
                this.bootState = 'info';
                this.$emit('result', 'SelfUpdate', this.bootState);
            },
        },
    };
</script>
