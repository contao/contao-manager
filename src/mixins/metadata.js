export default {
    data: () => ({
        metadata: null,
    }),

    methods: {
        async loadMetadata() {
            const metadata = await this.$store.dispatch('packages/search/get', this.data.name);

            if (metadata) {
                this.metadata = Object.assign(
                    {},
                    this.data,
                    metadata,
                    {
                        metadata: `https://github.com/contao/package-metadata/tree/master/meta/${this.data.name}/${this.$i18n.locale()}.yml`
                    }
                );
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
        this.$watch(this.$i18n.locale, this.loadMetadata);
    },
}
