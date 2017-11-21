<template>
    <boot-check v-else :progress="bootState" :title="$t('ui.system.composer.title')" :description="bootDescription">
    </boot-check>
</template>

<script>
    import api from '../../api';

    import BootCheck from '../fragments/BootCheck';
    import BoxedLayout from '../layouts/Boxed';

    export default {
        components: { BootCheck, BoxedLayout },

        props: {
            current: Boolean,
        },

        data: () => ({
            bootState: 'loading',
            bootDescription: '',
            consoleOutput: null,
        }),

        created() {
            this.bootDescription = this.$t('ui.system.running');

            api.system.composer().then(() => {
                this.bootState = 'success';
                this.bootDescription = this.$t('ui.system.composer.success');
                this.$emit('success', 'Composer');
            }).catch((response) => {
                if (response.status === 503) {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.system.prerequisite');
                } else {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.system.error');
                }

                this.$emit('error', 'Composer');
            });
        },
    };
</script>
