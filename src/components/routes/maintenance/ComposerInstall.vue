<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <figure class="maintenance__image"><img src="../../../assets/images/composer-logo.png" alt=""></figure>

            <div class="maintenance__about">
                <h1>{{ $t('ui.maintenance.composerInstall.title') }}</h1>
                <p v-html="$t('ui.maintenance.composerInstall.description')"></p>
            </div>

            <fieldset class="maintenance__actions">
                <button-group :label="$t('ui.maintenance.composerInstall.button')" type="primary" icon="run" @click="composerInstall">
                    <link-menu :items="advancedActions" color="primary"/>
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

        computed: {
            advancedActions() {
                return [
                    {
                        label: this.$t('ui.maintenance.composerInstall.update'),
                        action: this.composerUpdate,
                    },
                ];
            },
        },

        methods: {
            composerInstall() {
                this.$store.dispatch('tasks/execute', { name: 'composer/install' });
            },

            composerUpdate() {
                this.$store.dispatch('tasks/execute', { name: 'composer/update' });
            },
        },
    };
</script>
