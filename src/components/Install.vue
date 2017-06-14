<template>
    <boxed-layout wide="1" mainClass="install">
        <div slot="header">
            <p class="headline">
                <strong>Contao Manager</strong>
                {{ 'ui.package.version' | translate({ version: '@package_version@' }) }}
            </p>
            <p v-html="$t('ui.install.intro')"></p>
        </div>

        <section slot="section">

            <fieldset v-if="!authUsername">
                <legend>{{ 'ui.install.accountHeadline' | translate }}</legend>
                <p>{{ 'ui.install.accountCreate' | translate }}</p>
                <text-field ref="username" name="username" :label="$t('ui.install.accountUsername')" class="inline" :disabled="installing" v-model="username" @enter="install"></text-field>
                <text-field type="password" name="password" :label="$t('ui.install.accountPassword')" :placeholder="$t('ui.install.accountPasswordPlaceholder')" :error="errors.password" :disabled="installing" v-model="password" @input="validatePassword" @enter="install"></text-field>
                <text-field type="password" name="password_confirm" :label="$t('ui.install.accountPasswordConfirm')" :disabled="installing" :error="errors.password_confirm" v-model="password_confirm" @input="validatePasswordConfirm" @enter="install"></text-field>
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

            <fieldset v-if="advanced">
                <legend>{{ 'ui.install.expertHeadline' | translate }}</legend>
                <p>{{ 'ui.install.expertDescription' | translate }}</p>
                <text-field ref="github_oauth_token" name="github_oauth_token" :label="$t('ui.install.expertGithub')" :placeholder="$t('ui.install.optional')" :disabled="installing" :error="errors.github_oauth_token" v-model="github_oauth_token" @enter="install"></text-field>
                <text-field ref="php_cli" name="php_cli" :label="$t('ui.install.expertPhp')" :disabled="installing" :error="errors.php_cli" v-model="php_cli" @input="validatePhpCli" @enter="install"></text-field>
            </fieldset>

            <fieldset :class="{ submit: true, advanced: advanced || installing }">
                <button class="primary install" @click="install" :disabled="!inputValid || installing">
                    <span v-if="!installing">{{ buttonLabel }}</span>
                    <loader v-else></loader>
                </button>
                <a href="#" class="button advanced" v-if="!advanced && !installing" @click.prevent="enableAdvanced">{{ 'ui.install.buttonExpert' | translate }}</a>
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
            versions: { '4.4.*': '4.4 (latest)' },

            php_cli: '',
            github_oauth_token: '',

            errors: {
                password: '',
                github_oauth_token: '',
                php_cli: '',
            },

            installing: false,
            advanced: false,
        }),

        computed: {

            inputValid() {
                if (!this.php_cli) {
                    return false;
                }

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

            buttonLabel() {
                if (!this.contaoVersion) {
                    return this.$t('ui.install.buttonInstall');
                }

                if (!this.authUsername) {
                    return this.$t('ui.install.buttonAccount');
                }

                return this.$t('ui.install.buttonConfigure');
            },

            contaoVersion() {
                return this.$store.state.version;
            },

            authUsername() {
                return this.$store.state.auth.username;
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

            validatePhpCli(value) {
                this.errors.php_cli = null;

                if (value.length === 0) {
                    this.errors.php_cli = this.$t('ui.install.phpMissing');
                }
            },

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
                        'auth/createAccount',
                        {
                            username: this.username,
                            password: this.password,
                        },
                    ).then(
                        () => {
                            // Refresh status to show the logged in user
                            this.$store.dispatch('fetchStatus', true).then(() => {
                                resolve();
                            });
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

                    return this.$store.dispatch('configure', config).then(
                        () => true,
                        (error) => {
                            this.errors[error.key] = error.message;
                            this.advanced = true;
                            this.installing = false;

                            this.$nextTick(() => {
                                if (this.$refs[error.key]) {
                                    this.$refs[error.key].focus();
                                }
                            });

                            return false;
                        },
                    );
                }).then((success) => {
                    if (!success) {
                        return false;
                    }

                    // Refresh status to run integrity check for the newly set php_cli
                    return this.$store.dispatch('fetchStatus', true).then(
                        () => {
                            if (this.contaoVersion) {
                                return true;
                            }

                            return this.$store.dispatch('install', this.version).then(() => true);
                        },
                    );
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
            store.dispatch('fetchStatus').then((result) => {
                if (result.status === apiStatus.OK) {
                    next(routes.packages);
                }

                if (result.task) {
                    store.dispatch('tasks/reload').then(
                        () => store.dispatch('fetchStatus', true),
                        () => {},
                    );
                }

                next();
            });
        },

        mounted() {
            if (this.$refs.username) {
                this.$refs.username.focus();
            }

            this.php_cli = this.$store.state.config.php_cli;

            if (!this.php_cli) {
                this.advanced = true;
            }
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
