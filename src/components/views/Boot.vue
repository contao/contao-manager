<template>
    <boxed-layout :wide="true" slotClass="view-boot" v-if="!currentView">
        <header class="view-boot__header">
            <img src="../../assets/images/boot.svg" width="80" height="80" alt="Contao Logo" class="view-boot__icon">
            <h1 class="view-boot__headline">{{ 'ui.boot.headline' | translate }}</h1>
            <p class="view-boot__description">{{ 'ui.boot.description' | translate }}</p>
        </header>
        <main v-if="boot" class="view-boot__checks">

            <component v-for="(component, name) in views" :is="component" :current="false" :ready="canShow(name)" @result="result" @view="setView"/>

            <div class="clearfix"></div>
            <div class="view-boot__summary view-boot__summary--error" v-if="hasError">
                <svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <h1 class="view-boot__issue">{{ 'ui.boot.issue1' | translate }}</h1>
                <p class="view-boot__issue">{{ 'ui.boot.issue2' | translate }}</p>
            </div>
            <div class="view-boot__summary" v-if="canContinue">
                <button @click="finish" class="view-boot__continue widget-button widget-button--primary">{{ 'ui.boot.run' | translate }}</button>
            </div>
        </main>
        <main v-else class="view-boot__loading">
            <loader/>
        </main>
    </boxed-layout>

    <component v-else :is="currentView ? views[currentView] : null" :current="true" @view="setView"/>
</template>

<script>
    import views from '../../router/views';
    import routes from '../../router/routes';

    import BoxedLayout from '../layouts/Boxed';
    import Loader from '../fragments/Loader';
    import SelfUpdate from '../boot/SelfUpdate';
    import Config from '../boot/Config';
    import PhpWeb from '../boot/PhpWeb';
    import PhpCli from '../boot/PhpCli';
    import Composer from '../boot/Composer';
    import Contao from '../boot/Contao';

    export default {
        components: { BoxedLayout, Loader },

        data: () => ({
            currentView: null,
            boot: false,
            status: {},
        }),

        computed: {
            views() {
                if (this.$route.name === routes.oauth.name) {
                    return { PhpWeb, Config, PhpCli, SelfUpdate };
                }

                return { PhpWeb, Config, PhpCli, SelfUpdate, Composer, Contao };
            },

            hasError() {
                return Object.values(this.status).indexOf('error') !== -1;
            },

            canContinue() {
                return Object.values(this.status).indexOf(null) === -1
                    && Object.values(this.status).indexOf('error') === -1
                    && Object.values(this.status).indexOf('action') === -1;
            },

            shouldContinue() {
                return Object.values(this.status).indexOf(null) === -1
                    && Object.values(this.status).indexOf('error') === -1
                    && Object.values(this.status).indexOf('action') === -1
                    && Object.values(this.status).indexOf('warning') === -1;
            },
        },

        methods: {
            setView(view) {
                if (view === null) {
                    this.$store.dispatch('server/purgeCache');
                    this.setStatus(this.views);
                }

                this.currentView = view;
            },

            finish() {
                window.localStorage.setItem('contao_manager_booted', '1');
                this.$store.commit('setView', views.READY);
            },

            result(name, state) {
                this.$set(this.status, name, state);
                this.update();
            },

            update() {
                const keys = Object.keys(this.status);
                for (let k = 0; k < keys.length; k += 1) {
                    if (this.status[keys[k]] === null) {
                        this.currentView = null;
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

            setStatus(components) {
                this.status = Object
                    .keys(components)
                    .reduce((status, key) => Object.assign(status, { [key]: null }), {})
                ;
            },
        },

        watch: {
            views(value) {
                this.setStatus(value);
            },

            shouldContinue(value) {
                if (value && (
                    window.localStorage.getItem('contao_manager_booted') === '1'
                    || this.$route.name === routes.oauth.name
                )) {
                    this.finish();
                }
            },
        },

        created() {
            this.$store.dispatch('server/purgeCache');
            this.$store.dispatch('tasks/reload').then(() => {
                this.boot = true;
            });
            this.setStatus(this.views);
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .view-boot {
        &__header {
            max-width: 280px;
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

        &__continue {
            clear: both;
            display: block;
            width: 220px;
            margin: 0 auto;
        }

        @include screen(960) {
            &__checks {
                margin: 0 80px 50px;
            }
        }
    }
</style>
