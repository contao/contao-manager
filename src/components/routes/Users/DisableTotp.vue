<template>
    <popup-overlay class="disable-totp" :headline="$t('ui.totp.headline')" @submit="submit" @clear="close">
        <p class="disable-totp__text">{{ $t('ui.totp.disableText') }}</p>

        <text-field
            ref="totp" name="totp"
            required pattern="\d+" minlength="6" maxlength="6"
            autocomplete="one-time-code"
            :error="error" @keyup="error = ''"
            v-model="totp"
        />

        <template #actions>
            <button type="button" class="widget-button" :disabled="loading" @click="close">{{ $t('ui.totp.cancel') }}</button>
            <loading-button submit color="primary" :loading="loading">{{ $t('ui.totp.disable') }}</loading-button>
        </template>
    </popup-overlay>
</template>

<script>
    import { mapState } from 'vuex';
    import PopupOverlay from 'contao-package-list/src/components/fragments/PopupOverlay';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
    import TextField from '../../widgets/TextField.vue';

    export default {
        components: { PopupOverlay, LoadingButton, TextField },

        data: () => ({
            loading: false,
            error: '',
            totp: null,
        }),

        computed: {
            ...mapState('auth', ['username']),
        },

        methods: {
            submit() {
                this.loading = true;

                this.$request.delete(`api/users/${ this.username }/totp`, {
                    data: { totp: this.totp, },
                }, null, {
                    200: async () => {
                        await this.$store.dispatch('auth/status');
                        this.$notify.success(this.$t('ui.totp.disabled'));
                        this.close();
                    },
                    422: () => {
                        this.loading = false;
                        this.error = this.$t('ui.totp.invalid');
                        this.$refs.totp.focus();
                    }
                });
            },

            close() {
                this.$store.commit('modals/close', 'disable-totp');
            },
        },

        mounted() {
            setTimeout(() => {
                this.$refs.totp.focus();
            }, 0);
        }
    };
</script>

<style rel="stylesheet/scss" lang="scss">
.disable-totp {
    &__text {
        margin-bottom: 1em;
    }
}
</style>
