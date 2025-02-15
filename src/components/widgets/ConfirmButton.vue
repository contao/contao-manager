<template>
    <button
        type="button"
        :class="buttonClass"
        :disabled="disabled"
        @click="click"
    >
        <span :class="slotClass"><slot/></span>
        <span class="confirm-button__icon" :class="{ 'confirm-button__icon--confirm': confirm }">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
        </span>
    </button>
</template>

<script>
    export default {
        emits: ['click'],

        props: {
            color: String,
            icon: String,
            inline: Boolean,
            small: Boolean,
            disabled: Boolean,
        },

        data: () => ({
            confirm: false,
        }),

        computed: {
            buttonClass: vm => ({
                'confirm-button': true,
                'widget-button': true,
                'widget-button--inline': vm.inline,
                'widget-button--small': vm.small,
                [`widget-button--${vm.color}`]: vm.color,
            }),

            slotClass: vm => ({
                [`widget-button--${vm.icon}`]: vm.icon,
            }),
        },

        methods: {
            click(e) {
                if (!this.confirm) {
                    e.preventDefault();
                    e.target.blur();
                    this.$emit('click', e);

                    this.confirm = true;
                    setTimeout(() => {
                        this.confirm = false;
                    }, 1000);
                }
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
.confirm-button {
    position: relative;

    &__icon {
        display: none;
        position: absolute;
        opacity: 0;
        z-index: 100;

        &--confirm {
            display: block;
            animation: .5s ease-out 0s confirm_button;
        }

        svg {
            fill: var(--btn-primary);
            width: 100%;
            height: 100%;
        }
    }

    @keyframes confirm_button {
        0% {
            opacity: 1;
            height: 10px;
            width: 10px;
            left: calc(50% - 10px / 2);
            top: calc(50% - 10px / 2);
        }
        100% {
            opacity: 0;
            height: 150px;
            width: 150px;
            left: calc(50% - 150px / 2);
            top: calc(50% - 150px / 2);
        }
    }
}
</style>
