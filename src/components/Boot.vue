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
    import views from '../router/views';

    import BoxedLayout from './layouts/Boxed';
    import TextField from './widgets/TextField';
    import SelectMenu from './widgets/SelectMenu';
    import Checkbox from './widgets/Checkbox';
    import Loader from './fragments/Loader';

    export default {
        components: { BoxedLayout, TextField, SelectMenu, Checkbox, Loader },

        data: () => ({
            version: '',
            versions: { '4.4.*': '4.4 (latest)' },

            php_cli: '',
            github_oauth_token: '',

            errors: {
                github_oauth_token: '',
                php_cli: '',
            },

            installing: false,
            advanced: false,
        }),

        computed: {

            inputValid() {
                return !!this.php_cli;
            },

            buttonLabel() {
                if (!this.contaoVersion) {
                    return this.$t('ui.install.buttonInstall');
                }

                return this.$t('ui.install.buttonConfigure');
            },

            contaoVersion() {
                return this.$store.state.version;
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

                const config = {
                    php_cli: this.php_cli,
                    php_can_fork: false,
                    php_force_background: false,
                };

                if (this.github_oauth_token) {
                    config.github_oauth_token = this.github_oauth_token;
                }

                this.$store.dispatch('configure', config).then(
                    // Refresh status to run integrity check for the newly set php_cli
                    () => this.$store.dispatch('fetchStatus', true).then(
                        () => {
                            if (this.contaoVersion) {
                                return true;
                            }

                            return this.$store.dispatch('install', this.version).then(() => true);
                        },
                    ),
                    (error) => {
                        this.errors[error.key] = error.message;
                        this.advanced = true;
                        this.installing = false;

                        this.$nextTick(() => {
                            if (this.$refs[error.key]) {
                                this.$refs[error.key].focus();
                            }
                        });
                    },
                );
            },

            enableAdvanced() {
                if (this.installing) {
                    return;
                }

                this.advanced = true;
            },
        },

        mounted() {
            const load = () => {
                console.log(this.$store.state.status);
                if (this.$store.state.status === apiStatus.OK) {
                    this.$store.commit('setView', views.READY);
                }

                this.php_cli = this.$store.state.config.php_cli;

                if (!this.php_cli) {
                    this.advanced = true;
                }
            };

            store.dispatch('fetchStatus').then((result) => {
                if (result.task) {
                    store.dispatch('tasks/reload').then(
                        () => store.dispatch('fetchStatus', true).then(load),
                        () => {},
                    );
                } else {
                    load();
                }
            });
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
