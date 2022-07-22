<template>
    <boxed-layout v-if="current" :wide="true" slotClass="admin-user-check">

        <header class="admin-user-check__header">
            <img src="../../assets/images/user.svg" width="80" height="80" class="admin-user-check__icon" alt="">
            <h1 class="admin-user-check__headline">{{ $t('ui.server.adminUser.headline') }}</h1>
            <p class="admin-user-check__description">{{ $t('ui.server.adminUser.description') }}</p>
        </header>

        <main class="admin-user-check__form">
            <form @submit.prevent="save">
                <div class="admin-user-check__fields">
                    <h2 class="admin-user-check__fieldtitle">{{ $t('ui.server.adminUser.formTitle') }}</h2>
                    <p class="admin-user-check__fielddesc">{{ $t('ui.server.adminUser.formText') }}</p>

                    <text-field ref="username" name="username" :label="$t('ui.server.adminUser.username')" :disabled="processing" required validate v-model="username"/>
                    <text-field ref="name" name="name" :label="$t('ui.server.adminUser.name')" :disabled="processing" required validate v-model="name"/>
                    <text-field
                        ref="email"
                        name="email"
                        type="email"
                        :label="$t('ui.server.adminUser.email')"
                        :disabled="processing"
                        required validate
                        :error="errors.email" @focus="errors.email = ''" @blur="validateEmail"
                        v-model="email"
                    />
                    <text-field
                        ref="password" name="password" type="password"
                        :label="$t('ui.server.adminUser.password')" :placeholder="$t('ui.server.adminUser.passwordPlaceholder')"
                        :disabled="processing"
                        required pattern=".{8,}" validate
                        :error="errors.password" @focus="errors.password = ''" @blur="validatePassword"
                        v-model="password"
                    />
                </div>

                <loading-button submit color="primary" :loading="processing" :disabled="!valid">{{ $t('ui.server.adminUser.create') }}</loading-button>
                <button type="button" class="widget-button" :disabled="processing" @click="cancel">{{ $t('ui.server.adminUser.cancel') }}</button>
            </form>
        </main>

    </boxed-layout>

    <boot-check v-else :progress="bootState" :title="$t('ui.server.adminUser.title')" :description="bootDescription">
        <button type="button" v-if="bootState === 'warning'" @click="showAdminUser" class="widget-button widget-button--warning">{{ $t('ui.server.adminUser.create') }}</button>
    </boot-check>
</template>

<script>
    import boot from '../../mixins/boot';

    import BootCheck from '../fragments/BootCheck';
    import BoxedLayout from '../layouts/Boxed';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
    import TextField from '../widgets/TextField';

    export default {
        mixins: [boot],
        components: { BootCheck, BoxedLayout, TextField, LoadingButton },

        data: () => ({
            processing: false,
            valid: false,

            hasUser: false,
            username: '',
            name: '',
            email: '',
            password: '',

            errors: {
                email: '',
                password: '',
            },
            error: null,
        }),

        methods: {
            async boot() {
                this.bootState = 'loading';
                this.bootDescription = this.$t('ui.server.running');
                this.$store.commit('setSafeMode', false);

                const response = await this.$store.dispatch('server/adminUser/get', false);
                const result = response.body;

                if (response.status === 200) {
                    this.hasUser = !!result.hasUser;

                    if (this.hasUser) {
                        this.bootState = 'success';
                        this.bootDescription = this.$t('ui.server.adminUser.success');
                    } else {
                        this.bootState = 'warning';
                        this.bootDescription = this.$t('ui.server.adminUser.warning');
                    }
                } else if (response.status === 501) {
                    this.bootState = 'info';
                    this.bootDescription = this.$t('ui.server.adminUser.unsupported');
                } else if (response.status === 502) {
                    this.bootState = 'info';
                    this.bootDescription = this.$t('ui.server.adminUser.unavailable');
                } else if (response.status === 503) {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.prerequisite');
                } else {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.error');
                }

                this.$emit('result', 'AdminUser', this.bootState);
            },

            showAdminUser() {
                this.$emit('view', 'AdminUser');
            },

            validate() {
                this.error = null;

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
                    this.errors.email = this.$t('ui.server.adminUser.emailInvalid');
                }
            },

            validatePassword() {
                this.errors.password = null;

                if (this.password === '') {
                    return;
                }

                if (this.password.length < 8) {
                    this.errors.password = this.$t('ui.server.adminUser.passwordLength');
                }
            },

            async save() {
                this.processing = true;

                const response = await this.$store.dispatch('server/adminUser/set', {
                    username: this.username,
                    name: this.name,
                    email: this.email,
                    password: this.password
                });

                if (response.status === 502) {
                    this.processing = false;
                    this.valid = false;
                    this.error = response.body.detail || true;
                    return;
                }

                this.hasUser = true;
                this.processing = false;
                this.$emit('result', 'AdminUser', null);
                this.$emit('view', null);
                this.boot();
            },

            cancel() {
                this.username = '';
                this.name = '';
                this.email = '';
                this.password = '';

                this.$emit('view', null);
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
        }
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .admin-user-check {
        &__header {
            max-width: 280px;
            margin-left: auto;
            margin-right: auto;
            padding: 40px 0;
            text-align: center;

            .widget-button {
                margin-top: 1em;
            }
        }

        &__icon {
            background: $contao-color;
            border-radius: 10px;
            padding:10px;
        }

        &__headline {
            margin-top: 20px;
            margin-bottom: 25px;
            font-size: 36px;
            font-weight: $font-weight-light;
            line-height: 1;
        }

        &__description {
            margin: 1em 0;
            text-align: justify;
        }

        &__form {
            position: relative;
            max-width: 280px;
            margin: 0 auto 50px;
            opacity: 1;

            .widget-text {
                margin-top: 10px;

                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: $font-weight-medium;
                }
            }
        }

        &__fields {
            margin-bottom: 2em;
        }

        &__fieldtitle {
            margin-bottom: .5em;
            font-size: 18px;
            font-weight: $font-weight-bold;
            line-height: 30px;
        }

        &__fielddesc {
            margin-bottom: 1em;
            text-align: justify;
        }

        &__error {
            margin-bottom: 1.5em;
            padding: 4px 10px;
            color: #fff;
            background: $red-button;
            border-radius: 2px;
        }

        .widget-button {
            margin-bottom: .5em;
        }

        @include screen(960) {
            padding-top: 100px;

            &__header {
                float: left;
                width: 470px;
                max-width: none;
                padding: 0 60px 100px;
            }

            &__form {
                float: left;
                width: 370px;
                max-width: none;
                margin: 0 50px;
                padding-bottom: 50px;

                .widget-text {
                    label {
                        display: block;
                        float: left;
                        width: 120px;
                        padding-top: 10px;
                        font-weight: $font-weight-medium;
                    }

                    input {
                        width: 250px !important;
                    }
                }

                .widget-button {
                    width: 250px;
                    margin-left: 120px;
                }
            }
        }
    }
</style>
