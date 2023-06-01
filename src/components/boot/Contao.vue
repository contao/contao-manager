<template>
    <boot-check :progress="bootState" :title="$t('ui.server.contao.title')" :description="bootDescription"></boot-check>
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

                this.$store.dispatch('server/contao/get').then((result) => {
                    if (!result.version) {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.contao.empty');
                    } else if (!result.supported) {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.contao.old', result);
                    } else {
                        this.bootState = 'success';
                        this.bootDescription = this.$t('ui.server.contao.found', result);

                        this.$store.commit('setVersions', result);
                    }
                }).catch((response) => {
                    if (response.status === 503) {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.prerequisite');
                    } else if (response.status === 502) {
                        window.localStorage.removeItem('contao_manager_booted');
                        this.$store.commit('setView', views.RECOVERY);
                    } else {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.error');
                    }
                }).then(() => {
                    this.$emit('result', 'Contao', this.bootState);
                });
            },
        },
    };
</script>
