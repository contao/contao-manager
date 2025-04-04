<template>
    <boxed-layout :wide="true" slotClass="view-account">
        <header class="view-account__header">
            <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
            <p class="view-account__product">
                <strong>{{ $t('ui.account.welcome') }}</strong>
                Contao Manager @manager_version@
            </p>
            <p>
                <i18n-t keypath="ui.account.intro1">
                    <template #readTheManualToGetStarted>
                        <i18n-t tag="strong" keypath="ui.account.introGetStarted">
                            <template #readTheManual><a :href="`https://to.contao.org/docs/contao-manager?lang=${$i18n.locale}`" target="_blank">{{ $t('ui.account.introManual') }}</a></template>
                        </i18n-t>
                    </template>
                </i18n-t>
                <br /><br />
                <i18n-t keypath="ui.account.intro2">
                    <template #ourGithubIssues><a href="https://github.com/contao/contao-manager/issues" target="_blank">{{ $t('ui.account.introIssues') }}</a></template>
                </i18n-t>
            </p>
        </header>

        <transition name="animate-flip" type="transition" mode="out-in">
            <main class="view-account__totp" v-if="currentUser && !hasTotp">
                <h1 class="view-account__headline">{{ $t('ui.account.totpHeadline') }}</h1>
                <p class="view-account__description">{{ $t('ui.account.totpDescription') }}</p>

                <button class="widget-button widget-button--primary" @click="setupTotp">{{ $t('ui.totp.setup') }}</button>
                <button class="widget-button widget-button--alert" @click="skipTotp">{{ $t('ui.totp.skip') }}</button>
            </main>

            <main class="view-account__form" v-else>
                <form @submit.prevent="createAccount">
                    <h1 class="view-account__headline">{{ $t('ui.account.headline') }}</h1>
                    <p class="view-account__description">{{ $t('ui.account.description') }}</p>

                    <fieldset class="view-account__fields">
                        <text-field
                            ref="username" name="username"
                            :label="$t('ui.account.username')"
                            :disabled="logging_in"
                            required
                            :error="errors.username" @blur="errors.username = ''"
                            v-model="username"
                        />
                        <text-field
                            ref="password" name="password" type="password"
                            :label="$t('ui.account.password')" :placeholder="$t('ui.account.passwordPlaceholder')"
                            :disabled="logging_in"
                            required pattern=".{8,}"
                            :error="errors.password" @blur="validatePassword"
                            v-model="password"
                            v-if="usePassword"
                        />

                        <button-group submit color="primary" :disabled="!valid" :loading="logging_in" :label="$t('ui.account.submit')" v-if="supportsWebAuthn">
                            <button type="button" class="widget-button" :disabled="logging_in" @click="usePassword = !usePassword">{{ $t(`ui.account.${usePassword ? 'usePasskey' : 'usePassword'}`) }}</button>
                        </button-group>
                        <loading-button submit color="primary" :disabled="!valid" :loading="logging_in" v-else>{{ $t('ui.account.submit') }}</loading-button>
                        <button type="button" class="widget-button widget-button--anchor" @click="gotoLogin" v-if="isInvitation">{{ $t('ui.account.login') }}</button>
                    </fieldset>
                </form>
            </main>
        </transition>

        <aside class="view-account__contribute">
            <p>
                {{ $t('ui.account.contribute1') }}<br />
                <i18n-t keypath="ui.account.contribute2">
                    <template #donate><a href="https://to.contao.org/donate" target="_blank">{{ $t('ui.account.contributeDonate') }}</a></template>
                </i18n-t>
            </p>
        </aside>
    </boxed-layout>
</template>

<script>
import { mapState } from 'vuex';
import { startRegistration, browserSupportsWebAuthn } from '@simplewebauthn/browser';
import views from '../../router/views';
import BoxedLayout from '../layouts/BoxedLayout';
import TextField from '../widgets/TextField';
import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
import ButtonGroup from '@/components/widgets/ButtonGroup.vue';
import SetupTotp from '../routes/Users/SetupTotp.vue';

export default {
    components: { BoxedLayout, TextField, LoadingButton, ButtonGroup },

    data: () => ({
        username: '',
        password: '',

        errors: {
            username: '',
            password: '',
        },

        valid: false,
        logging_in: false,
        usePassword: false,
        supportsWebAuthn: true,
    }),

    computed: {
        ...mapState('auth', { currentUser: 'username', hasTotp: 'totpEnabled' }),
        isInvitation: (vm) => !!vm.$route.query.invitation,
    },

    methods: {
        validate() {
            this.valid = this.$refs.username.checkValidity()
                && (!this.usePassword || this.$refs.password.checkValidity());
        },

        validatePassword() {
            this.errors.password = null;

            if (this.password === '') {
                return;
            }

            if (this.password.length < 8) {
                this.errors.password = this.$t('ui.account.passwordLength');
            }
        },

        async createAccount() {
            if (!this.valid) {
                return;
            }

            this.logging_in = true;

            const data = {
                username: this.username,
            };

            if (this.isInvitation) {
                data.invitation = this.$route.query.invitation;
            }

            if (this.usePassword) {
                data.password = this.password;
            } else {
                const optionsJSON = (await this.$request.post('api/session/options', data)).data;
                let passkey;

                try {
                    passkey = await startRegistration({ optionsJSON });
                } catch (error) {
                    this.logging_in = false;
                    return;
                }

                if (!passkey) {
                    this.logging_in = false;
                    return;
                }

                data.passkey = JSON.stringify(passkey);
            }

            if ((await this.$store.dispatch('auth/login', data)).status !== 201) {
                this.logging_in = false;
                this.errors.username = this.$t('ui.account.loginInvalid');
                setTimeout(() => {
                    this.$refs.username.focus();
                }, 0);
            }
        },

        setupTotp() {
            this.$store.commit('modals/open', { id: 'setup-totp', component: SetupTotp });
        },

        skipTotp() {
            this.$store.commit('setView', views.BOOT);
        },

        gotoLogin() {
            this.$router.replace({ name: this.$route.name, query: null });
            this.$store.commit('setView', views.LOGIN);
        },
    },

    watch: {
        username() {
            this.validate();
        },

        password() {
            this.validate();
        },

        hasTotp() {
            this.$store.commit('setView', views.BOOT);
        },
    },

    mounted() {
        this.supportsWebAuthn = (location.protocol === 'https:' || process.env.NODE_ENV === 'development') && browserSupportsWebAuthn();
        this.usePassword = !this.supportsWebAuthn;

        if (this.$refs.username) {
            this.$refs.username.focus();
        }
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.view-account {
    &__header {
        max-width: 280px;
        margin-left: auto;
        margin-right: auto;
        padding: 40px 0;
        text-align: center;
    }

    &__product {
        margin-top: 15px;
        margin-bottom: 40px;
        font-weight: defaults.$font-weight-bold;

        strong {
            display: block;
            margin-bottom: 10px;
            font-size: 54px;
            font-weight: defaults.$font-weight-light;
            line-height: 1;
        }
    }

    &__headline {
        margin-bottom: 0.5em;
        font-size: 18px;
        font-weight: defaults.$font-weight-bold;
        line-height: 30px;
    }

    &__description {
        margin-bottom: 1em;
        text-align: justify;
    }

    &__form,
    &__totp {
        position: relative;
        max-width: 280px;
        margin: 0 auto;

        .widget-button {
            margin-top: 1.5em;
        }
    }

    &__form {
        .widget-text {
            margin-top: 10px;

            label {
                display: block;
                padding-bottom: 5px;
            }
        }
    }

    &__contribute {
        max-width: 280px;
        margin: 60px auto 0;
        font-size: 12px;
        text-align: center;

        br {
            display: none;
        }
    }

    @include defaults.screen(960) {
        display: flex;
        flex-flow: row wrap;
        align-items: center;
        padding-top: 50px;

        &__header,
        &__form,
        &__totp {
            padding: 50px;
            width: 50%;
            max-width: none;
        }

        &__form {
            .widget-text label {
                float: left;
                width: 120px;
                padding-top: 10px;
                font-weight: defaults.$font-weight-medium;
            }

            input[type=text],
            input[type=password],
            select {
                width: 250px !important;
            }

            .button-group,
            .widget-button--anchor {
                width: 250px;
                margin-left: 120px;
            }
        }

        &__contribute {
            max-width: 840px;

            br {
                display: block;
            }
        }
    }
}
</style>
