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
        async boot() {
            this.bootDescription = this.$t('ui.server.running');

            const response = await this.$store.dispatch('server/php-web/get');

            if (response.status === 200) {
                if (response.data.problem) {
                    this.problem = response.data.problem;
                    this.bootState = 'error';
                    this.bootDescription = response.data.problem.title;
                } else if (response.data.version_id < 70000) {
                    this.bootState = 'info';
                    this.bootDescription = this.$t('ui.server.php_web.below7', response.data);
                } else {
                    this.bootState = 'success';
                    this.bootDescription = this.$t('ui.server.php_web.success', response.data);
                }
            } else {
                this.bootState = 'error';
                this.bootDescription = this.$t('ui.server.error');
            }

            this.$emit('result', this.bootState);
        },
    },
};
</script>
