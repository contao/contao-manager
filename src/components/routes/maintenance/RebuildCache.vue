<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <figure class="maintenance__image">
                <img class="color-scheme--light" src="../../../assets/images/symfony-logo.svg" alt="">
                <img class="color-scheme--dark" src="../../../assets/images/symfony-logo--dark.svg" alt="">
            </figure>

            <div class="maintenance__about">
                <h1>{{ $t('ui.maintenance.rebuildCache.title') }}</h1>
                <p>{{ $t('ui.maintenance.rebuildCache.description') }}</p>
            </div>

            <fieldset class="maintenance__actions">
                <button-group :label="$t('ui.maintenance.rebuildCache.rebuildProd')" type="primary" icon="update" @click="rebuildProd">
                    <link-menu align="right" :items="advancedActions()" color="primary"/>
                </button-group>
            </fieldset>
        </div>
    </section>
</template>

<script>
    import ButtonGroup from '../../widgets/ButtonGroup';
    import LinkMenu from 'contao-package-list/src/components/fragments/LinkMenu';

    export default {
        components: { ButtonGroup, LinkMenu },

        methods: {
            advancedActions() {
                return [
                    {
                        label: this.$t('ui.maintenance.rebuildCache.rebuildDev'),
                        action: this.rebuildDev,
                    },
                    {
                        label: this.$t('ui.maintenance.rebuildCache.clearProd'),
                        action: this.clearProd,
                    },
                    {
                        label: this.$t('ui.maintenance.rebuildCache.clearDev'),
                        action: this.clearDev,
                    },
                ];
            },

            rebuildProd() {
                this.execute('prod', true);
            },

            rebuildDev() {
                this.execute('dev', true);
            },

            clearProd() {
                this.execute('prod', false);
            },

            clearDev() {
                this.execute('dev', false);
            },

            execute(environment, warmup) {
                const task = {
                    name: 'contao/rebuild-cache',
                    config: { environment, warmup },
                };

                this.$store.dispatch('tasks/execute', task);
            },
        },
    };
</script>
