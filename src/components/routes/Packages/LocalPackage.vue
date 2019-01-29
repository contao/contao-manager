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
    };
</script>
