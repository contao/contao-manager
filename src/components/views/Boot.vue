<template>
    <div ref="current" v-if="currentStep"></div>

    <boxed-layout :wide="true" slotClass="view-boot" v-else>
        <header class="view-boot__header">
            <img src="../../assets/images/boot.svg" width="80" height="80" alt="Contao Logo" class="view-boot__icon">
            <h1 class="view-boot__headline">{{ $t('ui.boot.headline') }}</h1>
            <p class="view-boot__description">{{ $t('ui.boot.description') }}</p>
        </header>
        <main v-if="tasksInitialized" class="view-boot__checks">

            <div ref="steps"></div>

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
            <loader/>
        </main>
    </boxed-layout>
</template>

<script>
    import Vue from 'vue';
    import { mapState } from 'vuex';

    import views from '../../router/views';
    import routes from '../../router/routes';

    import BoxedLayout from '../layouts/Boxed';
    import Loader from 'contao-package-list/src/components/fragments/Loader';
    import SelfUpdate from '../boot/SelfUpdate';
    import Config from '../boot/Config';
    import PhpWeb from '../boot/PhpWeb';
    import PhpCli from '../boot/PhpCli';
    import Composer from '../boot/Composer';
    import Contao from '../boot/Contao';
    import Database from '../boot/Database';

    export default {
        components: { BoxedLayout, Loader },

        data: () => ({
            steps: [],
            currentStep: null,
            status: {},
        }),

        computed: {
            ...mapState(['safeMode']),
            ...mapState('tasks', { tasksInitialized: 'initialized' }),

            hasError: vm => Object.values(vm.status).indexOf('error') !== -1,
            autoContinue: vm => (window.localStorage.getItem('contao_manager_booted') === '1'
                    && Object.values(vm.status).indexOf('error') === -1
                    && Object.values(vm.status).indexOf('action') === -1
                    && Object.values(vm.status).indexOf('warning') === -1
                ) || vm.$route.name === routes.oauth.name,

            canContinue: vm => Object.values(vm.status).indexOf(null) === -1
                    && Object.values(vm.status).indexOf('error') === -1
                    && Object.values(vm.status).indexOf('action') === -1,

            shouldContinue: vm => Object.values(vm.status).indexOf(null) === -1
                    && Object.values(vm.status).indexOf('error') === -1
                    && Object.values(vm.status).indexOf('action') === -1
                    && Object.values(vm.status).indexOf('warning') === -1,
        },

        methods: {
            async setView(view) {
                if (!view) {
                    this.currentStep = null;
                    this.$nextTick(() => {
                        if (!this.$refs.steps) {
                            return;
                        }

                        this.$refs.steps.innerHTML = '';

                        Object.keys(this.steps).forEach((key) => {
                            this.steps[key].current = false;
                            this.$refs.steps.append(this.steps[key].$el);
                        })
                    });
                    return;
                }

                Object.keys(this.steps).forEach((key) => {
                    this.steps[key].current = false;
                })

                this.currentStep = view;
                this.$nextTick(() => {
                    this.$refs.current.innerHTML = '';
                    this.$refs.current.append(this.steps[view].$el);
                    this.steps[view].current = true;
                })
            },

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
                this.$set(this.status, name, state);

                const keys = Object.keys(this.status);
                for (let k = 0; k < keys.length; k += 1) {
                    if (this.status[keys[k]] === null) {
                        this.steps[keys[k]].ready = this.canShow(keys[k]);
                        this.currentStep = null;
                        return;
                    }
                }
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

            initViews() {
                if (!this.$refs.steps) {
                    return;
                }

                this.$refs.steps.innerHTML = '';
                this.steps = {};
                let steps = { PhpWeb, Config, PhpCli, SelfUpdate, Composer, Contao, Database };

                if (this.$route.name === routes.oauth.name) {
                    steps = { PhpWeb, Config, PhpCli, SelfUpdate };
                }

                this.status = Object
                    .keys(steps)
                    .reduce((status, key) => Object.assign(status, { [key]: null }), {})
                ;

                Object.keys(steps).forEach((key) => {
                    const instance = new (Vue.extend(steps[key]))({
                        parent: this
                    });

                    instance.$mount();
                    instance.$on('result', this.result);
                    instance.$on('view', this.setView);
                    instance.ready = this.canShow(key);

                    this.steps[key] = instance;

                    this.$refs.steps.append(instance.$el);
                })
            },
        },

        watch: {
            $route() {
                this.initViews();
            },

            shouldContinue(value) {
                if (value && this.autoContinue) {
                    this.finish();
                }
            },
        },

        async mounted() {
            await this.$store.dispatch('reset');
            await this.$store.dispatch('tasks/init');
            this.initViews();
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .view-boot {
        &__header {
            margin-left: auto;
            margin-right: auto;
            padding: 40px 0;
            text-align: center;
        }

        &__icon {
            background: $contao-color;
            border-radius: 10px;
            padding:10px;
        }

        &__headline {
            margin-top: 15px;
            font-size: 36px;
            font-weight: $font-weight-light;
            line-height: 1;
        }

        &__description {
            margin: 0;
            font-weight: $font-weight-bold;
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
            margin: 0 20px 50px;

            .boot-check:nth-child(odd) {
                background: #f5f9fa;
            }
        }

        &__summary {
            margin: 50px 0 0;

            &--error svg {
                width: 100%;
                height: 40px;
                fill: $red-button;
            }
        }

        &__issue {
            max-width: 60%;
            margin: 10px auto;
            text-align: center;
            color: $red-button;
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

        @include screen(960) {
            &__checks {
                margin: 0 80px 50px;
            }
        }
    }
</style>
