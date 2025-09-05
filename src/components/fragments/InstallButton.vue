<template>
    <confirm-button
        color="primary"
        icon="add"
        :small="small"
        :inline="inline"
        :disabled="disabled || isRootInstalled || isAdded || isRequired || !canBeInstalled || !isCompatible"
        @click="install"
        v-if="isGranted(scopes.INSTALL)"
    >
        {{ $t(small ? 'ui.package.installButtonShort' : 'ui.package.installButton') }}
    </confirm-button>
</template>

<script>
import { mapGetters } from 'vuex';
import scopes from '../../scopes';
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

    computed: {
        ...mapGetters('auth', ['isGranted']),
        scopes: () => scopes,
    },
};
</script>
