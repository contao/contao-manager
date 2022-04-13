<template>
    <message-overlay :message="overlayMessage" :active="safeMode || !supported">
        <section class="maintenance">
            <div class="maintenance__inside">
                <figure class="maintenance__image"><img src="../../../assets/images/logo.svg" alt="" /></figure>
                <div class="maintenance__about">
                    <h1>{{ $t('ui.maintenance.databaseMigration.title') }}</h1>
                    <p>{{ $t('ui.maintenance.databaseMigration.description') }}</p>
                </div>
                <fieldset class="maintenance__actions" v-if="loading">
                    <loader class="maintenance__loader"/>
                </fieldset>
                <fieldset class="maintenance__actions" v-else>
                    <button-group :to="{ name: routes.databaseMigration.name }" :label="$t('ui.maintenance.databaseMigration.button')" type="primary" icon="database">
                        <link-menu align="right" :items="advancedActions()" color="primary"/>
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
    import Loader from 'contao-package-list/src/components/fragments/Loader';
    import ButtonGroup from '../../widgets/ButtonGroup';
    import LinkMenu from 'contao-package-list/src/components/fragments/LinkMenu';

    export default {
        components: { MessageOverlay, Loader, ButtonGroup, LinkMenu },

        data: () => ({
            routes,
            loading: true,
            supported: false,
        }),

        computed: {
            ...mapState(['safeMode']),
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
        },

        async mounted () {
            const response = await this.$store.dispatch('server/contao/get');

            if (response.status === 200) {
                const commands = response.body?.cli?.commands;

                this.supported = commands['contao:migrate']
                    && commands['contao:migrate']?.options.includes('hash')
                    && commands['contao:migrate']?.options.includes('format')
                    && commands['contao:migrate']?.options.includes('dry-run');
            }

            this.loading = false
        }
    };
</script>
