<template>
    <boot-check :progress="bootState" :title="$t('ui.server.composer.title')" :description="bootDescription">
        <button v-if="bootState === 'action'" @click="install" class="widget-button widget-button--primary widget-button--run">{{ $t('ui.server.composer.button') }}</button>
    </boot-check>
</template>

<script>
    import { mapState } from 'vuex';
    import boot from '../../mixins/boot';
    import BootCheck from '../fragments/BootCheck';

    export default {
        mixins: [boot],
        components: { BootCheck },

        computed: {
            ...mapState('tasks', { taskStatus: 'status' }),
        },

        methods: {
            async boot() {
                this.bootState = 'loading';
                this.bootDescription = this.$t('ui.server.running');

                try {
                    const result = await this.$store.dispatch('server/composer/get');

                    if (result.json.found && !result.json.valid) {
                        this.bootState = 'error';
                        this.bootDescription = result.json.error;
                    } else if (result.json.found && !result.vendor.found) {
                        this.bootState = 'action';
                        this.bootDescription = this.$t('ui.server.composer.install');
                        this.$store.commit('setSafeMode', true);
                    } else {
                        this.bootState = 'success';
                        this.bootDescription = this.$t('ui.server.composer.success');
                    }

                    const composerConfig = await this.$store.dispatch('config/composer/get');

                    if (!composerConfig || composerConfig.length === 0) {
                        await this.$store.dispatch('config/composer/writeDefaults');
                    }
                } catch (response) {
                    if (response.status === 503) {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.prerequisite');
                    } else {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.error');
                    }
                }

                this.$emit('result', 'Composer', this.bootState);
            },

            async install() {
                await this.$store.dispatch('tasks/execute', { name: 'composer/install' });

                if (this.taskStatus !== 'complete') {
                    return;
                }

                await this.$store.dispatch('tasks/deleteCurrent');
            },
        },
    };
</script>
