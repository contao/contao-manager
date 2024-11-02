<template>
    <boot-check :progress="bootState" :title="$t('ui.server.config.title')" :description="bootDescription">
        <button class="widget-button widget-button--alert" v-if="bootState === 'error' || bootState === 'action'" @click="showConfiguration">{{ $t('ui.server.config.setup') }}</button>
        <button class="widget-button widget-button--edit" v-else-if="bootState !== 'loading'" @click="showConfiguration">{{ $t('ui.server.config.change') }}</button>
    </boot-check>
</template>

<script>
import boot from '../../mixins/boot';
import views from '../../router/views';
import BootCheck from '../fragments/BootCheck';

export default {
        mixins: [boot],
        components: { BootCheck },

        methods: {
            async boot() {
                this.bootDescription = this.$t('ui.server.running');

                this.$store.dispatch('server/config/get').then((result) => {

                    this.php_cli = result.php_cli;
                    this.cloud = result.cloud.enabled;
                    this.cloudIssues = result.cloud.issues;

                    if (!result.php_cli) {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.config.stateErrorCli');
                    } else if (result.cloud.enabled && result.cloud.issues.length > 0) {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.config.stateErrorCloud');
                    } else {
                        this.bootState = 'success';
                        this.bootDescription = this.$t('ui.server.config.stateSuccess', { php_cli: result.php_cli });
                    }
                }).catch(() => {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.error');
                }).then(() => {
                    this.$emit('result', this.bootState);
                });
            },

            showConfiguration() {
                this.$store.commit('setView', views.CONFIG);
            },
        }
    };
</script>
