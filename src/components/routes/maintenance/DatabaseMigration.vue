<template>
    <message-overlay :message="overlayMessage" :active="safeMode || supported === false">
        <section class="maintenance">
            <div class="maintenance__inside">
                <figure class="maintenance__image"><img src="../../../assets/images/logo.svg" alt="" /></figure>
                <div class="maintenance__about">
                    <h1>
                        {{ $t('ui.maintenance.databaseMigration.title') }}
                        <span class="maintenance__error" v-if="hasError && hasInstallTool">{{ $t('ui.maintenance.databaseMigration.error') }}</span>
                        <span class="maintenance__warning" v-if="totalMigrations">{{ $tc('ui.maintenance.databaseMigration.migrations', totalMigrations) }}</span>
                        <span class="maintenance__warning" v-else-if="totalSchemaUpdates">{{ $tc('ui.maintenance.databaseMigration.schemaUpdates', totalSchemaUpdates) }}</span>
                    </h1>
                    <p>{{ $t('ui.maintenance.databaseMigration.description') }}</p>
                </div>
                <fieldset class="maintenance__actions">
                    <loader class="maintenance__loader" v-if="loading"/>
                    <a class="widget-button widget-button--alert" href="/contao/install" v-else-if="hasError && hasInstallTool">{{ $t('ui.maintenance.databaseMigration.installTool') }}</a>
                    <button-group
                        :label="$t('ui.maintenance.databaseMigration.button')"
                        :type="hasChanges ? 'warning' : 'primary'" icon="database"
                        @click="checkMigrations"
                        v-else
                    >
                        <link-menu align="right" :items="advancedActions()" :color="hasChanges ? 'warning' : 'primary'"/>
                    </button-group>
                </fieldset>
            </div>
        </section>
    </message-overlay>
</template>

<script>
    import { mapGetters, mapState } from 'vuex';

    import MessageOverlay from '../../fragments/MessageOverlay';
    import Loader from 'contao-package-list/src/components/fragments/Loader';
    import ButtonGroup from '../../widgets/ButtonGroup';
    import LinkMenu from 'contao-package-list/src/components/fragments/LinkMenu';

    export default {
        components: { MessageOverlay, Loader, ButtonGroup, LinkMenu },

        computed: {
            ...mapState(['safeMode']),
            ...mapState('contao/install-tool', { hasInstallTool: 'isSupported' }),
            ...mapState('server/database', ['loading', 'supported', 'status']),
            ...mapGetters('server/database', ['hasError', 'hasChanges', 'totalMigrations', 'totalSchemaUpdates']),

            overlayMessage: vm => vm.safeMode ? vm.$t('ui.maintenance.safeMode') : vm.$t('ui.maintenance.unsupported'),
        },

        methods: {
            checkMigrations () {
                this.$store.commit('checkMigrations');
            },

            advancedActions () {
                return [
                    {
                        label: this.$t('ui.maintenance.databaseMigration.migrationOnly'),
                        action: () => {
                            this.$store.commit('checkMigrations', 'migrations-only');
                        },
                    },
                    {
                        label: this.$t('ui.maintenance.databaseMigration.schemaOnly'),
                        action: () => {
                            this.$store.commit('checkMigrations', 'schema-only');
                        },
                    },
                ];
            },
        },

        mounted () {
            this.$store.dispatch('server/database/get');
        },
    };
</script>
