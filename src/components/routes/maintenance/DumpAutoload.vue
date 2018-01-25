<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <div class="maintenance__about">
                <h1>Composer Class Loader</h1>
                <p>The Composer autoloader is responsible for PHP class loading. The autoloader must be dumped it after adding custom namespaces to the root composer.json.</p>
            </div>

            <fieldset class="maintenance__actions">
                <button class="widget-button widget-button--primary widget-button--update" @click="execute">{{ 'ui.maintenance.dump-autoload' | translate }}</button>
            </fieldset>
        </div>
    </section>
</template>

<script>
    export default {
        methods: {
            execute() {
                const task = {
                    type: 'dump-autoload',
                };

                this.$store.dispatch('tasks/execute', task);
            },
        },
    };
</script>
