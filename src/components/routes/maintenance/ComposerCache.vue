<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <figure class="maintenance__image"><img src="../../../assets/images/composer-logo.png" alt=""></figure>

            <div class="maintenance__about">
                <h1>{{ $t('ui.maintenance.composerCache.title') }}</h1>
                <p v-html="$t('ui.maintenance.composerCache.description')"></p>
            </div>

            <fieldset class="maintenance__actions">
                <button class="widget-button widget-button--primary widget-button--trash" @click="execute">{{ $t('ui.maintenance.composerCache.button') }}</button>
            </fieldset>
        </div>
    </section>
</template>

<script>
    export default {
        methods: {
            execute() {
                this.$store.dispatch('tasks/execute', { name: 'composer/clear-cache' });
            },
        },
    };
</script>
