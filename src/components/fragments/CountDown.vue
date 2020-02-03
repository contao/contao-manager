<template>
    <div>
        <div :style="loading ? 'visibility:hidden' : ''">
            <p :style="`line-height: ${size}px; font-size: ${size/3}px`">{{ countdown }}</p>
            <svg xmlns="http://www.w3.org/2000/svg" :width="size" :height="size">
                <circle :r="circleRadius" :cy="circleWidth" :cx="circleWidth" :stroke-width="strokeWidth" stroke="#31a64b" fill="none"/>
                <circle :r="circleRadius" :cy="circleWidth" :cx="circleWidth" :stroke-width="strokeWidth - 2" stroke="#ffffff" fill="none" :style="`stroke-dasharray: ${circumference}; stroke-dashoffset: ${offset}`"/>
            </svg>
        </div>
        <loader v-show="loading" :style="`position:absolute; left: calc(50% -${size / 2}px); top: calc(50% - ${size / 2}px)`"/>
    </div>
</template>

<script>
    import Loader from 'contao-package-list/src/components/fragments/Loader';

    export default {
        components: { Loader },

        props: {
            seconds: {
                type: Number,
                default: 60,
            },

            size: {
                type: Number,
                default: 24,
            },

            infinite: {
                type: Boolean,
                default: true,
            },

            loading: Boolean,
        },

        data: () => ({
            countdown: 0,
            offset: 0,
            interval: null,
        }),

        computed: {
            strokeWidth: vm => Math.floor(vm.size / 6),
            circleWidth: vm => Math.floor(vm.size / 2),
            circleRadius: vm => vm.circleWidth - vm.strokeWidth + 2,
            circumference: vm => vm.circleRadius * 2 * Math.PI,
        },

        methods: {
            start() {
                clearInterval(this.interval);

                this.countdown = this.seconds;

                this.$nextTick(() => {
                    this.offset = this.circumference;

                    this.interval = setInterval(() => {
                        if (this.countdown === 0) {
                            if (!this.infinite) {
                                clearInterval(this.interval);
                                return;
                            }

                            this.countdown = this.seconds;
                            this.offset = this.circumference
                        }

                        this.offset = (this.countdown * (this.circumference / this.seconds));
                        this.countdown--;
                    }, 1000);
                });
            },
        },

        mounted() {
            this.start();
        },
    };
</script>

<style scoped>
    div {
        position: relative;
        text-align: center;
        line-height: 0;
    }

    p {
        position: absolute;
        width: 100%;
        text-align:center;
    }

    svg {
        -webkit-transform: rotate(-90deg);
        transform: rotate(-90deg);
    }
</style>
