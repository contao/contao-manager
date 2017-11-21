<template>
    <boot-check :progress="bootState" :title="$t('ui.system.php_cli.title')" :description="bootDescription" :detail="problem && problem.detail">
        <a v-if="problem && problem.type" :href="problem.type" target="_blank">{{ 'ui.system.details' | translate }}</a>
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

            problem: {},
        }),

        created() {
            this.bootDescription = this.$t('ui.system.running');

            api.system.phpCli().then((result) => {
                if (result.problem) {
                    this.problem = result.problem;
                    this.bootState = 'error';
                    this.bootDescription = result.problem.title;
                } else {
                    this.bootState = 'success';
                    this.bootDescription = this.$t('ui.system.php_cli.success', { version: result.version });
                }
            }).catch((response) => {
                if (response.status === 503) {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.system.prerequisite');
                } else {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.system.error');
                }
            }).then(() => {
                if (this.bootState === 'error') {
                    this.$emit('error', 'PhpCli');
                } else {
                    this.$emit('success', 'PhpCli');
                }
            });
        },
    };
</script>
