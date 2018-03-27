<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <figure class="maintenance__image"><img src="../../../assets/images/composer-logo.png" /></figure>

            <div class="maintenance__about">
                <h1>{{ 'ui.maintenance.composerCache.title' | translate }}</h1>
                <p v-html="$t('ui.maintenance.composerCache.description')"></p>
            </div>

            <fieldset class="maintenance__actions">
                <button class="widget-button widget-button--primary widget-button--trash" @click="execute">{{ 'ui.maintenance.composerCache.button' | translate }}</button>
            </fieldset>
        </div>
    </section>
</template>

<script>
    export default {
        methods: {
            execute() {
                const task = {
                    type: 'composer-cache',
                };

                this.$store.dispatch('tasks/execute', task);
            },
        },
    };
</script>
