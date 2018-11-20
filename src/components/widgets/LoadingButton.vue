<template>
    <button
        type="button"
        :class="buttonClass"
        :disabled="disabled || loading"
        @click.prevent="(e) => { $emit('click', e) }"
    >
        <span :class="{ loading }"><slot/></span>
        <loader v-show="loading"/>
    </button>
</template>

<script>
    import Loader from '../fragments/Loader';

    export default {
        components: { Loader },

        props: {
            color: String,
            icon: String,
            loading: Boolean,
            disabled: Boolean,
        },

        computed: {
            buttonClass: vm => ({
                'loading-button': true,
                'widget-button': true,
                [`widget-button--${vm.color}`]: vm.color && !vm.loading,
                [`widget-button--${vm.icon}`]: vm.icon && !vm.loading,
            }),
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    .loading-button {
        position: relative;

        > .loader {
            position: absolute;
            left: calc(50% - 25px / 2);
            top: calc(50% - 25px / 2);
        }

        > .loading {
            visibility: hidden;
        }
    }
</style>
