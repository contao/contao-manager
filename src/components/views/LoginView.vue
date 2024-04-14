<template>
    <boxed-layout slotClass="view-login">
        <header class="view-login__header">
            <img src="../../assets/images/logo.svg" width="80" height="80" alt="Contao Logo" />
            <p class="view-login__product">Contao Manager</p>
        </header>
        <main class="view-login__locked" v-if="locked">
            <i18n tag="p" path="ui.login.locked">
                <template #lockFile><strong>contao-manager/login.lock</strong><br></template>
            </i18n>
        </main>
        <main class="view-login__form" v-else>
            <form @submit.prevent="login">
                <h1 class="view-login__headline">{{ $t('ui.login.headline') }}</h1>
                <p class="view-login__description">{{ $t('ui.login.description') }}</p>

                <text-field ref="username" name="username" :label="$t('ui.login.username')" :placeholder="$t('ui.login.username')" class="view-login__user" :class="login_failed ? 'widget--error' : ''" :disabled="logging_in" v-model="username" @input="reset"/>
                <text-field type="password" name="password" :label="$t('ui.login.password')" :placeholder="$t('ui.login.password')" class="view-login__password" :class="login_failed ? 'widget--error' : ''" :disabled="logging_in" v-model="password" @input="reset"/>

                <loading-button submit class="view-login__button" color="primary" :disabled="!inputValid || login_failed" :loading="logging_in">
                    {{ $t('ui.login.button') }}
                </loading-button>

                <a :href="`https://to.contao.org/docs/manager-password?lang=${$i18n.locale}`" target="_blank" class="view-login__link">{{ $t('ui.login.forgotPassword') }}</a>
            </form>
        </main>
    </boxed-layout>
</template>

<script>
    import { mapState } from 'vuex';
    import views from '../../router/views';

    import BoxedLayout from '../layouts/BoxedLayout';
    import TextField from '../widgets/TextField';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { BoxedLayout, TextField, LoadingButton },

        data: () => ({
            username: '',
            password: '',

            logging_in: false,
            login_failed: false,
        }),

        computed: {
            ...mapState(['locked']),

            inputValid() {
                return this.username !== '' && this.password !== '' && this.password.length >= 8;
            },
        },

        methods: {
            login() {
                if (!this.inputValid) {
                    return;
                }

                this.logging_in = true;

                this.$store.dispatch('auth/login', {
                    username: this.username,
                    password: this.password,
                }).then((success) => {
                    if (success) {
                        this.$store.commit('setView', views.BOOT);
                    } else {
                        this.logging_in = false;
                        this.login_failed = true;
                    }
                });
            },
            reset() {
                this.login_failed = false;
            },
        },

        mounted() {
            // Hide error screen when redirecting to login (timeout or 401 error).
            this.$store.commit('setError', null);

            if (this.$refs.username) {
                this.$refs.username.focus();
            }
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

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
            font-weight: $font-weight-light;
            line-height: 1;
        }

        &__form {
            position: relative;
            max-width: 280px;
            margin: 0 auto 80px;

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
            margin-top: .5em;
            margin-bottom: 30px;
        }

        label {
            position: absolute;
            text-indent: -999em;

            &[for=ctrl_username] {
                top: 0;
                bottom: 0;
                margin: auto;
                right: 13px;
                width: 16px;
                height: 16px;
                background: url("../../assets/images/person.svg") left top no-repeat;
                background-size: 16px 16px;
                z-index: 10;
            }

            &[for=ctrl_password] {
                top: 0;
                bottom: 0;
                margin: auto;
                right: 12px;
                width: 16px;
                height: 16px;
                background: url("../../assets/images/lock.svg") left top no-repeat;
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

            input {
                border-radius: 0 0 var(--border-radius) var(--border-radius) !important;
            }
        }

        .widget-text--password button {
            display: none;
        }

        &__link {
            display: block;
            font-size: 12px;
            text-align: right;
        }

        &__button {
            margin: 12px 0 6px;

            .sk-circle {
                color: #fff;
                text-align: center;
            }
        }

        .fragment-footer {
            display: block;
        }
    }
</style>
