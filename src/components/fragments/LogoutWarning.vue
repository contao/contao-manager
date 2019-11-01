<template>
    <div class="popup-overlay">
        <div ref="popup" :class="popupClass">
            <h1 :class="headlineClass">{{$t('ui.logout.headline')}}</h1>

            <template v-if="countdown > 0">
                <p class="logout-warning__text">{{$t('ui.logout.warning')}}</p>
                <p class="logout-warning__countdown">{{minutes}}:{{seconds}}</p>

                <loading-button color="primary" :loading="renew" :disabled="logout" @click="keepAlive">{{$t('ui.logout.renew')}}</loading-button>
                <loading-button @click="logout" :loading="logout" :disabled="renew">{{$t('ui.logout.logout')}}</loading-button>
            </template>
            <template v-else>
                <p class="logout-warning__text">{{$t('ui.logout.expired')}}</p>
                <loading-button @click="close">{{$t('ui.logout.login')}}</loading-button>
            </template>
        </div>
    </div>
</template>

<script>
    import { mapState } from 'vuex';

    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { LoadingButton },

        data: () => ({
            renew: false,
            logout: false,
        }),

        computed: {
            ...mapState('auth', ['countdown']),

            minutes() {
                return Math.floor(this.countdown / 60);
            },

            seconds() {
                const seconds = this.countdown % 60;

                if (seconds < 10) {
                    return `0${seconds}`;
                }

                return seconds;
            },

            popupClass() {
                return {
                    'logout-warning': true,
                    'logout-warning--fixed': !this.$refs.popup || this.$refs.popup.clientHeight < window.innerHeight,
                };
            },

            headlineClass() {
                return {
                    'logout-warning__headline': true,
                    'logout-warning__headline--error': this.countdown === 0,
                };
            },
        },

        methods: {
            async keepAlive() {
                this.renew = true;
                await this.$store.dispatch('auth/status');
                this.renew = false;
            },

            async logout() {
                this.logout = true;
                await this.$store.dispatch('auth/logout');
                this.$store.commit('auth/resetCountdown');
                this.logout = false;
            },

            close() {
                this.$store.commit('auth/resetCountdown');
            }
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .logout-warning {
        position: fixed;
        display: block;
        top: 20%;
        left: 50%;
        width: 500px;
        max-width: 90%;
        text-align: center;
        background: #ffffff;
        z-index: 10;
        opacity: 1;
        transform: translateX(-50%);
        border-bottom: 2px solid #ddd3bc;
        border-radius: 2px;

        &__headline {
            position: relative;
            background: $contao-color;
            color: #fff;
            font-weight: $font-weight-normal;
            line-height: 40px;
            border-radius: 2px 2px 0 0;

            &--complete {
                background-color: $green-button;
            }

            &--error {
                background-color: $red-button;
            }
        }

        &__text {
            margin: 2em 40px;
        }

        &__countdown {
            margin: -20px 0 20px;
            font: $font-weight-bold 4em/1.6 $font-monospace;
            color: $orange-button;
        }

        .widget-button {
            width: auto;
            height: 35px;
            margin: 0 5px 2em 5px;
            padding: 0 30px;
            line-height: 35px;
        }
    }
</style>
