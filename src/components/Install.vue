<template>
    <boxed-layout wide="1" mainClass="install">
        <p slot="intro">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem.</p>

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
                <text-field type="password" name="password" label="Password" :class="passwordClass" :disabled="installing" v-model="password" @enter="install"></text-field>
                <text-field type="password" name="password_confirm" label="Retype Password" :class="passwordClass" :disabled="installing" v-model="password_confirm" @enter="install"></text-field>
            </fieldset>

            <fieldset v-else>
                <legend>User Account</legend>
                <p>A user account has already been configured. You are logged in as {{ authUsername }}.</p>
            </fieldset>

            <fieldset>
                <legend>Contao Installation</legend>
                <p>Select the Contao version to install.</p>
                <select-menu name="version" label="Version" class="inline" v-bind="version" :options="versions"></select-menu>
            </fieldset>

            <fieldset v-if="advanced">
                <legend>Expert Settings</legend>
                <p>Configure the Contao Manager to run on your webserver.</p>
                <text-field name="php_cli" label="PHP binary" :disabled="installing" v-model="php_cli" @enter="install"></text-field>
                <checkbox name="php_can_fork" label="PHP can fork" :disabled="installing" v-model="php_can_fork"></checkbox>
                <checkbox name="php_force_background" label="Force PHP to background" :disabled="installing" v-model="php_force_background"></checkbox>
            </fieldset>

            <fieldset class="submit">
                <button class="primary install" @click="install" :disabled="!inputValid || installing">
                    <span v-if="!installing">Install</span>
                    <loader v-else></loader>
                </button>
                <a href="#" :class="{ button: true, advanced: true, disabled: advanced }" @click.prevent="enableAdvanced">Advanced</a>
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
            php_can_fork: false,
            php_force_background: false,

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
        },
        methods: {
            install() {
                if (!this.inputValid) {
                    return;
                }

                this.installing = true;

                if (this.isLoggedIn) {
                    this.$store.dispatch('install', this.version).then(
                        () => {
                            this.$router.push(routes.packages);
                        },
                    );
                } else {
                    this.$store
                        .dispatch('configure', {
                            username: this.username,
                            password: this.password,
                            config: {
                                php_cli: this.php_cli,
                                php_can_fork: this.php_can_fork,
                                php_force_background: this.php_force_background,
                            },
                        }).then(() => {
                            this.$store.dispatch('install', this.version).then(
                                () => {
                                    this.installComplete = true;
                                },
                            );
                        });
                }
            },

            enableAdvanced() {
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
            this.php_can_fork = this.$store.state.autoconfig.php_can_fork;
            this.php_force_background = this.$store.state.autoconfig.php_force_background;
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
