export default {
    data: () => ({
        metadata: null,
    }),

    methods: {
        async loadMetadata() {
            const metadata = await this.$store.dispatch('packages/search/get', this.data.name);

            if (metadata) {
                this.metadata = Object.assign({}, this.data, metadata);
            }
        },
    },

    watch: {
        data() {
            this.loadMetadata();
        },
    },

    created() {
        this.loadMetadata();
        this.$watch(this.$i18n.locale, this.load);
    },
}
