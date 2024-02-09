<template>
    <message-overlay :message="overlayMessage" :active="safeMode || supported === false">
        <section class="maintenance">
            <div class="maintenance__inside">
                <figure class="maintenance__image"><img src="../../../assets/images/logo.svg" alt="" /></figure>
                <div class="maintenance__about">
                    <h1>
                        {{ $t('ui.maintenance.database.title') }}
                        <span class="maintenance__error" v-if="hasError">{{ $t('ui.maintenance.database.error') }}</span>
                        <span class="maintenance__warning" v-if="hasWarning">{{ $t('ui.maintenance.database.warning') }}</span>
                        <span class="maintenance__warning" v-else-if="totalMigrations">{{ $tc('ui.maintenance.database.migrations', totalMigrations) }}</span>
                        <span class="maintenance__warning" v-else-if="totalSchemaUpdates">{{ $tc('ui.maintenance.database.schemaUpdates', totalSchemaUpdates) }}</span>
                    </h1>
                    <p>{{ $t('ui.maintenance.database.description') }}</p><br>
                    <p v-if="!supportsBackups && !loadingBackups">{{ $t('ui.maintenance.database.backupUnsupported') }}</p>
                    <p v-else-if="supportsBackups && backupFiles.length">{{ $tc('ui.maintenance.database.backupList', backupFiles.length, { date: datimFormat(backupFiles[0].createdAt) }) }}</p>
                    <p v-else-if="supportsBackups">{{ $t('ui.maintenance.database.backupEmpty') }}</p>
                </div>
                <fieldset class="maintenance__actions">
                    <loading-spinner class="maintenance__loader" v-if="loading"/>
                    <a class="widget-button widget-button--alert" href="/contao/install" v-else-if="hasError && !supported">{{ $t('ui.maintenance.database.installTool') }}</a>
                    <button class="widget-button widget-button--alert" v-else-if="hasError" @click="checkMigrations">{{ $t('ui.maintenance.database.button') }}</button>
                    <button-group
                        :label="$t('ui.maintenance.database.button')"
                        :type="(hasChanges || hasWarning) ? 'warning' : 'primary'" icon="database"
                        @click="checkMigrations"
                        v-else
                    >
                        <link-menu align="right" :items="advancedActions()" :color="hasChanges ? 'warning' : 'primary'"/>
                    </button-group>
                    <loading-button class="widget-button" @click="createBackup" :disabled="!supportsBackups" :loading="loadingBackups">{{ $t('ui.maintenance.database.createBackup') }}</loading-button>
                </fieldset>
            </div>
        </section>
    </message-overlay>
</template>

<script>
    import { mapGetters, mapState } from 'vuex';
    import datimFormat from 'contao-package-list/src/filters/datimFormat';

    import MessageOverlay from '../../fragments/MessageOverlay';
    import LoadingSpinner from 'contao-package-list/src/components/fragments/LoadingSpinner';
    import ButtonGroup from '../../widgets/ButtonGroup';
    import LinkMenu from 'contao-package-list/src/components/fragments/LinkMenu';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { MessageOverlay, LoadingSpinner, ButtonGroup, LinkMenu, LoadingButton },

        computed: {
            ...mapState(['safeMode']),
            ...mapState('server/database', ['loading', 'supported', 'status']),
            ...mapState('contao/backup', { supportsBackups: 'supported', backupFiles: 'files', loadingBackups: 'loading' }),
            ...mapGetters('server/database', ['hasError', 'hasChanges', 'hasWarning', 'totalMigrations', 'totalSchemaUpdates']),

            overlayMessage: vm => vm.safeMode ? vm.$t('ui.maintenance.safeMode') : vm.$t('ui.maintenance.unsupported'),
        },

        methods: {
            datimFormat (value) {
                return datimFormat(value, 'short', 'long');
            },

            checkMigrations () {
                this.$store.commit('checkMigrations');
            },

            advancedActions () {
                return [
                    {
                        label: this.$t('ui.maintenance.database.migrationOnly'),
                        action: () => {
                            this.$store.commit('checkMigrations', 'migrations-only');
                        },
                    },
                    {
                        label: this.$t('ui.maintenance.database.schemaOnly'),
                        action: () => {
                            this.$store.commit('checkMigrations', 'schema-only');
                        },
                    },
                ];
            },

            async createBackup () {
                await this.$store.dispatch('tasks/execute', { name: 'contao/backup-create' });
                await this.$store.dispatch('contao/backup/fetch', false)
            }
        },

        mounted () {
            this.$store.dispatch('server/database/get');
            this.$store.dispatch('contao/backup/fetch');
        },
    };
</script>
