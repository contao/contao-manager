<template>
    <boot-check :progress="bootState" :title="$t('ui.server.php_cli.title')" :description="bootDescription" :detail="problem && problem.detail">
        <a v-if="problem && problem.type" :href="problem.type" target="_blank">{{ 'ui.server.details' | translate }}</a>
    </boot-check>
</template>

<script>
    import boot from '../../mixins/boot';

    import BootCheck from '../fragments/BootCheck';

    export default {
        mixins: [boot],
        components: { BootCheck },

        data: () => ({
            problem: {},
        }),

        methods: {
            boot() {
                this.bootDescription = this.$t('ui.server.running');

                this.$store.dispatch('server/php-cli/get').then((result) => {
                    if (result.problem) {
                        this.problem = result.problem;
                        this.bootState = 'error';
                        this.bootDescription = result.problem.title;
                    } else {
                        this.bootState = 'success';
                        this.bootDescription = this.$t('ui.server.php_cli.success', { version: result.version });
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
                    this.$emit('result', 'PhpCli', this.bootState);
                });
            },
        },
    };
</script>
