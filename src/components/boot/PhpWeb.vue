<template>
    <boot-check v-else :progress="bootState" :title="$t('ui.system.php_web.title')" :description="bootDescription" :detail="problem && problem.detail">
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

            api.system.phpWeb().then((result) => {
                if (result.problem) {
                    this.problem = result.problem;
                    this.bootState = 'error';
                    this.bootDescription = result.problem.title;
                } else if (result.version_id < 70000) {
                    this.bootState = 'info';
                    this.bootDescription = this.$t('ui.system.php_web.below7', result);
                } else {
                    this.bootState = 'success';
                    this.bootDescription = this.$t('ui.system.php_web.success', result);
                }
            }).catch(() => {
                this.bootState = 'error';
                this.bootDescription = this.$t('ui.system.error');
            }).then(() => {
                if (this.bootState === 'error') {
                    this.$emit('error', 'PhpWeb');
                } else {
                    this.$emit('success', 'PhpWeb');
                }
            });
        },
    };
</script>
