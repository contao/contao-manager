<template>
    <section class="maintenance">
        <div class="maintenance__inside">
            <div class="maintenance__about">
                <h1>Application Cache</h1>
                <p>Rebuilding the application cache is required after modifying any of the configuration files.</p>
            </div>

            <fieldset class="maintenance__config">
                <select-menu name="environment" v-model="env" :options="{ 'prod': 'Production Environment', 'dev': 'Development Environment' }"/>
            </fieldset>

            <fieldset class="maintenance__actions">
                <button class="widget-button widget-button--primary widget-button--update" @click="execute">{{ 'ui.maintenance.rebuild-cache' | translate }}</button>
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
