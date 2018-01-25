<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <div class="maintenance__about">
                <h1>{{ 'ui.maintenance.composerInstall.title' | translate }}</h1>
                <p v-html="$t('ui.maintenance.composerInstall.description')"></p>
            </div>

            <fieldset class="maintenance__actions">
                <button class="widget-button widget-button--primary widget-button--run" @click="execute">{{ 'ui.maintenance.composerInstall.button' | translate }}</button>
            </fieldset>
        </div>
    </section>
</template>

<script>
    export default {
        methods: {
            execute() {
                const task = {
                    type: 'composer-install',
                };

                this.$store.dispatch('tasks/execute', task);
            },
        },
    };
</script>
