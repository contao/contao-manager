<template>
    <popup-overlay :popup-class="popupClass">
        <h1 :class="headlineClass">{{$t('ui.logout.headline')}}</h1>

        <template v-if="countdown > 0">
            <p class="logout-warning__text">{{$t('ui.logout.warning')}}</p>
            <p class="logout-warning__countdown">{{minutes}}:{{seconds}}</p>

            <loading-button color="primary" :loading="renew" :disabled="logout" @click="keepAlive">{{$t('ui.logout.renew')}}</loading-button>
            <loading-button @click="doLogout" :loading="logout" :disabled="renew">{{$t('ui.logout.logout')}}</loading-button>
        </template>
        <template v-else>
            <p class="logout-warning__text">{{$t('ui.logout.expired')}}</p>
            <loading-button @click="close">{{$t('ui.logout.login')}}</loading-button>
        </template>
    </popup-overlay>
</template>

<script>
    import { mapState } from 'vuex';

    import PopupOverlay from 'contao-package-list/src/components/fragments/PopupOverlay';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { PopupOverlay, LoadingButton },

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

            async doLogout() {
                this.logout = true;
                await this.$store.dispatch('auth/logout');
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
        top: 50%;
        left: 50%;
        width: 500px;
        max-width: 90%;
        text-align: center;
        background: var(--popup-bg);
        z-index: 10;
        opacity: 1;
        transform: translate(-50%, -50%);
        border-radius: var(--border-radius);
        overflow: hidden;

        &__headline {
            position: relative;
            background: var(--contao);
            color: #fff;
            font-weight: $font-weight-normal;
            line-height: 40px;

            &--complete {
                background-color: var(--btn-primary);
            }

            &--error {
                background-color: var(--btn-alert);
            }
        }

        &__text {
            margin: 2em 40px;
        }

        &__countdown {
            margin: -20px 0 20px;
            font: $font-weight-bold 4em/1.6 $font-monospace;
            color: var(--btn-warning);
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
