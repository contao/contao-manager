<template>
    <composer-package :package="data"/>
</template>

<script>
    import ComposerPackage from './ComposerPackage';

    export default {
        components: { ComposerPackage },

        props: {
            package: {
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
        },
    };
</script>
