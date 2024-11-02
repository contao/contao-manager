<template>
    <boxed-layout :wide="true" slotClass="view-boot">
        <header class="view-boot__header">
            <img src="../../assets/images/boot.svg" width="80" height="80" alt="" class="view-boot__icon">
            <h1 class="view-boot__headline">{{ $t('ui.boot.headline') }}</h1>
            <p class="view-boot__description">{{ $t('ui.boot.description') }}</p>
        </header>
        <main v-if="tasksInitialized" class="view-boot__checks">

            <div>
                <boot-php-web :ready="canShow('PhpWeb')" @result="(...args) => result('PhpWeb', ...args)"/>
                <boot-config :ready="canShow('Config')" @result="(...args) => result('Config', ...args)"/>
                <boot-php-cli :ready="canShow('PhpCli')" @result="(...args) => result('PhpCli', ...args)"/>
                <boot-self-update :ready="canShow('SelfUpdate')" @result="(...args) => result('SelfUpdate', ...args)"/>
                <boot-composer :ready="canShow('Composer')" @result="(...args) => result('Composer', ...args)" v-if="!isOAuth"/>
                <boot-contao :ready="canShow('Contao')" @result="(...args) => result('Contao', ...args)" v-if="!isOAuth"/>
            </div>

            <div class="clearfix"></div>
            <div class="view-boot__summary view-boot__summary--error" v-if="hasError">
                <svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <h1 class="view-boot__issue">{{ $t('ui.boot.issue1') }}</h1>
                <p class="view-boot__issue">{{ $t('ui.boot.issue2') }}</p>
                <button @click="runSafeMode" class="widget-button widget-button--warning view-boot__safeMode" v-if="safeMode">{{ $t('ui.boot.safeMode') }}</button>
            </div>
            <div class="view-boot__summary" v-else-if="safeMode">
                <button @click="runSafeMode" class="widget-button widget-button--warning view-boot__safeMode">{{ $t('ui.boot.safeMode') }}</button>
            </div>
            <div class="view-boot__summary" v-else-if="!autoContinue">
                <button @click="finish" class="widget-button widget-button--primary view-boot__continue" :disabled="!canContinue">{{ $t('ui.boot.run') }}</button>
            </div>
        </main>
        <main v-else class="view-boot__loading">
            <loading-spinner/>
        </main>
    </boxed-layout>
</template>

<script>
    import { mapState } from 'vuex';

    import views from '../../router/views';
    import routes from '../../router/routes';

    import BoxedLayout from '../layouts/BoxedLayout';
    import LoadingSpinner from 'contao-package-list/src/components/fragments/LoadingSpinner';
    import BootPhpWeb from '../boot/BootPhpWeb.vue';
    import BootConfig from '../boot/BootConfig.vue';
    import BootPhpCli from '../boot/BootPhpCli.vue';
    import BootSelfUpdate from '../boot/BootSelfUpdate.vue';
    import BootComposer from '../boot/BootComposer.vue';
    import BootContao from '../boot/BootContao.vue';

    export default {
        components: { BoxedLayout, LoadingSpinner, BootPhpWeb, BootConfig, BootPhpCli, BootSelfUpdate, BootComposer, BootContao },

        data: () => ({
            status: {
                PhpWeb: null,
                Config: null,
                PhpCli: null,
                SelfUpdate: null,
                Composer: null,
                Contao: null,
            },
        }),

        computed: {
            ...mapState(['safeMode']),
            ...mapState('tasks', { tasksInitialized: 'initialized' }),

            isOAuth: vm => vm.$route.name === routes.oauth.name,
            hasError: vm => Object.values(vm.status).indexOf('error') !== -1,
            autoContinue: vm => (window.localStorage.getItem('contao_manager_booted') === '1'
                    && Object.values(vm.status).indexOf('error') === -1
                    && Object.values(vm.status).indexOf('action') === -1
                    && Object.values(vm.status).indexOf('warning') === -1
                ) || vm.isOAuth,

            canContinue: vm => Object.values(vm.status).indexOf(null) === -1
                    && Object.values(vm.status).indexOf('error') === -1
                    && Object.values(vm.status).indexOf('action') === -1,

            shouldContinue: vm => Object.values(vm.status).indexOf(null) === -1
                    && Object.values(vm.status).indexOf('error') === -1
                    && Object.values(vm.status).indexOf('action') === -1
                    && Object.values(vm.status).indexOf('warning') === -1,
        },

        methods: {
            runSafeMode() {
                this.$store.commit('setSafeMode', true);
                this.$store.commit('setView', views.READY);
            },

            finish() {
                window.localStorage.setItem('contao_manager_booted', '1');
                this.$store.commit('setSafeMode', false);
                this.$store.commit('setView', views.READY);
            },

            result(name, state) {
                this.status[name] = state;
            },

            canShow(name) {
                const keys = Object.keys(this.status);
                for (let k = 0; k < keys.length; k += 1) {
                    if (keys[k] === name) {
                        return true;
                    }

                    if (this.status[keys[k]] === null || this.status[keys[k]] === 'error' || this.status[keys[k]] === 'action') {
                        return false;
                    }
                }

                return false;
            },
        },

        watch: {
            shouldContinue(value) {
                if (value && this.autoContinue) {
                    this.finish();
                }
            },
        },

        async mounted() {
            await this.$store.dispatch('reset');
            await this.$store.dispatch('tasks/init');
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.view-boot {
    &__header {
        margin-left: auto;
        margin-right: auto;
        padding: 40px 0;
        text-align: center;
    }

    &__icon {
        background: var(--contao);
        border-radius: 10px;
        padding:10px;
    }

    &__headline {
        margin-top: 15px;
        font-size: 36px;
        font-weight: defaults.$font-weight-light;
        line-height: 1;
    }

    &__description {
        margin: 0;
        font-weight: defaults.$font-weight-bold;
    }

    &__loading {
        width: 30px;
        margin: 0 auto 40px;

        .sk-circle {
            width: 30px;
            height: 30px;
        }
    }

    &__checks {
        margin: 0 16px 50px;

        .boot-check:nth-child(odd) {
            border-radius: var(--border-radius);
            background: var(--table-odd-bg);
        }
    }

    &__summary {
        margin: 50px 0 0;

        &--error svg {
            width: 100%;
            height: 40px;
            fill: var(--btn-alert);
        }
    }

    &__issue {
        max-width: 60%;
        margin: 10px auto;
        text-align: center;
        color: var(--btn-alert);
        line-height: 1.2em;
    }

    &__safeMode {
        clear: both;
        display: block !important;
        width: 220px !important;
        margin: 2em auto 0;
    }

    &__continue {
        clear: both;
        display: block !important;
        width: 220px !important;
        margin: 0 auto;
    }

    @include defaults.screen(960) {
        &__checks {
            margin: 0 80px 50px;
        }
    }
}
</style>
