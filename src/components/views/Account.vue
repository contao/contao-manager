<template>
    <boxed-layout :wide="true" slotClass="view-account">
        <header class="view-account__header">
            <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
            <p class="view-account__product">
                <strong>{{ 'ui.account.welcome' | translate }}</strong>
                Contao Manager @package_version@
            </p>
            <p class="view-account__intro" v-html="$t('ui.account.intro')"></p>
        </header>

        <main class="view-account__form">
            <h1 class="view-account__headline">{{ 'ui.account.headline' | translate }}</h1>
            <p class="view-account__description">{{ 'ui.account.description' | translate }}</p>

            <fieldset class="view-account__fields">
                <text-field ref="username" name="username" :label="$t('ui.account.username')" class="inline" :disabled="installing" v-model="username" @enter="createAccount"></text-field>
                <text-field type="password" name="password" :label="$t('ui.account.password')" :placeholder="$t('ui.account.passwordPlaceholder')" :error="errors.password" :disabled="installing" v-model="password" @input="validatePassword" @enter="createAccount"></text-field>
                <text-field type="password" name="password_confirm" :label="$t('ui.account.passwordConfirm')" :disabled="installing" :error="errors.password_confirm" v-model="password_confirm" @input="validatePasswordConfirm" @enter="createAccount"></text-field>
            </fieldset>

            <fieldset class="view-account__fields">
                <button class="widget-button widget-button--primary" @click="createAccount" :disabled="!inputValid || installing">
                    <span v-if="!installing">{{ 'ui.account.submit' | translate }}</span>
                    <loader v-else></loader>
                </button>
            </fieldset>
        </main>

        <div class="clearfix"></div>
        <aside class="view-account__contribute">
            <p v-html="$t('ui.account.contribute')"></p>
        </aside>
    </boxed-layout>
</template>

<script>
    import views from '../../router/views';

    import BoxedLayout from '../layouts/Boxed';
    import TextField from '../widgets/TextField';
    import SelectMenu from '../widgets/SelectMenu';
    import Checkbox from '../widgets/Checkbox';
    import Loader from '../fragments/Loader';

    export default {
        components: { BoxedLayout, TextField, SelectMenu, Checkbox, Loader },

        data: () => ({
            username: '',
            password: '',
            password_confirm: '',

            errors: {
                password: '',
            },

            installing: false,
        }),

        computed: {
            inputValid() {
                return !(this.username === '' || this.password === '' || this.password_confirm === '' || !this.passwordValid);
            },

            passwordValid() {
                return this.password === ''
                    || this.password_confirm === ''
                    || (this.password === this.password_confirm
                        && this.password.length >= 8);
            },
        },

        methods: {
            validatePassword() {
                this.errors.password = null;
                this.errors.password_confirm = null;

                if (this.password === '' && this.password_confirm === '') {
                    return;
                }

                if (this.password === '') {
                    this.errors.password = this.$t('ui.widget.mandatory');
                } else if (this.password.length < 8) {
                    this.errors.password = this.$t('ui.account.passwortLength');
                } else if (this.password !== this.password_confirm) {
                    this.errors.password_confirm = this.$t('ui.account.passwortDifferent');
                }
            },

            validatePasswordConfirm() {
                this.errors.password = null;
                this.errors.password_confirm = null;

                if (this.password === '' && this.password_confirm === '') {
                    return;
                }

                if (this.password_confirm === '') {
                    this.errors.password_confirm = this.$t('ui.widget.mandatory');
                } else if (this.password_confirm.length < 8) {
                    this.errors.password_confirm = this.$t('ui.account.passwortLength');
                } else if (this.password !== this.password_confirm) {
                    this.errors.password_confirm = this.$t('ui.account.passwortDifferent');
                }
            },

            createAccount() {
                if (!this.inputValid) {
                    return;
                }

                this.installing = true;

                this.$store.dispatch(
                    'auth/login',
                    {
                        username: this.username,
                        password: this.password,
                    },
                ).then(
                    (success) => {
                        if (success) {
                            this.$store.commit('setView', views.BOOT);
                        } else {
                            this.$store.dispatch('apiError');
                        }
                    },
                );
            },
        },

        mounted() {
            if (this.$refs.username) {
                this.$refs.username.focus();
            }
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

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
            font-weight: $font-weight-bold;

            strong {
                display: block;
                margin-bottom: 10px;
                font-size: 54px;
                font-weight: $font-weight-light;
                line-height: 1;
            }
        }

        &__headline {
            font-size: 18px;
            font-weight: $font-weight-bold;
        }

        &__form {
            position: relative;
            max-width: 250px;
            margin: 0 auto;

            input,
            select {
                margin: 5px 0 10px;
            }
        }

        &__fields {
            margin-top: 2em;
        }

        &__contribute {
            max-width: 250px;
            margin: 80px auto 0;
            font-size: 12px;
            text-align: center;

            br {
                display: none;
            }
        }

        @include screen(960) {
            padding-top: 100px;

            &__header {
                float: left;
                width: 470px;
                max-width: none;
                padding: 0 60px;
            }

            &__form {
                float: left;
                width: 370px;
                max-width: none;
                margin: 20px 50px 0;

                .widget-text label {
                    float: left;
                    width: 120px;
                    padding-top: 15px;
                    font-weight: $font-weight-medium;
                }

                input[type=text],
                input[type=password],
                select {
                    width: 250px !important;
                }

                .widget-button {
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
