<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <figure class="maintenance__image"><img src="../../../assets/images/logo.svg" /></figure>

            <div class="maintenance__about">
                <h1>{{ 'ui.maintenance.installTool.title' | translate }}</h1>
                <p>{{ 'ui.maintenance.installTool.description' | translate }}</p>
            </div>

            <fieldset class="maintenance__actions">
                <button-group :label="$t('ui.maintenance.installTool.unlock')" type="primary" icon="update" @click="unlock">
                    <link-menu :items="advancedActions()" color="primary"/>
                </button-group>
            </fieldset>
        </div>
    </section>
</template>

<script>
    import ButtonGroup from '../../widgets/ButtonGroup';
    import LinkMenu from '../../fragments/LinkMenu';

    export default {
        components: { ButtonGroup, LinkMenu },

        methods: {
            advancedActions() {
                return [
                    {
                        label: this.$t('ui.maintenance.installTool.lock'),
                        action: this.lock,
                    },
                ];
            },

            unlock() {
                const task = {
                    name: 'contao/install-tool',
                    config: { lock: false },
                };

                this.$store.dispatch('tasks/execute', task);
            },

            lock() {
                const task = {
                    name: 'contao/install-tool',
                    config: { lock: true },
                };

                this.$store.dispatch('tasks/execute', task);
            },
        },
    };
</script>
