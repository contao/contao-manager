<template>
    <boxed-layout wide="1" mainClass="install">
        <header slot="header">
            <img src="../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
            <p class="headline">
                <strong>Contao Manager</strong>
                {{ 'ui.package.version' | translate({ version: '@package_version@' }) }}
            </p>
            <p v-html="$t('ui.install.intro')"></p>
        </header>

        <section slot="section">

            <fieldset>
                <legend>{{ 'ui.install.accountHeadline' | translate }}</legend>
                <p>{{ 'ui.install.accountCreate' | translate }}</p>
                <text-field ref="username" name="username" :label="$t('ui.install.accountUsername')" class="inline" :disabled="installing" v-model="username" @enter="createAccount"></text-field>
                <text-field type="password" name="password" :label="$t('ui.install.accountPassword')" :placeholder="$t('ui.install.accountPasswordPlaceholder')" :error="errors.password" :disabled="installing" v-model="password" @input="validatePassword" @enter="createAccount"></text-field>
                <text-field type="password" name="password_confirm" :label="$t('ui.install.accountPasswordConfirm')" :disabled="installing" :error="errors.password_confirm" v-model="password_confirm" @input="validatePasswordConfirm" @enter="createAccount"></text-field>
            </fieldset>

            <fieldset class="submit advanced">
                <button class="primary install" @click="createAccount" :disabled="!inputValid || installing">
                    <span v-if="!installing">{{ 'ui.install.buttonAccount' | translate }}</span>
                    <loader v-else></loader>
                </button>
            </fieldset>

        </section>

    </boxed-layout>
</template>

<script>
    import BoxedLayout from './layouts/Boxed';
    import TextField from './widgets/TextField';
    import SelectMenu from './widgets/SelectMenu';
    import Checkbox from './widgets/Checkbox';
    import Loader from './fragments/Loader';

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
                if (this.authUsername) {
                    return true;
                }

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
                    this.errors.password = this.$t('ui.install.accountPasswortLength');
                } else if (this.password !== this.password_confirm) {
                    this.errors.password_confirm = this.$t('ui.install.accountPasswortDifferent');
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
                    this.errors.password_confirm = this.$t('ui.install.accountPasswortLength');
                } else if (this.password !== this.password_confirm) {
                    this.errors.password_confirm = this.$t('ui.install.accountPasswortDifferent');
                }
            },

            createAccount() {
                if (!this.inputValid) {
                    return;
                }

                this.installing = true;

                this.$store.dispatch(
                    'auth/createAccount',
                    {
                        username: this.username,
                        password: this.password,
                    },
                ).then(
                    () => this.$store.dispatch('auth/status'),
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
