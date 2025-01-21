<template>
    <popup-overlay
        class="logout-warning"
        :headline="$t('ui.logout.headline')"
        :headlineType="this.countdown === 0 ? 'alert' : null"
    >
        <template v-if="countdown > 0">
            <p class="logout-warning__text">{{$t('ui.logout.warning')}}</p>
            <p class="logout-warning__countdown">{{minutes}}:{{seconds}}</p>
        </template>
        <template v-else>
            <p class="logout-warning__text">{{$t('ui.logout.expired')}}</p>
        </template>

        <template #actions>
            <template v-if="countdown > 0">
                <loading-button color="primary" :loading="renew" :disabled="logout" @click="keepAlive">{{$t('ui.logout.renew')}}</loading-button>
                <loading-button @click="doLogout" :loading="logout" :disabled="renew">{{$t('ui.logout.logout')}}</loading-button>
            </template>
            <loading-button @click="close" v-else>{{$t('ui.logout.login')}}</loading-button>
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
@use "~contao-package-list/src/assets/styles/defaults";

.logout-warning {
    text-align: center;

    &__countdown {
        margin: 20px 0;
        font: defaults.$font-weight-bold 4em/1.6 defaults.$font-monospace;
        color: var(--btn-warning);
    }
}
</style>
