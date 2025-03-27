<template>
    <section>
        <header class="setup__header">
            <img src="../../assets/images/database.svg" width="80" height="80" class="setup__icon" alt="" />
            <h1 class="setup__headline">{{ $t('ui.setup.database-connection.headline') }}</h1>
            <i18n-t tag="p" keypath="ui.setup.database-connection.description" class="setup__description">
                <template #env><code>.env.local</code></template>
            </i18n-t>
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

                        <text-field name="user" :label="$t('ui.setup.database-connection.user')" :disabled="processing" v-model="user" />
                        <text-field name="password" type="password" :label="$t('ui.setup.database-connection.password')" :disabled="processing" v-model="password" />
                        <text-field name="server" :label="$t('ui.setup.database-connection.server')" :disabled="processing" required v-model="server" />
                        <text-field name="database" :label="$t('ui.setup.database-connection.database')" :disabled="processing" required v-model="database" />
                    </div>
                    <div class="setup__actions">
                        <loading-button submit color="primary" icon="save" :loading="processing" :disabled="!valid">{{ $t('ui.setup.database-connection.save') }}</loading-button>
                        <button type="button" class="widget-button" :disabled="processing" @click="load" v-if="currentState === 'edit'">{{ $t('ui.setup.cancel') }}</button>
                    </div>
                </form>
            </main>

            <main class="setup__form" v-else v-bind:key="'confirmation'">
                <div class="setup__fields">
                    <h2 class="setup__fieldtitle">{{ $t('ui.setup.database-connection.formTitle') }}</h2>
                    <i18n-t tag="p" keypath="ui.setup.database-connection.connected" class="setup__fielddesc" v-if="url">
                        <template #database><i>{{ database }}</i></template>
                        <template #server><i>{{ server }}</i></template>
                    </i18n-t>
                    <button type="button" class="widget-button widget-button--edit widget-button--small" @click="currentState = 'edit'">{{ $t('ui.setup.database-connection.change') }}</button>
                </div>

                <transition name="animate-flip" type="transition" mode="out-in">
                    <div v-if="hasDatabaseError || !backupRestore || !hasBackups" v-bind:key="'migrate'">
                        <div class="setup__fields">
                            <h2 class="setup__fieldtitle">{{ $t('ui.setup.database-connection.schemaTitle') }}</h2>
                            <p class="setup__fielddesc setup__warning" v-if="status && status.total > 0">{{ $tc(`ui.setup.database-connection.${currentState}`, status.total) }}</p>
                            <p class="setup__fielddesc" v-else>{{ $t('ui.setup.database-connection.noChanges') }}</p>
                        </div>
                        <div class="setup__actions setup__actions--center">
                            <template v-if="status && status.total > 0">
                                <button type="button" class="widget-button widget-button--inline" @click="$emit('continue')" v-if="!hasDatabaseError">{{ $t('ui.setup.database-connection.skip') }}</button>
                                <button type="button" class="widget-button widget-button--inline widget-button--primary" @click="checkMigrations">{{ $t('ui.setup.database-connection.check') }}</button>
                            </template>
                            <button type="button" class="widget-button widget-button--primary" @click="$emit('continue')" v-else>{{ $t('ui.setup.continue') }}</button>
                        </div>
                    </div>

                    <div v-else-if="backupRestored" v-bind:key="'restored'">
                        <div class="setup__fields">
                            <h2 class="setup__fieldtitle">{{ $t('ui.setup.database-connection.restoreTitle') }}</h2>
                            <svg class="setup__check" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" /></svg>
                            <p class="setup__fielddesc">{{ $t('ui.setup.database-connection.restored') }}</p>
                        </div>
                        <div class="setup__actions setup__actions--center">
                            <button type="button" class="widget-button widget-button--primary" @click="$store.commit('contao/backup/setRestore', false)">{{ $t('ui.setup.continue') }}</button>
                        </div>
                    </div>

                    <div v-else v-bind:key="'backup'">
                        <div class="setup__fields">
                            <h2 class="setup__fieldtitle">{{ $t('ui.setup.database-connection.restoreTitle') }}</h2>
                            <p class="setup__fielddesc">{{ $tc('ui.setup.database-connection.restoreText', files.length) }}</p>
                            <radio-button required allow-html :options="fileOptions" name="selection" v-model="selection" v-if="files.length > 1" />

                            <p class="setup__fielddesc setup__warning">{{ $t('ui.setup.database-connection.backupWarning') }}</p>
                            <!--
                            TODO: re-enable when we fix the restore deleting file problem
                            <check-box :label="$t('ui.setup.database-connection.backup')" name="backup" v-model="backup"/>
                            -->
                        </div>

                        <div class="setup__actions">
                            <button type="button" class="widget-button widget-button--inline" @click="$store.commit('contao/backup/setRestore', false)">{{ $t('ui.setup.database-connection.skip') }}</button>
                            <button type="button" class="widget-button widget-button--inline widget-button--primary" :disabled="files.length > 1 && !selection" @click="restore">{{ $t('ui.setup.database-connection.restore') }}</button>
                        </div>
                    </div>
                </transition>
            </main>
        </transition>
    </section>
</template>

<script>
import { mapState, mapGetters } from 'vuex';

import TextField from '../widgets/TextField';
import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
import RadioButton from '../widgets/RadioButton';
// import CheckBox from '../widgets/CheckBox';
import datimFormat from 'contao-package-list/src/filters/datimFormat';
import filesize from '../../tools/filesize';

export default {
    components: { /*CheckBox,*/ RadioButton, TextField, LoadingButton },

    data: () => ({
        processing: false,
        validUrl: true,
        valid: false,
        validating: false,
        currentState: null,

        url: '',
        user: '',
        password: '',
        server: 'localhost',
        database: '',

        backup: true,
        selection: null,
    }),

    computed: {
        ...mapState('tasks', { taskStatus: 'status' }),
        ...mapState('contao/backup', { backupRestored: 'restored', backupRestore: 'restore' }),
        ...mapState('server/database', { currentUrl: 'url', urlPattern: 'pattern', status: 'status' }),
        ...mapState('contao/backup', ['files']),
        ...mapGetters('server/database', { hasDatabaseError: 'hasError' }),
        ...mapGetters('contao/backup', ['hasBackups']),

        fileOptions() {
            return this.files.map((f) => ({
                value: f.name,
                label: this.$t('ui.setup.database-connection.restoreOption', { date: datimFormat(f.createdAt), size: filesize(f.size) }),
            }));
        },
    },

    methods: {
        datimFormat(value) {
            return datimFormat(value, 'short', 'long');
        },

        checkMigrations() {
            this.$store.commit('checkMigrations');
        },

        parseUrl() {
            if (!this.validateUrl()) {
                return;
            }

            this.validating = true;

            const match = new RegExp(this.urlPattern, 'i').exec(this.url);

            this.user = match[3] ? decodeURIComponent(match[3]) : '';
            this.password = match[5] ? decodeURIComponent(match[5]) : '';
            this.server = decodeURIComponent(match[6]);
            this.database = decodeURIComponent(match[8]);

            if (this.server.substring(this.server.length - 5) === ':3306') {
                this.server = this.server.substring(0, this.server.length - 5);
            } else if (!this.server.includes(':')) {
                this.server = `${this.server}:3306`;
            }

            this.valid = this.validateUrl();
            this.validating = false;
        },

        updateUrl() {
            if (this.validating) {
                return;
            }

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
                    url += ':' + encodeURIComponent(this.password);
                }

                url += '@';
            }

            url += server;

            if (this.database) {
                url += '/' + encodeURIComponent(this.database);
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
            this.url = (await this.$store.dispatch('server/database/get')).data.url;
            this.currentState = this.status?.type;
            this.parseUrl();

            if (this.currentState === 'error') {
                this.validUrl = false;
                this.valid = false;
            }
        },

        async save() {
            this.processing = true;

            const response = await this.$store.dispatch('server/database/set', this.url);

            if (response.data.status.type === 'error') {
                this.processing = false;
                this.validUrl = false;
                this.valid = false;
                return;
            }

            await this.$store.dispatch('server/adminUser/get', false);

            this.processing = false;
        },

        async restore() {
            await this.$store.dispatch('tasks/execute', {
                name: 'contao/backup-restore',
                config: {
                    file: this.files.length > 1 ? this.selection : this.files[0].name,
                    backup: false, // TODO this.backup when we fix the restore deleting file problem
                },
            });

            if (this.taskStatus !== 'complete') {
                return;
            }

            this.$store.commit('contao/backup/setRestored');
            await this.$store.dispatch('tasks/deleteCurrent');
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

        status() {
            this.currentState = this.status?.type;
        },
    },

    mounted() {
        this.load();

        this.selection = null;
        this.backup = true;
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
.setup {
    &__or {
        position: relative;
        overflow: hidden;
        margin: 1em 0;
        text-align: center;

        &:before {
            content: "";
            position: absolute;
            top: 0.8em;
            left: 0;
            right: 0;
            display: block;
            height: 1px;
            background: var(--border);
            z-index: 1;
        }

        span {
            position: relative;
            padding: 0 10px;
            background: var(--popup-bg);
            z-index: 2;
        }
    }
}
</style>
