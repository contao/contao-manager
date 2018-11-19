<template>
    <composer-package :data="data"/>
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

        computed: {
            data() {
                return Object.assign(
                    {},
                    this.package,
                    {
                        title: this.package._highlightResult.title.value,
                        hideName: this.package.title === this.package.name,
                        description: this.package._highlightResult.description.value,
                    }
                );
            },
        },
    };
</script>
