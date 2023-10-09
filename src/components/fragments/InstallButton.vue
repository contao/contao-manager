<template>
    <confirm-button
        color="primary"
        icon="add"
        :small="small"
        :inline="inline"
        :disabled="disabled || isRootInstalled || isAdded || isRequired || !canBeInstalled"
        @click="install"
    >{{ $t(small ? 'ui.package.installButtonShort' : 'ui.package.installButton') }}</confirm-button>
</template>

<script>
    import packageStatus from '../../mixins/packageStatus';
    import ConfirmButton from '../widgets/ConfirmButton';

    export default {
        components: { ConfirmButton },
        mixins: [packageStatus],

        props: {
            data: {
                type: Object,
                required: true,
            },
            small: Boolean,
            inline: Boolean,
            disabled: Boolean,
        },

        methods: {
            install() {
                this.$store.commit('packages/add', { name: this.data.name });
            },
        }
    };
</script>
