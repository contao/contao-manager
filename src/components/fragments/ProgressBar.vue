<template>
    <div class="progress-bar">
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
            this.$nextTick(this.updateWidth);
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .progress-bar {
        position: relative;
        width: 100%;
        height: 30px;
        background: #fff;
        border: 2px solid $orange-button;
        color: #000;
        font-weight: $font-weight-bold;
        text-align: center;
        line-height: 26px;

        &__bar {
            position: absolute;
            overflow: hidden;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            background: $orange-button;

            span {
                display: block;
                color: #fff;
                text-align: center;
            }
        }

    }
</style>
