<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <figure class="maintenance__image"><img src="../../../assets/images/symfony-logo.svg" /></figure>

            <div class="maintenance__about">
                <h1>{{ 'ui.maintenance.rebuildCache.title' | translate }}</h1>
                <p>{{ 'ui.maintenance.rebuildCache.description' | translate }}</p>
            </div>

            <fieldset class="maintenance__config">
                <select-menu name="environment" v-model="env" :options="{ 'prod': $t('ui.maintenance.rebuildCache.prod'), 'dev': $t('ui.maintenance.rebuildCache.dev') }"/>
            </fieldset>

            <fieldset class="maintenance__actions">
                <button class="widget-button widget-button--primary widget-button--update" @click="execute">{{ 'ui.maintenance.rebuildCache.button' | translate }}</button>
            </fieldset>
        </div>
    </section>
</template>

<script>
    import SelectMenu from '../../widgets/SelectMenu';

    export default {
        components: { SelectMenu },

        data: () => ({
            env: 'prod',
        }),

        methods: {
            execute() {
                const task = {
                    type: 'rebuild-cache',
                    environment: this.env,
                };

                this.$store.dispatch('tasks/execute', task);
            },
        },
    };
</script>
