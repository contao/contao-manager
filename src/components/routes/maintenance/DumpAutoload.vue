<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <figure class="maintenance__image"><img src="../../../assets/images/composer-logo.png" alt="" /></figure>

            <div class="maintenance__about">
                <h1>{{ $t('ui.maintenance.dumpAutoload.title') }}</h1>
                <p>{{ $t('ui.maintenance.dumpAutoload.description') }}</p>
            </div>

            <fieldset class="maintenance__actions">
                <button class="widget-button widget-button--primary widget-button--update" @click="execute">{{ $t('ui.maintenance.dumpAutoload.button') }}</button>
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
