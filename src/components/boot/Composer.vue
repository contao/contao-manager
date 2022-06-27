<template>
    <boot-check :progress="bootState" :title="$t('ui.server.composer.title')" :description="bootDescription">
        <button v-if="bootState === 'action'" @click="install" class="widget-button widget-button--primary widget-button--run">{{ $t('ui.server.composer.button') }}</button>
    </boot-check>
</template>

<script>
    import views from '../../router/views';
    import boot from '../../mixins/boot';
    import BootCheck from '../fragments/BootCheck';

    export default {
        mixins: [boot],
        components: { BootCheck },

        methods: {
            boot() {
                this.bootDescription = this.$t('ui.server.running');

                this.$store.dispatch('server/composer/get').then((result) => {
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
                }).catch((response) => {
                    if (response.status === 503) {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.prerequisite');
                    } else {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.error');
                    }
                }).then(() => {
                    this.$emit('result', 'Composer', this.bootState);
                });
            },

            install() {
                this.$store.dispatch('tasks/execute', { name: 'composer/install' }).then(() => {
                    window.location.reload();
                });
            },
        },
    };
</script>
