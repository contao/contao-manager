<template>
    <message-overlay :message="overlayMessage" :active="safeMode || !supported">
        <section class="maintenance">
            <div class="maintenance__inside">
                <figure class="maintenance__image"><img src="../../../assets/images/logo.svg" alt="" /></figure>
                <div class="maintenance__about">
                    <h1>
                        {{ $t('ui.maintenance.databaseMigration.title') }}
                        <span class="maintenance__warning" v-if="totalMigrations">{{ $tc('ui.maintenance.databaseMigration.migrations', totalMigrations) }}</span>
                        <span class="maintenance__warning" v-else-if="totalSchemaUpdates">{{ $tc('ui.maintenance.databaseMigration.schemaUpdates', totalSchemaUpdates) }}</span>
                    </h1>
                    <p>{{ $t('ui.maintenance.databaseMigration.description') }}</p>
                </div>
                <fieldset class="maintenance__actions">
                    <button-group :to="{ name: routes.databaseMigration.name }" :label="$t('ui.maintenance.databaseMigration.button')" :type="(totalMigrations || totalSchemaUpdates) ? 'warning' : 'primary'" icon="database">
                        <link-menu align="right" :items="advancedActions()" :color="(totalMigrations || totalSchemaUpdates) ? 'warning' : 'primary'"/>
                    </button-group>
                </fieldset>
            </div>
        </section>
    </message-overlay>
</template>

<script>
    import { mapState } from 'vuex';
    import routes from '../../../router/routes';

    import MessageOverlay from '../../fragments/MessageOverlay';
    import ButtonGroup from '../../widgets/ButtonGroup';
    import LinkMenu from 'contao-package-list/src/components/fragments/LinkMenu';

    export default {
        components: { MessageOverlay, ButtonGroup, LinkMenu },

        data: () => ({
            routes,
        }),

        computed: {
            ...mapState(['safeMode']),
            ...mapState('server/database', ['supported', 'totalMigrations', 'totalSchemaUpdates']),
            overlayMessage: vm => vm.safeMode ? vm.$t('ui.maintenance.safeMode') : vm.$t('ui.maintenance.unsupported'),
        },

        methods: {
            advancedActions () {
                return [
                    {
                        label: this.$t('ui.maintenance.databaseMigration.migrationOnly'),
                        href: this.$router.resolve({ name: routes.databaseMigration.name, query: { type: 'migrations-only' } }).href,
                    },
                    {
                        label: this.$t('ui.maintenance.databaseMigration.schemaOnly'),
                        href: this.$router.resolve({ name: routes.databaseMigration.name, query: { type: 'schema-only' } }).href,
                    },
                ];
            },
        }
    };
</script>
