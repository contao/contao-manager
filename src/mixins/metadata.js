export default {
    data: () => ({
        metadata: null,
    }),

    methods: {
        async load() {
            const metadata = await this.$store.dispatch('packages/search/get', this.data.name);

            if (metadata) {
                this.metadata = Object.assign({}, this.data, metadata);
            }
        },
    },

    watch: {
        data() {
            this.load();
        },
    },

    created() {
        this.load();
        this.$watch(this.$i18n.locale, this.load);
    },
}
