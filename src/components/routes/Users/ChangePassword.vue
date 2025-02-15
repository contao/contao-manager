<template>
    <popup-overlay class="change-password" :headline="$t('ui.user-manager.passwordHeadline')" @submit="submit" @clear="close">
        <template>
            <p>{{ $t('ui.user-manager.passwordText') }}</p>

            <text-field
                ref="password"
                name="current-password" type="password"
                :label="$t('ui.user-manager.currentPassword')"
                required minlength="8"
                autocomplete="current-password"
                :error="error" @keyup="error = ''"
                v-model="currentPassword"
                :disabled="loading"
            />

            <text-field
                name="new-password" type="password"
                :label="$t('ui.user-manager.newPassword')"
                required minlength="8"
                autocomplete="new-password"
                v-model="newPassword"
                :disabled="loading"
            />
        </template>

        <template #actions>
            <button type="button" class="widget-button" :disabled="loading" @click="close">{{ $t('ui.user-manager.cancel') }}</button>
            <loading-button submit color="primary" :loading="loading">{{ $t('ui.user-manager.submitPassword') }}</loading-button>
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
            currentPassword: null,
            newPassword: null,
            newPasswordConfirm: null,
            error: '',
        }),

        computed: {
            ...mapState('auth', ['username']),
        },

        methods: {
            async submit() {
                this.loading = true;

                await this.$request.put(`api/users/${ this.username }/password`, {
                    current_password: this.currentPassword,
                    new_password: this.newPassword,
                }, null, {
                    200: () => {
                        this.$notify.success(this.$t('ui.user-manager.passwordChanged'));
                        this.close();
                    },
                    422: () => {
                        this.error = this.$t('ui.user-manager.passwordError');

                        setTimeout(() => {
                            this.$refs.password.focus();
                        }, 0);
                    }
                });

                this.loading = false;
            },

            close() {
                this.$store.commit('modals/close', 'change-password');
            },
        },

        mounted () {
            this.$refs.password.focus();
        }
    };
</script>

<style rel="stylesheet/scss" lang="scss">
.change-password {
    .widget {
        margin: 1em 0 0;
    }
}
</style>
