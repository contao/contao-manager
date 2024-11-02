<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <figure class="maintenance__image"><img src="../../../assets/images/composer-logo.png" alt=""></figure>

            <div class="maintenance__about">
                <h1>{{ $t('ui.maintenance.composerInstall.title') }}</h1>
                <i18n-t tag="p" keypath="ui.maintenance.composerInstall.description">
                    <template #vendor><code>/vendor</code></template>
                    <template #composerLock><code>composer.lock</code></template>
                </i18n-t>
            </div>

            <fieldset class="maintenance__actions">
                <button-group :label="$t('ui.maintenance.composerInstall.button')" type="primary" icon="run" @click="composerInstall">
                    <link-menu align="right" :items="advancedActions" color="primary"/>
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
