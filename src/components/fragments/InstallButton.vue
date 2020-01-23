<template>
    <confirm-button
        color="primary"
        icon="add"
        :small="small"
        :inline="inline"
        :disabled="isInstalled || disabled"
        @click="install"
    >{{ $t(small ? 'ui.package.installButtonShort' : 'ui.package.installButton') }}</confirm-button>
</template>

<script>
    import { mapGetters } from 'vuex';

    import ConfirmButton from '../widgets/ConfirmButton';

    export default {
        components: { ConfirmButton },

        props: {
            data: {
                type: Object,
                required: true,
            },
            small: Boolean,
            inline: Boolean,
            disabled: Boolean,
        },

        computed: {
            ...mapGetters('packages', ['packageInstalled', 'packageRoot', 'packageAdded', 'packageRequired']),

            isInstalled: vm => (vm.packageInstalled(vm.data.name) && vm.packageRoot(vm.data.name))
                || vm.packageAdded(vm.data.name)
                || vm.packageRequired(vm.data.name),
        },

        methods: {
            install() {
                this.$store.commit('packages/add', { name: this.data.name });
            },
        }
    };
</script>
