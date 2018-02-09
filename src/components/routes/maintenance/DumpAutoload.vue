<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <figure class="maintenance__image"><img src="../../../assets/images/composer-logo.png" /></figure>

            <div class="maintenance__about">
                <h1>{{ 'ui.maintenance.dumpAutoload.title' | translate }}</h1>
                <p>{{ 'ui.maintenance.dumpAutoload.description' | translate }}</p>
            </div>

            <fieldset class="maintenance__actions">
                <button class="widget-button widget-button--primary widget-button--update" @click="execute">{{ 'ui.maintenance.dumpAutoload.button' | translate }}</button>
            </fieldset>
        </div>
    </section>
</template>

<script>
    export default {
        methods: {
            execute() {
                this.$store.dispatch('tasks/execute', { name: 'composer/dump-autoload' });
            },
        },
    };
</script>
