<template>
    <boxed-layout v-if="current" :wide="true" slotClass="database-check">

        <header class="database-check__header">
            <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" class="database-check__icon" />
            <h1 class="database-check__headline">{{ $t('ui.server.database.headline') }}</h1>
            <p class="database-check__description">{{ $t('ui.server.database.description') }}</p>
        </header>

        <main class="database-check__form">
            <form @submit.prevent="save">
                <div class="database-check__fields">
                    <h2 class="database-check__fieldtitle">{{ $t('ui.server.database.formTitle') }}</h2>
                    <p class="database-check__fielddesc">{{ $t('ui.server.database.formText') }}</p>

                    <text-field
                        ref="url"
                        name="url" type="url"
                        :label="$t('ui.server.database.url')" :placeholder="$t('ui.server.database.urlPlaceholder')"
                        required :pattern="urlPattern"
                        :disabled="processing"
                        v-model="url" @focus="validUrl=true" @blur="parseUrl"
                        :error="validUrl ? undefined : $t('ui.server.database.validUrl')"
                    />

                    <p>or</p>

                    <text-field name="user" :label="$t('ui.server.database.user')" :disabled="processing" v-model="user"/>
                    <text-field name="password" type="password" :label="$t('ui.server.database.password')" :disabled="processing" v-model="password"/>
                    <text-field name="server" :label="$t('ui.server.database.server')" :disabled="processing" required v-model="server"/>
                    <text-field name="database" :label="$t('ui.server.database.database')" :disabled="processing" required v-model="database"/>
                </div>

                <loading-button submit color="primary" icon="save" :loading="processing" :disabled="!valid">{{ $t('ui.server.database.save') }}</loading-button>
                <button type="button" class="widget-button" :disabled="processing" @click="cancel">{{ $t('ui.server.database.cancel') }}</button>
            </form>

        </main>

    </boxed-layout>

    <boot-check v-else :progress="bootState" :title="$t('ui.server.database.title')" :description="bootDescription">
        <button type="button" v-if="bootState === 'warning'" @click="checkDatabase" class="widget-button widget-button--warning">{{ $t('ui.server.database.check') }}</button>
        <button type="button" v-if="bootState === 'error'" @click="showConfiguration" class="widget-button widget-button--primary widget-button--run">{{ $t('ui.server.database.setup') }}</button>
        <button type="button" class="widget-button widget-button--edit" v-else-if="bootState !== 'loading'" @click="showConfiguration">{{ $t('ui.server.database.change') }}</button>
    </boot-check>
</template>

<script>
    import views from '../../router/views';
    import boot from '../../mixins/boot';

    import BootCheck from '../fragments/BootCheck';
    import BoxedLayout from '../layouts/Boxed';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
    import TextField from '../widgets/TextField';
    import RadioButton from '../widgets/RadioButton';
    import routes from '../../router/routes';

    export default {
        mixins: [boot],
        components: { RadioButton, BootCheck, BoxedLayout, TextField, LoadingButton },

        data: () => ({
            processing: false,
            validUrl: true,
            valid: false,
            urlPattern: '',

            url: '',
            user: '',
            password: '',
            server: 'localhost',
            database: '',
        }),

        watch: {
            user() {
                this.updateUrl();
            },
            password() {
                this.updateUrl();
            },
            server() {
                this.updateUrl();
            },
            database() {
                this.updateUrl();
            },
        },

        methods: {
            reload() {
                this.processing = true;
                window.location.reload()
            },

            async boot() {
                this.bootState = 'loading';
                this.bootDescription = this.$t('ui.server.running');
                this.$store.commit('setSafeMode', false);

                const response = await this.$store.dispatch('server/database/get', false);
                const result = response.body;

                if (response.status === 200) {
                    this.url = response.body.url;
                    this.urlPattern = result.pattern;
                    this.parseUrl();

                    switch (result.status.type) {
                        case 'error':
                            this.bootState = 'error';
                            this.$store.commit('setSafeMode', true);

                            if (this.url === '') {
                                this.bootDescription = this.$t('ui.server.database.empty');
                            } else if (result.status.message) {
                                this.bootDescription = result.status.message;
                            } else {
                                this.bootDescription = this.$t('ui.server.database.error');
                            }
                            break;

                        case 'migration':
                        case 'schema':
                            this.bootState = 'warning';
                            this.bootDescription = this.$tc(`ui.server.database.${result.status.type}`, result.status.total);
                            break;

                        default:
                            this.bootState = 'success';
                            this.bootDescription = this.$t('ui.server.database.success');
                            break;
                    }

                } else if (response.status === 501) {
                    this.bootState = 'info';
                    this.bootDescription = this.$t('ui.server.database.unsupported');
                } else if (response.status === 503) {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.prerequisite');
                } else {
                    this.bootState = 'action';
                    this.bootDescription = this.$t('ui.server.error');
                }

                this.$emit('result', 'Database', this.bootState);
            },

            showConfiguration() {
                this.$emit('view', 'Database');
            },

            checkDatabase() {
                this.$store.commit('setView', views.READY);
                this.$router.push({ name: routes.databaseMigration.name });
            },

            parseUrl() {
                this.validUrl = true;
                this.valid = false;

                if (this.url === '') {
                    return;
                }

                const validator = new RegExp(this.urlPattern, 'i');

                this.validUrl = validator.test(this.url);

                if (!this.validUrl) {
                    return;
                }

                const match = validator.exec(this.url)

                this.user = match[3] ? decodeURIComponent(match[3]) : '';
                this.password = match[5] ? decodeURIComponent(match[5]) : '';
                this.server = decodeURIComponent(match[6]);
                this.database = decodeURIComponent(match[8]);

                if (this.server.substring(this.server.length - 5) === ':3306') {
                    this.server = this.server.substring(0, this.server.length - 5);
                } else if (!this.server.includes(':')) {
                    this.server = `${this.server}:3306`;
                }

                this.valid = true;
            },

            updateUrl() {
                this.valid = false;

                if (!this.server) {
                    return;
                }

                const serverParts = this.server.split(':', 1);
                const server = `${encodeURIComponent(serverParts[0])}:${serverParts[1] || '3306'}`;

                let url = 'mysql://';

                if (this.user) {
                    url += encodeURIComponent(this.user);

                    if (this.password) {
                        url += ':'+encodeURIComponent(this.password);
                    }

                    url += '@';
                }

                url += server;

                if (this.database) {
                    url += '/'+encodeURIComponent(this.database);
                }

                this.url = url;
                this.validUrl = true;
                this.valid = true;
            },

            async save(event) {
                event.preventDefault();
                this.processing = true;

                const response = await this.$store.dispatch('server/database/set', this.url);

                if (response.body.status.type === 'error') {
                    this.processing = false;
                    this.validUrl = false;
                    this.valid = false;
                    return;
                }

                this.processing = false;
                this.$emit('result', 'Database', null);
                this.$emit('view', null);
                this.boot();
            },

            cancel() {
                this.$emit('view', null);
            }
        }
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .database-check {
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
