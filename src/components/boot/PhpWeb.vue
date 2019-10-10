<template>
    <boot-check :progress="bootState" :title="$t('ui.server.php_web.title')" :description="bootDescription" :detail="problem && problem.detail">
        <a v-if="problem && problem.type" :href="problem.type" target="_blank">{{ $t('ui.server.details') }}</a>
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

                this.$store.dispatch('server/php-web/get').then((result) => {
                    if (result.problem) {
                        this.problem = result.problem;
                        this.bootState = 'error';
                        this.bootDescription = result.problem.title;
                    } else if (result.version_id < 70000) {
                        this.bootState = 'info';
                        this.bootDescription = this.$t('ui.server.php_web.below7', result);
                    } else {
                        this.bootState = 'success';
                        this.bootDescription = this.$t('ui.server.php_web.success', result);
                    }
                }).catch(() => {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.error');
                }).then(() => {
                    this.$emit('result', 'PhpWeb', this.bootState);
                });
            },
        },
    };
</script>
