<template>
    <boxed-layout mainClass="login">
        <section slot="section">
            <h1>Sign In</h1>
            <p>Login to manage your installation.</p>

            <text-field ref="username" name="username" label="Username" placeholder="Username" :class="login_failed ? 'invalid' : ''" :disabled="logging_in" v-model="username" @enter="login" @input="reset"></text-field>
            <text-field type="password" name="password" label="Password" placeholder="Password" :class="login_failed ? 'invalid' : ''" :disabled="logging_in" v-model="password" @enter="login" @input="reset"></text-field>

            <a href="https://manager.contao.org/en/forgot-password.html" target="_blank">Forgot your password?</a>

            <button class="primary" @click="login" :disabled="!inputValid || logging_in || login_failed">
                <span v-if="!logging_in">Sign In</span>
                <loader v-else></loader>
            </button>
        </section>
    </boxed-layout>
</template>

<script>
    import store from '../store';
    import apiStatus from '../api/status';
    import routes from '../router/routes';

    import BoxedLayout from './layouts/Boxed';
    import TextField from './widgets/TextField';
    import Loader from './fragments/Loader';

    export default {
        components: { BoxedLayout, TextField, Loader },
        data: () => ({
            username: '',
            password: '',

            logging_in: false,
            login_failed: false,
        }),
        computed: {
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
                })
                .then(() => {
                    if (this.$store.state.auth.isLoggedIn) {
                        this.$router.push(routes.install);
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
        beforeRouteEnter(to, from, next) {
            store.dispatch('fetchStatus').then((status) => {
                if (status === apiStatus.OK) {
                    next(routes.packages);
                }

                next();
            });
        },
        mounted() {
            this.$refs.username.focus();
        },
    };
</script>
