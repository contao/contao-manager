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
            load() {
                this.data = this.package;

                this.$store.dispatch('packages/fetch', this.package.name).then(
                    (data) => {
                        this.data = Object.assign({}, this.package, data);
                    },
                    () => {
                        // Ignore if package is not found in index
                    },
                );
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
