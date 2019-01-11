<template>
    <composer-package shave-description :data="metadata || data"/>
</template>

<script>
    import metadata from '../../../mixins/metadata';
    import ComposerPackage from './ComposerPackage';

    export default {
        mixins: [metadata],
        components: { ComposerPackage },

        props: {
            data: {
                type: Object,
                required: true,
            },
        },

        data: () => ({
            data: null,
        }),

        methods: {
            async load() {
                this.data = this.package;

                const metadata = await this.$store.dispatch('packages/search/get', this.package.name);

                if (null !== metadata) {
                    this.data = Object.assign({}, this.package, metadata);
                }
            },
        },

        watch: {
            package() {
                this.load();
            },
        },

        created() {
            this.load();
            this.$watch(this.$i18n.locale, this.load);
        },
    };
</script>
