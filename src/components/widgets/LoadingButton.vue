<template>
    <button
        :type="submit ? 'submit' : 'button'"
        :class="buttonClass"
        :disabled="disabled || loading"
        @click="click"
    >
        <span :class="slotClass"><slot/></span>
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
            inline: Boolean,
            loading: Boolean,
            disabled: Boolean,
            submit: Boolean,
        },

        computed: {
            buttonClass: vm => ({
                'loading-button': true,
                'widget-button': true,
                'widget-button--inline': vm.inline,
                [`widget-button--${vm.color}`]: vm.color,
            }),

            slotClass: vm => ({
                loading: vm.loading,
                [`widget-button--${vm.icon}`]: vm.icon,
            }),
        },

        methods: {
            click(e) {
                if (!this.submit) {
                    e.preventDefault();
                    $emit('click', e);
                }
            },
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
