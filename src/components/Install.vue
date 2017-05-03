<template>
    <boxed-layout wide="1" mainClass="install">
        <div slot="intro">
            <p v-html="$t('ui.install.intro')"></p>
        </div>

        <section slot="section">

            <fieldset v-if="!authUsername">
                <legend>{{ 'ui.install.accountHeadline' | translate }}</legend>
                <p>{{ 'ui.install.accountCreate' | translate }}</p>
                <text-field ref="username" name="username" :label="$t('ui.install.accountUsername')" class="inline" :disabled="installing" v-model="username" @enter="install"></text-field>
                <text-field type="password" name="password" :label="$t('ui.install.accountPassword')" placeholder="" :class="passwordClass" :disabled="installing" v-model="password" @enter="install"></text-field>
                <text-field type="password" name="password_confirm" label="Retype Password" :class="passwordClass" :disabled="installing" v-model="password_confirm" @enter="install"></text-field>
            </fieldset>

            <fieldset v-else>
                <legend>{{ 'ui.install.accountHeadline' | translate }}</legend>
                <p v-html="$t('ui.install.accountCreated', { username: authUsername })"></p>
            </fieldset>

            <fieldset v-if="!contaoVersion">
                <legend>{{ 'ui.install.contaoHeadline' | translate }}</legend>
                <p>{{ 'ui.install.contaoSelect' | translate }}</p>
                <select-menu name="version" :label="$t('ui.install.contaoVersion')" class="inline" v-bind="version" :options="versions"></select-menu>
            </fieldset>

            <fieldset v-else>
                <legend>{{ 'ui.install.contaoHeadline' | translate }}</legend>
                <p>{{ 'ui.install.contaoSelected' | translate({ version: contaoVersion }) }}</p>
            </fieldset>

            <fieldset v-if="showAdvanced">
                <legend>{{ 'ui.install.expertHeadline' | translate }}</legend>
                <p>{{ 'ui.install.expertDescription' | translate }}</p>
                <text-field name="github_oauth_token" :label="$t('ui.install.expertGithub')" :disabled="installing" v-model="github_oauth_token" @enter="install"></text-field>
                <text-field name="php_cli" :label="$t('ui.install.expertPhp')" :class="!php_cli ? 'invalid' : ''" :disabled="installing" v-model="php_cli" @enter="install"></text-field>
            </fieldset>

            <fieldset :class="{ submit: true, advanced: showAdvanced || installing }">
                <button class="primary install" @click="install" :disabled="!inputValid || installing">
                    <span v-if="!installing">{{ 'ui.install.buttonInstall' | translate }}</span>
                    <loader v-else></loader>
                </button>
                <a href="#" class="button advanced" v-if="!showAdvanced && !installing" @click.prevent="enableAdvanced">{{ 'ui.install.buttonExpert' | translate }}</a>
            </fieldset>

        </section>

    </boxed-layout>
</template>

<script>
    import store from '../store';
    import apiStatus from '../api/status';
    import routes from '../router/routes';

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
            version: '',
            versions: { '4.3.*': '4.3 (latest)' },

            php_cli: '',
            github_oauth_token: '',

            installing: false,
            advanced: false,
        }),

        computed: {
            showAdvanced() {
                return this.advanced || !this.php_cli;
            },

            inputValid() {
                if (!this.php_cli) {
                    return false;
                }

                if (this.authUsername) {
                    return true;
                }

                return !(this.username === '' || this.password === '' || this.password_confirm === '' || !this.passwordValid);
            },
            passwordClass() {
                return {
                    inline: true,
                    invalid: !this.passwordValid,
                };
            },
            passwordValid() {
                return this.password === ''
                    || this.password_confirm === ''
                    || (this.password === this.password_confirm
                        && this.password.length >= 8);
            },
            contaoVersion() {
                return this.$store.state.version;
            },
            authUsername() {
                return this.$store.state.auth.username;
            },
        },

        methods: {
            install() {
                if (!this.inputValid) {
                    return;
                }

                this.installing = true;

                new Promise((resolve) => {
                    if (this.authUsername) {
                        resolve();
                        return;
                    }

                    this.$store.dispatch(
                        'auth/login',
                        {
                            username: this.username,
                            password: this.password,
                        },
                    ).then(
                        () => {
                            resolve();
                        },
                    );
                }).then(() => {
                    const config = {
                        php_cli: this.php_cli,
                        php_can_fork: false,
                        php_force_background: false,
                    };

                    if (this.github_oauth_token) {
                        config.github_oauth_token = this.github_oauth_token;
                    }

                    return this.$store.dispatch('configure', config);
                }).then(() => {
                    if (this.contaoVersion) {
                        return this.$store.dispatch('fetchStatus', true);
                    }

                    return this.$store.dispatch('install', this.version);
                });
            },

            enableAdvanced() {
                if (this.installing) {
                    return;
                }

                this.advanced = true;
            },
        },

        beforeRouteEnter(to, from, next) {
            store.dispatch('fetchStatus').then((status) => {
                if (status === apiStatus.OK) {
                    next(routes.packages);
                }

                next();
            });
        },

        mounted() {
            if (this.$refs.username) {
                this.$refs.username.focus();
            }

            this.php_cli = this.$store.state.config.php_cli;
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
    @import "../assets/styles/defaults";

    .install-complete {
        text-align: center;
        padding: 100px 30px 0;

        p {
            margin-bottom: 50px;
        }

        .button {
            float: left !important;
            width: calc(50% - 20px) !important;
            margin: 0 10px;
        }
    }
</style>
