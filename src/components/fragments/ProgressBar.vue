<template>
    <div class="progress-bar" :class="{ [`progress-bar--${color}`]: !!color }">
        <div class="progress-bar__label" ref="bar">{{ label ? label : `${progress}%` }}</div>
        <div class="progress-bar__bar" :style="`width:${progress}%`">
            <span :style="`width:${width}px`">{{ label ? label : `${progress}%` }}</span>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        amount: [String, Number],
        label: String,
        color: String,
    },

    data: () => ({
        width: 0,
    }),

    computed: {
        progress() {
            return Math.floor(this.amount);
        },
    },

    methods: {
        updateWidth() {
            if (!this.$refs.bar) {
                return;
            }

            this.width = this.$refs.bar.clientWidth;
        },
    },

    mounted() {
        setTimeout(this.updateWidth, 0);
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use '~contao-package-list/src/assets/styles/defaults';

.progress-bar {
    --progress-color: var(--btn-warning);

    position: relative;
    width: 100%;
    height: 30px;
    background: var(--clr-btn);
    border: 2px solid var(--progress-color);
    color: #000;
    font-weight: defaults.$font-weight-bold;
    text-align: center;
    line-height: 26px;

    &__bar {
        position: absolute;
        overflow: hidden;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: var(--progress-color);

        span {
            display: block;
            color: var(--clr-btn);
            text-align: center;
        }
    }

    &--primary {
        --progress-color: var(--btn-primary);
    }

    &--alert {
        --progress-color: var(--btn-alert);
    }
}
</style>
