<template>
    <section class="maintenance" v-if="opcodeEnabled">
        <div class="maintenance__inside">
            <figure class="maintenance__image"><img src="../../../assets/images/php-logo.svg" alt="" /></figure>

            <div class="maintenance__about">
                <h1>{{ 'ui.maintenance.opcodeCache.title' | translate }}</h1>
                <p>{{ 'ui.maintenance.opcodeCache.description' | translate }}</p>
            </div>

            <fieldset class="maintenance__actions">
                <loader class="maintenance__loader" v-if="loading "/>
                <button class="widget-button widget-button--primary widget-button--trash" v-else @click="execute">{{ 'ui.maintenance.opcodeCache.button' | translate }}</button>
            </fieldset>
        </div>
    </section>
</template>

<script>
    import Loader from 'contao-package-list/src/components/fragments/Loader';

    export default {
        components: { Loader },

        data: () => ({
            opcodeEnabled: false,
            loading: false,
        }),

        methods: {
            execute() {
                this.loading = true;

                this.$store.dispatch('server/opcache/delete').then((status) => {
                    this.loading = false;
                    this.opcodeEnabled = status.opcache_enabled;
                });
            },
        },

        mounted() {
            this.$store.dispatch('server/opcache/get').then(
                (status) => {
                    this.opcodeEnabled = status.opcache_enabled;
                },
                () => {
                    this.opcodeEnabled = false;
                },
            );
        },
    };
</script>
