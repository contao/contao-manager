<template>
    <boot-check :progress="bootState" :title="$t('ui.server.contao.title')" :description="bootDescription">
        <button class="widget-button widget-button--primary widget-button--run" :disabled="!isGranted('ROLE_INSTALL')" @click="setup" v-if="bootState === 'action'">{{ $t(`ui.server.contao.${(databaseProblem && !databaseAccessProblem) ? 'check' : 'setup'}`) }}</button>
        <button class="widget-button widget-button--alert" :disabled="!isGranted('ROLE_INSTALL')" @click="setup" v-if="bootState === 'warning'">{{ $t(`ui.server.contao.${(databaseProblem && !databaseAccessProblem) ? 'check' : 'setup'}`) }}</button>
        <button class="widget-button" :disabled="!isGranted('ROLE_INSTALL')" @click="setup" v-if="bootState === 'success' && databaseSupported">{{ $t('ui.server.contao.setup') }}</button>
    </boot-check>
</template>

<script>
    import views from '../../router/views';
    import boot from '../../mixins/boot';

    import BootCheck from '../fragments/BootCheck';
    import { mapGetters, mapState } from 'vuex';

    export default {
        mixins: [boot],
        components: { BootCheck },

        data: () => ({
            processing: false
        }),

        computed: {
            ...mapState('server/database', { databaseSupported: 'supported', databaseStatus: 'status' }),
            ...mapState('server/adminUser', { userSupported: 'supported', hasUser: 'hasUser' }),
            ...mapGetters('auth', ['isGranted']),
            ...mapGetters('server/database', { databaseProblem: 'hasError', databaseAccessProblem: 'accessProblem' }),
        },

        methods: {
            async boot() {
                this.bootState = 'loading';
                this.bootDescription = this.$t('ui.server.running');

                const response = await this.$store.dispatch('server/contao/get', false);
                const result = response.data;
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

                if (bootState === 'success' && this.isGranted('ROLE_UPDATE')) {
                    await Promise.all([
                        this.$store.dispatch('contao/install-tool/fetch', false),
                        this.$store.dispatch('server/database/get', false)
                    ])

                    if (this.databaseSupported) {
                        if (this.databaseAccessProblem) {
                            bootState = 'warning';
                            bootDescription += ` ${this.$t('ui.server.contao.connectionError')}`;
                        } else if (this.databaseProblem) {
                            bootState = 'warning';
                            bootDescription += ` ${this.$t('ui.server.contao.connectionProblem')}`;
                        } else if (this.isGranted('ROLE_INSTALL')) {
                            await this.$store.dispatch('server/adminUser/get', false)

                            if (this.userSupported && !this.hasUser) {
                                bootState = 'warning';
                                bootDescription += ` ${this.$t('ui.server.contao.missingUser')}`;
                            }
                        }
                    }
                }

                if (bootState === 'warning') {
                    this.$store.commit('setSafeMode', true);
                } else if (bootState === 'action') {
                    const composer = await this.$store.dispatch('server/composer/get');

                    if (composer.json.found && composer.json.valid) {
                        this.$store.commit('setSafeMode', true);
                    }
                }

                this.bootState = bootState;
                this.bootDescription = bootDescription;
                this.$emit('result', this.bootState);
            },

            async setup() {
                this.$store.commit('setSafeMode', false);
                const composer = await this.$store.dispatch('server/composer/get');

                if (!composer.json.found) {
                    this.$store.commit('setup', 0);
                } else if (this.databaseAccessProblem) {
                    this.$store.commit('setup', 3);
                } else if (this.databaseProblem) {
                    this.$store.commit('setView', views.MIGRATION);
                } else if (!this.hasUser) {
                    this.$store.commit('setup', this.hasUser === null ? 3 : 4);
                } else {
                    this.$store.commit('setup', 0);
                }
            }
        }
    };
</script>
