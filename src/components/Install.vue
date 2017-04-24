<template>
    <boxed-layout wide="1" mainClass="install">
        <div slot="intro">
            <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem.</p>

            <div :class="{ selftest: true, warnings: selftestWarnings > 0 }" v-if="selftestWarnings !== null">
                <router-link :to="{ name: 'install-check' }">Details</router-link>
                <img src="assets/images/selftest.svg" width="24" height="24">
                <p v-if="selftestWarnings > 0"><strong>Self Test:</strong> {{ selftestWarnings }} warning(s)</p>
                <p v-else><strong>Self Test:</strong> all good!</p>
            </div>
        </div>

        <section class="install-complete" slot="section" v-if="installComplete">
            <h1>Congratulations!</h1>
            <p>Contao has been successfully installed. To complete the setup process, you must open the Install Tool and enter your database credentials.</p>
            <fieldset>
                <a class="button primary" href="/install.php" target="_blank">Install Tool</a>
                <router-link class="button" :to="{ name: 'packages' }">Packages</router-link>
            </fieldset>
        </section>

        <section slot="section" v-else>

            <fieldset v-if="!isLoggedIn">
                <legend>User Account</legend>
                <p>Create a user account to manage your installation.</p>
                <text-field ref="username" name="username" label="Username" class="inline" :disabled="installing" v-model="username" @enter="install"></text-field>
                <text-field type="password" name="password" label="Password" placeholder="min. 8 characters" :class="passwordClass" :disabled="installing" v-model="password" @enter="install"></text-field>
                <text-field type="password" name="password_confirm" label="Retype Password" :class="passwordClass" :disabled="installing" v-model="password_confirm" @enter="install"></text-field>
            </fieldset>

            <fieldset v-else>
                <legend>User Account</legend>
                <p>You are logged in as {{ authUsername }}.</p>
            </fieldset>

            <fieldset>
                <legend>Contao Installation</legend>
                <p>Select the Contao version to install.</p>
                <select-menu name="version" label="Version" class="inline" v-bind="version" :options="versions"></select-menu>
            </fieldset>

            <fieldset v-if="advanced">
                <legend>Expert Settings</legend>
                <p>Configure the Contao Manager to run on your webserver.</p>
                <text-field name="github_oauth_token" label="GitHub Token" :disabled="installing" v-model="github_oauth_token" @enter="install"></text-field>
                <text-field name="php_cli" label="PHP binary" :disabled="installing" v-model="php_cli" @enter="install"></text-field>
                <text-field name="php_cli_arguments" label="CLI arguments" :disabled="installing" v-model="php_cli_arguments" @enter="install"></text-field>
            </fieldset>

            <fieldset :class="{ submit: true, advanced: advanced || installing }">
                <button class="primary install" @click="install" :disabled="!inputValid || installing">
                    <span v-if="!installing">Install</span>
                    <loader v-else></loader>
                </button>
                <a href="#" class="button" v-if="!advanced && !installing" @click.prevent="enableAdvanced">Advanced</a>
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
            php_cli_arguments: '',
            github_oauth_token: '',

            installing: false,
            installComplete: false,
            advanced: false,
        }),
        computed: {
            inputValid() {
                if (this.isLoggedIn) {
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
            isLoggedIn() {
                return this.$store.state.auth.isLoggedIn;
            },
            authUsername() {
                return this.$store.state.auth.username;
            },
            selftestWarnings() {
                if (!Array.isArray(this.$store.state.selftest)) {
                    return null;
                }

                let warnings = 0;

                this.$store.state.selftest.forEach((result) => {
                    if (result.state === 'WARNING') {
                        warnings += 1;
                    }
                });

                return warnings;
            },
        },
        methods: {
            install() {
                if (!this.inputValid) {
                    return;
                }

                this.installing = true;

                new Promise((resolve) => {
                    if (this.isLoggedIn) {
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

                    if (this.php_cli_arguments) {
                        config.php_cli_arguments = this.php_cli_arguments;
                    }

                    if (this.github_oauth_token) {
                        config.github_oauth_token = this.github_oauth_token;
                    }

                    return this.$store.dispatch('configure', config);
                }).then(() => {
                    this.$store.dispatch('install', this.version).then(
                        () => {
                            this.installComplete = true;
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

            this.php_cli = this.$store.state.autoconfig.php_cli;
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
