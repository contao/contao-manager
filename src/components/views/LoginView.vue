<template>
    <boxed-layout slotClass="view-login">
        <header class="view-login__header">
            <img src="../../assets/images/logo.svg" width="80" height="80" alt="Contao Logo" />
            <p class="view-login__product">Contao Manager</p>
        </header>
        <main class="view-login__locked" v-if="locked">
            <i18n-t tag="p" keypath="ui.login.locked">
                <template #lockFile><strong>contao-manager/login.lock</strong><br /></template>
            </i18n-t>
        </main>
        <transition name="animate-flip" type="transition" mode="out-in" v-else>
            <main class="view-login__form" v-if="!requires_totp">
                <form @submit.prevent="login">
                    <h1 class="view-login__headline">{{ $t('ui.login.headline') }}</h1>
                    <p class="view-login__description">{{ $t('ui.login.description') }}</p>

                    <text-field
                        ref="username"
                        name="username"
                        autocomplete="username webauthn"
                        :label="$t('ui.login.username')"
                        :placeholder="$t('ui.login.username')"
                        class="view-login__user"
                        :class="login_failed ? 'widget--error' : ''"
                        :disabled="logging_in || passkey_login"
                        v-model="username"
                        @input="reset"
                    />
                    <text-field
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        :label="$t('ui.login.password')"
                        :placeholder="$t('ui.login.password')"
                        minlength="8"
                        class="view-login__password"
                        :class="login_failed ? 'widget--error' : ''"
                        :disabled="logging_in || passkey_login"
                        v-model="password"
                        @input="reset"
                    />

                    <loading-button
                        submit
                        class="view-login__button"
                        color="primary"
                        :disabled="!inputValid || login_failed || passkey_login"
                        :loading="logging_in && !passkey_login"
                    >
                        {{ $t('ui.login.button') }}
                    </loading-button>

                    <p class="view-login__or">{{ $t('ui.login.or') }}</p>

                    <loading-button
                        class="view-login__button"
                        icon="passkey"
                        color="primary"
                        :loading="passkey_login"
                        :disabled="logging_in"
                        @click.prevent="passkeyLogin"
                        v-if="showPasskey"
                    >
                        {{ $t('ui.login.passkey') }}
                    </loading-button>

                    <a :href="`https://to.contao.org/docs/manager-password?lang=${$i18n.locale}`" target="_blank" class="view-login__link">{{ $t('ui.login.forgotPassword') }}</a>
                </form>
            </main>
            <main class="view-login__form" v-else>
                <form @submit.prevent="login">
                    <h1 class="view-login__headline">{{ $t('ui.login.totpHeadline') }}</h1>
                    <p class="view-login__description">{{ $t('ui.login.totpDescription') }}</p>

                    <text-field
                        name="totp"
                        required
                        minlength="6"
                        maxlength="6"
                        autocomplete="one-time-code"
                        :label="$t('ui.login.totp')"
                        :placeholder="$t('ui.login.totp')"
                        class="view-login__totp"
                        :class="login_failed ? 'widget--error' : ''"
                        :disabled="logging_in"
                        v-model="totp"
                        @input="reset"
                    />

                    <loading-button submit class="view-login__button" color="primary" :disabled="!totpValid || login_failed" :loading="logging_in">
                        {{ $t('ui.login.button') }}
                    </loading-button>

                    <button class="widget-button view-login__button" @click="cancelTotp">{{ $t('ui.login.cancel') }}</button>
                </form>
            </main>
        </transition>
    </boxed-layout>
</template>

<script>
import { mapState } from 'vuex';
import { startAuthentication, browserSupportsWebAuthn } from '@simplewebauthn/browser';
import views from '../../router/views';

import BoxedLayout from '../layouts/BoxedLayout';
import TextField from '../widgets/TextField';
import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

export default {
    components: { BoxedLayout, TextField, LoadingButton },

    data: () => ({
        username: '',
        password: '',
        totp: '',

        logging_in: false,
        passkey_login: false,
        requires_totp: false,
        login_failed: false,
        showPasskey: false,
    }),

    computed: {
        ...mapState(['locked']),

        inputValid() {
            return this.username !== '' && this.password !== '' && this.password.length >= 8;
        },

        totpValid() {
            return this.totp !== '' && /^\d{6}$/.test(this.totp);
        },
    },

    methods: {
        async login() {
            if (!this.inputValid) {
                return;
            }

            this.doLogin({
                username: this.username,
                password: this.password,
                totp: this.totp,
            });
        },

        async passkeyLogin({ useBrowserAutofill }) {
            this.passkey_login = !useBrowserAutofill;

            const optionsJSON = (await this.$request.get('api/session/options')).data;

            try {
                const resp = await startAuthentication({ optionsJSON, useBrowserAutofill: !!useBrowserAutofill });

                await this.doLogin({
                    passkey: JSON.stringify(resp),
                });
            } catch (err) {
                // Ignore Webauthn error
            }

            this.passkey_login = false;
        },

        async doLogin(data) {
            this.logging_in = true;

            const response = await this.$store.dispatch('auth/login', data);

            if (response.status === 201) {
                this.$store.commit('setView', views.BOOT);
            } else if (response.status === 401 && response.data.totp_enabled) {
                this.logging_in = false;
                this.requires_totp = true;
                this.login_failed = !!this.totp;
            } else {
                this.logging_in = false;
                this.login_failed = true;
            }
        },

        reset() {
            this.login_failed = false;
        },

        cancelTotp() {
            this.username = '';
            this.password = '';
            this.totp = '';
            this.logging_in = false;
            this.requires_totp = false;
            this.login_failed = false;
        },
    },

    async mounted() {
        // Hide error screen when redirecting to login (timeout or 401 error).
        this.$store.commit('setError', null);

        if (this.locked) {
            return;
        }

        if (this.$refs.username) {
            this.$refs.username.focus();
        }

        if (location.protocol !== 'https:' && process.env.NODE_ENV !== 'development') {
            this.showPasskey = false;
            return;
        }

        this.showPasskey = browserSupportsWebAuthn();
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use '~contao-package-list/src/assets/styles/defaults';

.view-login {
    &__header {
        max-width: 280px;
        margin: 0 auto 60px;
        padding-top: 40px;
        text-align: center;
    }

    &__product {
        margin-top: 15px;
        font-size: 38px;
        font-weight: defaults.$font-weight-light;
        line-height: 1;
    }

    &__form {
        position: relative;
        max-width: 280px;
        margin: 0 auto 60px;

        input {
            padding-right: 30px;
            margin: 5px 0 10px;
        }
    }

    &__locked {
        max-width: 290px;
        margin: -20px auto 60px;
        padding: 20px;
        background: var(--btn-alert);
        color: #fff;
        text-align: center;

        // prevent line breaks in lock file path/name
        strong {
            white-space: pre;
        }
    }

    &__headline {
        margin-bottom: 0;
    }

    &__description {
        margin-top: 0.5em;
        margin-bottom: 20px;
    }

    label {
        position: absolute;
        text-indent: -999em;
        pointer-events: none;

        &[for='ctrl_username'] {
            top: 0;
            bottom: 0;
            margin: auto;
            right: 13px;
            width: 16px;
            height: 16px;
            background: url('../../assets/images/person.svg') left top no-repeat;
            background-size: 16px 16px;
            z-index: 10;
        }

        &[for='ctrl_password'] {
            top: 0;
            bottom: 0;
            margin: auto;
            right: 12px;
            width: 16px;
            height: 16px;
            background: url('../../assets/images/lock.svg') left top no-repeat;
            background-size: 14px 14px;
            z-index: 10;
        }
    }

    &__user,
    &__password {
        input {
            margin: 0;
        }
    }

    &__user {
        input {
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
        }
    }

    &__password {
        margin-top: -1px;
        margin-bottom: 10px;

        input {
            border-radius: 0 0 var(--border-radius) var(--border-radius) !important;
        }
    }

    .widget-text--password button {
        opacity: 0;
    }

    &__link {
        display: block;
        margin-top: 2em;
        font-size: 12px;
        text-align: center;
    }

    &__button {
        margin: 3px 0;

        .sk-circle {
            color: #fff;
            text-align: center;
        }
    }

    &__or {
        display: grid;
        gap: 15px;
        grid-template-columns: 1fr auto 1fr;
        padding: 12px;

        &::before,
        &::after {
            align-self: center;
            border-top: 1px solid var(--border);
            content: '';
        }
    }

    .fragment-footer {
        display: block;
    }
}
</style>
