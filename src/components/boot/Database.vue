<template>
    <boxed-layout v-if="current" :wide="true" slotClass="database-check">

        <header class="database-check__header">
            <img src="../../assets/images/database.svg" width="80" height="80" class="database-check__icon" alt="">
            <h1 class="database-check__headline">{{ $t('ui.server.database.headline') }}</h1>
            <i18n tag="p" path="ui.server.database.description" class="database-check__description">
                <template #env><code>.env</code></template>
            </i18n>
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
                        required :pattern="urlPattern" validate
                        :disabled="processing"
                        v-model="url" @keyup="validUrl=true" @blur="parseUrl"
                        :error="validUrl ? undefined : $t('ui.server.database.validUrl')"
                    />

                    <div class="database-check__or"><span>{{ $t('ui.server.database.or') }}</span></div>

                    <text-field name="user" :label="$t('ui.server.database.user')" :disabled="processing" v-model="user"/>
                    <text-field name="password" type="password" :label="$t('ui.server.database.password')" :disabled="processing" v-model="password"/>
                    <text-field name="server" :label="$t('ui.server.database.server')" :disabled="processing" required v-model="server"/>
                    <text-field name="database" :label="$t('ui.server.database.database')" :disabled="processing" required v-model="database"/>

                    <text-field name="serverVersion" :label="$t('ui.server.database.serverVersion')" :disabled="processing" required v-model="serverVersion" v-if="unknownServerVersion"/>
                    <select-menu name="serverVersion" :label="$t('ui.server.database.serverVersion')" :disabled="processing" required include-blank :options="serverVersions" v-model="serverVersion" v-else/>

                </div>

                <loading-button submit color="primary" icon="save" :loading="processing" :disabled="!valid">{{ $t('ui.server.database.save') }}</loading-button>
                <button type="button" class="widget-button" :disabled="processing" @click="cancel">{{ $t('ui.server.database.cancel') }}</button>
            </form>

        </main>

    </boxed-layout>

    <boot-check v-else :progress="bootState" :title="$t('ui.server.database.title')" :description="bootDescription">
        <button type="button" v-if="bootState === 'warning'" @click="checkDatabase" class="widget-button widget-button--warning">{{ $t('ui.server.database.check') }}</button>
        <button type="button" v-if="bootState === 'action'" @click="showConfiguration" class="widget-button widget-button--primary widget-button--run">{{ $t('ui.server.database.setup') }}</button>
        <button type="button" class="widget-button widget-button--edit" v-if="bootState === 'success' || bootState === 'warning'" @click="showConfiguration">{{ $t('ui.server.database.change') }}</button>
    </boot-check>
</template>

<script>
    import views from '../../router/views';
    import boot from '../../mixins/boot';
    import routes from '../../router/routes';

    import BootCheck from '../fragments/BootCheck';
    import BoxedLayout from '../layouts/Boxed';
    import TextField from '../widgets/TextField';
    import SelectMenu from '../widgets/SelectMenu';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        mixins: [boot],
        components: { BootCheck, BoxedLayout, TextField, SelectMenu, LoadingButton },

        data: () => ({
            processing: false,
            validUrl: true,
            valid: false,
            currentUrl: '',
            urlPattern: '',

            url: '',
            user: '',
            password: '',
            server: 'localhost',
            database: '',
            serverVersion: ''
        }),

        computed: {
            unknownServerVersion: vm => vm.serverVersion && !vm.serverVersions.find(v => v.value === vm.serverVersion),

            serverVersions: (vm) => ([
                {
                    value: '8.0',
                    label: 'MySQL 8.0+',
                },
                {
                    value: '5.7.9',
                    label: 'MySQL 5.7.9+',
                },
                {
                    value: 'mariadb-10.2.7',
                    label: 'MariaDB 10.2.7+',
                },
                {
                    value: '5.1',
                    label: vm.$t('ui.server.database.oldVersion'),
                },
            ]),
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
                    this.url = result.url;
                    this.currentUrl = result.url;
                    this.urlPattern = result.pattern;
                    this.parseUrl();

                    switch (result.status.type) {
                        case 'error':
                            this.bootState = 'action';
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
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.error');
                }

                this.$emit('result', 'Database', this.bootState);
            },

            showConfiguration() {
                if (this.currentUrl) {
                    this.url = this.currentUrl;
                    this.parseUrl();
                }

                this.$emit('view', 'Database');
            },

            checkDatabase() {
                this.$store.commit('server/database/setBooting', true);
                this.$store.commit('setView', views.READY);
                this.$router.push({ name: routes.databaseMigration.name });
            },

            parseUrl() {
                if (!this.validateUrl()) {
                    return;
                }

                const match = new RegExp(this.urlPattern, 'i').exec(this.url)

                this.user = match[3] ? decodeURIComponent(match[3]) : '';
                this.password = match[5] ? decodeURIComponent(match[5]) : '';
                this.server = decodeURIComponent(match[6]);
                this.database = decodeURIComponent(match[8]);
                this.serverVersion = '';

                if (this.server.substring(this.server.length - 5) === ':3306') {
                    this.server = this.server.substring(0, this.server.length - 5);
                } else if (!this.server.includes(':')) {
                    this.server = `${this.server}:3306`;
                }

                if (match[9]) {
                    const params = new URLSearchParams(match[9]);
                    this.serverVersion = params.get('serverVersion');
                }

                this.valid = this.validateUrl();
            },

            updateUrl() {
                this.valid = false;

                if (!this.server) {
                    return;
                }

                const serverParts = this.server.split(':', 2);
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

                if (this.serverVersion) {
                    url += `?serverVersion=${this.serverVersion}`;
                }

                this.url = url;
                this.valid = this.validateUrl();
            },

            validateUrl() {
                this.validUrl = true;
                this.valid = false;

                if (this.url === '') {
                    return false;
                }

                this.validUrl = new RegExp(this.urlPattern, 'i').test(this.url);

                return this.validUrl;
            },

            async save() {
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
        },

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
            serverVersion() {
                this.updateUrl();
            },
        },

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

            .widget {
                margin-top: 10px;

                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: $font-weight-medium;
                }
            }
        }

        &__fields {
            margin-bottom: 1.5em;
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

        &__or {
            position: relative;
            overflow: hidden;
            margin: 1em 0;
            text-align: center;

            &:before {
                content: "";
                position: absolute;
                top: .8em;
                left: 0;
                right: 0;
                display: block;
                height: 1px;
                background: $border-color;
                z-index: 1;
            }

            span {
                position: relative;
                padding: 0 10px;
                background: #fff;
                z-index: 2;
            }
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

                .widget {
                    label {
                        float: left;
                        width: 120px;
                        padding-top: 10px;
                    }

                    input,
                    select {
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
