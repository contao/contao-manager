<template>
    <confirm-button
        :class="{ 'widget-button--small': small, 'widget-button--inline': inline }"
        color="primary"
        icon="add"
        :small="small"
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
            ...mapGetters('packages', ['packageInstalled', 'packageAdded']),

            isInstalled: vm => vm.packageInstalled(vm.data.name) || vm.packageAdded(vm.data.name),
        },

        methods: {
            install() {
                this.$store.commit('packages/add', { name: this.data.name });
            },
        }
    };
</script>
