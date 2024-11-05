<template>
    <section>

        <header class="setup__header">
            <img src="../../assets/images/user.svg" width="80" height="80" class="setup__icon" alt="">
            <h1 class="setup__headline">{{ $t('ui.setup.backend-user.headline') }}</h1>
            <p class="setup__description">{{ $t('ui.setup.backend-user.description') }}</p>
        </header>

            <main class="setup__form" v-if="hasUser === null">
                <div class="setup__fields">
                    <p class="setup__warning">{{ $t('ui.setup.backend-user.error') }}</p>
                    <console-output
                        class="view-recovery__console"
                        :title="$t('ui.recovery.console')"
                        :operations="[{ status: 'error', summary: 'vendor/bin/contao-console contao:user:list', console: response.data.detail }]"
                        :console-output="response.data.detail"
                        show-console force-console
                        v-if="response.status === 502"
                    />
                </div>
            </main>

        <transition name="animate-flip" type="transition" mode="out-in" v-else>

            <main class="setup__form setup__form--center" v-if="hasUser" v-bind:key="'confirmation'">
                <div class="setup__fields">
                    <svg class="setup__check" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" /></svg>
                    <p class="setup__fielddesc">{{ $t('ui.setup.backend-user.success') }}</p>
                </div>
                <div class="setup__actions setup__actions--center">
                    <button type="button" class="widget-button widget-button--inline widget-button--primary" @click="$emit('continue')">{{ $t('ui.setup.continue') }}</button>
                </div>
            </main>

            <main class="setup__form" v-else v-bind:key="'setup'">
                <form @submit.prevent="save">
                    <div class="setup__fields">
                        <h2 class="setup__fieldtitle">{{ $t('ui.setup.backend-user.formTitle') }}</h2>
                        <p class="setup__fielddesc">{{ $t('ui.setup.backend-user.formText') }}</p>

                        <text-field ref="username" name="username" :label="$t('ui.setup.backend-user.username')" :disabled="processing" required validate v-model="username"/>
                        <text-field ref="name" name="name" :label="$t('ui.setup.backend-user.name')" :disabled="processing" required validate v-model="name"/>
                        <text-field
                            ref="email"
                            name="email"
                            type="email"
                            :label="$t('ui.setup.backend-user.email')"
                            :disabled="processing"
                            required validate
                            :error="errors.email" @focus="errors.email = ''" @blur="validateEmail"
                            v-model="email"
                        />
                        <text-field
                            ref="password" name="password" type="password"
                            :label="$t('ui.setup.backend-user.password')" :placeholder="$t('ui.setup.backend-user.passwordPlaceholder')"
                            :disabled="processing"
                            required minlength="8" validate
                            :error="errors.password" @focus="errors.password = ''" @blur="validatePassword"
                            v-model="password"
                        />
                    </div>
                    <div class="setup__actions setup__actions--center">
                        <loading-button submit color="primary" :loading="processing" :disabled="!valid">{{ $t('ui.setup.backend-user.create') }}</loading-button>
                    </div>
                </form>
            </main>
        </transition>
    </section>
</template>

<script>
    import TextField from '../widgets/TextField';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
    import ConsoleOutput from '../fragments/ConsoleOutput';
    import { mapState } from 'vuex';

    export default {
        components: { TextField, LoadingButton, ConsoleOutput },

        data: () => ({
            processing: false,
            valid: false,

            username: '',
            name: '',
            email: '',
            password: '',

            errors: {
                email: '',
                password: '',
            }
        }),

        computed: {
            ...mapState('server/adminUser', { hasUser: 'hasUser', response: 'cache' }),
        },

        methods: {
            validate() {
                this.valid = this.$refs.username.checkValidity()
                    && this.$refs.name.checkValidity()
                    && this.$refs.email.checkValidity()
                    && this.$refs.password.checkValidity();
            },

            validateEmail() {
                this.errors.email = null;

                if (this.email === '') {
                    return;
                }

                if (!this.$refs.email.checkValidity()) {
                    this.errors.email = this.$t('ui.setup.backend-user.emailInvalid');
                }
            },

            validatePassword() {
                this.errors.password = null;

                if (this.password === '') {
                    return;
                }

                if (this.password.length < 8) {
                    this.errors.password = this.$t('ui.setup.backend-user.passwordLength');
                }
            },

            async save() {
                this.processing = true;

                await this.$store.dispatch('server/adminUser/set', {
                    username: this.username,
                    name: this.name,
                    email: this.email,
                    password: this.password
                });

                this.processing = false;

                this.$store.commit('setup', 5);
            }
        },

        watch: {
            username() {
                this.validate();
            },
            name() {
                this.validate();
            },
            email() {
                this.validate();
            },
            password() {
                this.validate();
            }
        },
    };
</script>
