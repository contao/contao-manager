<template>
    <boxed-layout wide="1" slotClass="view-boot" v-if="!currentView">
        <header class="view-boot__header">
            <img src="../../assets/images/boot.svg" width="80" height="80" alt="Contao Logo" class="view-boot__icon">
            <h1 class="view-boot__headline">{{ 'ui.boot.headline' | translate }}</h1>
            <p class="view-boot__description">{{ 'ui.boot.description' | translate }}</p>
        </header>
        <main v-if="boot" class="view-boot__checks">

            <self-update @error="reportError" @success="reportSuccess" @view="setView"></self-update>
            <config @error="reportError" @success="reportSuccess" @view="setView" v-if="canShow('Config')"></config>
            <php-web @error="reportError" @success="reportSuccess" @view="setView" v-if="canShow('PhpWeb')"></php-web>
            <php-cli @error="reportError" @success="reportSuccess" @view="setView" v-if="canShow('PhpCli')"></php-cli>
            <composer @error="reportError" @success="reportSuccess" @view="setView" v-if="canShow('Composer')"></composer>
            <contao @error="reportError" @success="reportSuccess" @view="setView" v-if="canShow('Contao')"></contao>

            <div class="clearfix"></div>
            <div class="view-boot__summary">
                <h1 v-if="hasError" class="view-boot__issue">{{ 'ui.boot.issue1' | translate }}</h1>
                <p v-if="hasError" class="view-boot__issue">{{ 'ui.boot.issue2' | translate }}</p>
                <button v-if="canContinue" @click="finish" class="view-boot__continue widget-button widget-button--primary">{{ 'ui.boot.run' | translate }}</button>
            </div>

        </main>
    </boxed-layout>

    <component v-else v-bind:is="currentView" :current="true" @view="setView"></component>
</template>

<script>
    import routes from '../../router/routes';
    import views from '../../router/views';

    import BoxedLayout from '../layouts/Boxed';
    import SelfUpdate from '../boot/SelfUpdate';
    import Config from '../boot/Config';
    import PhpWeb from '../boot/PhpWeb';
    import PhpCli from '../boot/PhpCli';
    import Composer from '../boot/Composer';
    import Contao from '../boot/Contao';

    export default {
        components: { BoxedLayout, SelfUpdate, Config, PhpWeb, PhpCli, Composer, Contao },

        data: () => ({
            currentView: null,
            boot: false,

            status: {
                SelfUpdate: null,
                Config: null,
                PhpWeb: null,
                PhpCli: null,
                Composer: null,
                Contao: null,
            },
        }),

        computed: {
            hasError() {
                return Object.values(this.status).indexOf(false) !== -1;
            },

            canContinue() {
                return Object.values(this.status).indexOf(null) === -1
                    && Object.values(this.status).indexOf(false) === -1;
            },
        },

        methods: {
            setView(view) {
                if (view === null) {
                    this.status = {
                        SelfUpdate: null,
                        Config: null,
                        PhpWeb: null,
                        PhpCli: null,
                        Composer: null,
                        Contao: null,
                    };
                }

                this.currentView = view;
            },

            finish() {
                window.localStorage.setItem('contao_manager_booted', '1');
                this.$store.commit('setView', views.READY);
                this.$router.push(routes.packages);
            },

            reportError(name) {
                this.$set(this.status, name, false);
                this.update();
            },

            reportSuccess(name) {
                this.$set(this.status, name, true);
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

                    if (this.status[keys[k]] !== true) {
                        return false;
                    }
                }

                return false;
            },
        },

        watch: {
            canContinue(value) {
                if (value && window.localStorage.getItem('contao_manager_booted') === '1') {
                    this.finish();
                }
            },
        },

        created() {
            this.$store.dispatch('tasks/reload').then(() => {
                this.boot = true;
            });
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

        &__checks {
            margin: 0 20px;

            .boot-check:nth-child(odd) {
                background: #f5f9fa;
            }
        }

        &__summary {
            margin: 50px 0;
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
                margin: 0 80px;
            }
        }
    }
</style>
