<template>
    <package :package="data" v-else/>
</template>

<script>
    import Package from './Package';

    export default {
        components: { Package },

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
                        this.data = Object.assign(this.package, data);
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
