export default {
    props: {
        ready: false,
        current: false,
    },

    data: () => ({
        booted: false,
        bootState: 'loading',
        bootDescription: '',
    }),

    watch: {
        ready(ready) {
            if (ready) {
                this.booted = true;
                this.boot();
            }
        },
    },

    created() {
        this.bootDescription = this.$t('ui.server.pending');

        if (this.ready) {
            this.booted = true;
            this.boot();
        }
    },
};
