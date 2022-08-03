<template>
    <boot-check :progress="bootState" :title="$t('ui.server.contao.title')" :description="bootDescription">
        <button v-if="bootState === 'warning'" @click="setup" class="widget-button widget-button--alert widget-button--run">{{ $t('ui.server.contao.setup') }}</button>
        <button v-if="bootState === 'success' && !hasInstallTool" @click="setup" class="widget-button">{{ $t('ui.server.contao.setup') }}</button>
    </boot-check>
</template>

<script>
    import views from '../../router/views';
    import boot from '../../mixins/boot';

    import BootCheck from '../fragments/BootCheck';
    import { mapState } from 'vuex';

    export default {
        mixins: [boot],
        components: { BootCheck },

        data: () => ({
            processing: false
        }),

        computed: {
            ...mapState('server/database', { databaseStatus: 'status' }),
            ...mapState('server/adminUser', ['hasUser']),
            ...mapState('contao/install-tool', { hasInstallTool: 'isSupported' }),
        },

        methods: {
            async boot() {
                this.bootState = 'loading';
                this.bootDescription = this.$t('ui.server.running');

                const response = await this.$store.dispatch('server/contao/get', false);
                const result = response.body;
                let bootState = this.bootState;
                let bootDescription = this.bootDescription;

                if (response.status === 200) {
                    if (!result.version) {
                        bootState = 'action';
                        bootDescription = this.$t('ui.server.contao.empty');
                    } else if (!result.supported) {
                        bootState = 'error';
                        bootDescription = this.$t('ui.server.contao.old', result);
                    } else {
                        bootState = 'success';
                        bootDescription = this.$t('ui.server.contao.found', { version: result.version, api: result.api.version });
                    }
                } else if (response.status === 503) {
                    bootState = 'error';
                    bootDescription = this.$t('ui.server.prerequisite');
                } else if (response.status === 502) {
                    window.localStorage.removeItem('contao_manager_booted');
                    this.$store.commit('setView', views.RECOVERY);
                } else {
                    bootState = 'error';
                    bootDescription = this.$t('ui.server.error');
                }

                if (bootState === 'success') {
                    await Promise.all([
                        this.$store.dispatch('contao/install-tool/fetch', false),
                        this.$store.dispatch('server/database/get', false)
                    ])

                    if (!this.hasInstallTool) {
                        await this.$store.dispatch('server/adminUser/get', false)

                        if (this.databaseStatus?.type === 'error') {
                            bootState = 'warning';
                            bootDescription += ` ${this.$t('ui.server.contao.connectionError')}`;
                        } else if (!this.hasUser) {
                            bootState = 'warning';
                            bootDescription += ` ${this.$t('ui.server.contao.missingUser')}`;
                        }
                    }
                }

                if (bootState === 'warning') {
                    this.$store.commit('setSafeMode', true);
                } else if (bootState === 'action') {
                    const composer = await this.$store.dispatch('server/composer/get');

                    if (!composer.json.found) {
                        this.$store.commit('setup', 0);
                    } else if (composer.json.valid) {
                        this.$store.commit('setSafeMode', true);
                    }
                }

                this.bootState = bootState;
                this.bootDescription = bootDescription;
                this.$emit('result', 'Contao', this.bootState);
            },

            setup() {
                this.$store.commit('setSafeMode', false);

                if (this.databaseStatus?.type === 'error') {
                    this.$store.commit('setup', 3);
                } else if (!this.hasUser) {
                    this.$store.commit('setup', 4);
                } else {
                    this.$store.commit('setup', 0);
                }
            }
        }
    };
</script>
