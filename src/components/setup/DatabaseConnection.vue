<template>
    <section>
        <header class="setup__header">
            <img src="../../assets/images/database.svg" width="80" height="80" class="setup__icon" alt="">
            <h1 class="setup__headline">{{ $t('ui.setup.database-connection.headline') }}</h1>
            <i18n tag="p" path="ui.setup.database-connection.description" class="setup__description">
                <template #env><code>.env</code></template>
            </i18n>
        </header>

        <transition name="animate-flip" type="transition" mode="out-in" v-if="currentState">

            <main class="setup__form" v-if="currentState === 'error' || currentState === 'edit'" v-bind:key="'setup'">
                <form @submit.prevent="save">
                    <div class="setup__fields">
                        <h2 class="setup__fieldtitle">{{ $t('ui.setup.database-connection.formTitle') }}</h2>
                        <p class="setup__fielddesc">{{ $t('ui.setup.database-connection.formText') }}</p>
                        <p class="setup__warning" v-if="currentState !== 'edit' && currentUrl !== ''">{{ $t('ui.setup.database-connection.error') }}</p>

                        <text-field
                            ref="url"
                            name="url" type="url"
                            :label="$t('ui.setup.database-connection.url')" placeholder="mysql://user:password@server:port/database"
                            required :pattern="urlPattern" validate
                            :disabled="processing"
                            v-model="url" @keyup="validUrl=true" @blur="parseUrl"
                            :error="validUrl ? undefined : $t('ui.setup.database-connection.validUrl')"
                        />

                        <div class="setup__or"><span>{{ $t('ui.setup.database-connection.or') }}</span></div>

                        <text-field name="user" :label="$t('ui.setup.database-connection.user')" :disabled="processing" v-model="user"/>
                        <text-field name="password" type="password" :label="$t('ui.setup.database-connection.password')" :disabled="processing" v-model="password"/>
                        <text-field name="server" :label="$t('ui.setup.database-connection.server')" :disabled="processing" required v-model="server"/>
                        <text-field name="database" :label="$t('ui.setup.database-connection.database')" :disabled="processing" required v-model="database"/>

                        <text-field name="serverVersion" :label="$t('ui.setup.database-connection.serverVersion')" :disabled="processing" required v-model="serverVersion" v-if="unknownServerVersion"/>
                        <select-menu name="serverVersion" :label="$t('ui.setup.database-connection.serverVersion')" :disabled="processing" required include-blank :options="serverVersions" v-model="serverVersion" v-else/>
                    </div>
                    <div class="setup__fields">
                        <loading-button submit color="primary" icon="save" :loading="processing" :disabled="!valid || !serverVersion">{{ $t('ui.setup.database-connection.save') }}</loading-button>
                        <button type="button" class="widget-button" :disabled="processing" @click="load" v-if="currentState === 'edit'">{{ $t('ui.setup.cancel') }}</button>
                    </div>
                </form>
            </main>

            <main class="setup__form setup__form--center" v-else v-bind:key="'confirmation'">
                <div class="setup__fields setup__fields--center">
                    <svg class="setup__check" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" /></svg>
                    <p class="setup__fielddesc">Successfully connected to database <i>{{ database }}</i> on <i>{{ server }}</i> ({{ currentServerVersion }}).</p>

                    <p class="setup__fielddesc setup__warning" v-if="status && status.total > 0">{{ $tc(`ui.setup.database-connection.${currentState}`, status.total) }}</p>
                    <p class="setup__fielddesc" v-else>{{ $t('ui.setup.database-connection.noChanges') }}</p>
                </div>
                <div class="setup__fields setup__fields--center">
                    <button type="button" class="widget-button widget-button--inline" @click="currentState = 'edit'">{{ $t('ui.setup.database-connection.change') }}</button>
                    <button type="button" class="widget-button widget-button--inline widget-button--primary" @click="checkMigrations" v-if="status && status.total > 0">{{ $t('ui.setup.database-connection.check') }}</button>
                    <button type="button" class="widget-button widget-button--inline widget-button--primary" @click="$emit('continue')" v-else>{{ $t('ui.setup.continue') }}</button>
                </div>
            </main>
        </transition>
    </section>
</template>

<script>
    import { mapState } from 'vuex';

    import TextField from '../widgets/TextField';
    import SelectMenu from '../widgets/SelectMenu';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { TextField, SelectMenu, LoadingButton },

        data: () => ({
            processing: false,
            validUrl: true,
            valid: false,
            currentState: null,

            url: '',
            user: '',
            password: '',
            server: 'localhost',
            database: '',
            serverVersion: ''
        }),

        computed: {
            ...mapState('server/database', { currentUrl: 'url', urlPattern: 'pattern', status: 'status' }),

            unknownServerVersion: vm => vm.serverVersion && !vm.serverVersions.find(v => v.value === vm.serverVersion),
            currentServerVersion: vm => vm.serverVersions.find(v => v.value === vm.serverVersion)?.label || vm.serverVersion,
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
                    label: vm.$t('ui.setup.database-connection.oldVersion'),
                },
            ]),
        },

        methods: {
            checkMigrations() {
                this.$store.commit('checkMigrations');
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

            async load() {
                this.url = (await this.$store.dispatch('server/database/get')).body.url;
                this.currentState = this.status?.type;
                this.parseUrl();

                if (this.currentState === 'error' && this.currentUrl) {
                    this.validUrl = false;
                    this.valid = false;
                }
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

                await this.$store.dispatch('server/adminUser/get', false)

                this.processing = false;
            },
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

            status() {
                this.currentState = this.status?.type;
            }
        },

        mounted() {
            this.load();
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .setup {
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
    }
</style>
