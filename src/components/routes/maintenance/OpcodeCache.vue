<template>
    <section class="maintenance" v-if="status.opcache_enabled">
        <div class="maintenance__inside">
            <figure class="maintenance__image"><img src="../../../assets/images/php-logo.svg" alt="" /></figure>

            <div class="maintenance__about">
                <h1>{{ $t('ui.maintenance.opcodeCache.title') }}</h1>
                <p>{{ $t('ui.maintenance.opcodeCache.description') }}</p>
            </div>

            <fieldset class="maintenance__actions">
                <loading-spinner class="maintenance__loader" v-if="loading "/>
                <button class="widget-button widget-button--primary widget-button--trash" v-else @click="execute">{{ $t('ui.maintenance.opcodeCache.button') }}</button>
            </fieldset>
        </div>
    </section>
</template>

<script>
    import LoadingSpinner from 'contao-package-list/src/components/fragments/LoadingSpinner';

    export default {
        components: { LoadingSpinner },

        data: () => ({
            opcodeEnabled: false,
            loading: false,
            status: { opcache_enabled: false },
        }),

        methods: {
            execute() {
                this.loading = true;

                this.$store.dispatch('server/opcache/delete', this.status.reset_token).then((status) => {
                    this.loading = false;
                    this.status = status;
                });
            },
        },

        mounted() {
            this.$store.dispatch('server/opcache/get').then(
                (status) => {
                    this.status = status;
                },
                () => {
                    this.status = { opcache_enabled: false };
                },
            );
        },
    };
</script>
