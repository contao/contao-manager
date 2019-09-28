<template>
    <boxed-layout v-if="current" :wide="true" slotClass="contao-check">
        <header class="contao-check__header">
            <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" class="contao-check__icon" />
            <h1 class="contao-check__headline">{{ 'ui.server.contao.headline' | translate }}</h1>
            <p class="contao-check__description">{{ 'ui.server.contao.description' | translate }}</p>
            <p class="contao-check__version"><strong>{{ 'ui.server.contao.ltsTitle' | translate }}:</strong> {{ 'ui.server.contao.ltsText' | translate }}</p>
            <p class="contao-check__version"><strong>{{ 'ui.server.contao.latestTitle' | translate }}:</strong> {{ 'ui.server.contao.latestText' | translate }}</p>
            <p class="contao-check__version" v-html="$t('ui.server.contao.releaseplan')"></p>
        </header>

        <section class="contao-check__form">

            <fieldset class="contao-check__fields">
                <legend class="contao-check__fieldtitle">{{ 'ui.server.contao.formTitle' | translate }}</legend>
                <p class="contao-check__fielddesc">{{ 'ui.server.contao.formText' | translate }}</p>
                <select-menu name="version" :label="$t('ui.server.contao.version')" :options="versions" :disabled="processing" v-model="version"/>
                <select-menu name="coreOnly" :label="$t('ui.server.contao.coreOnly')" :options="packages" :disabled="processing" v-model="coreOnly"/>
                <checkbox name="noUpdate" :label="$t('ui.server.contao.noUpdate')" :disabled="processing" v-model="noUpdate"/>
            </fieldset>

            <fieldset class="contao-check__fields">
                <loading-button color="primary" icon="run" :loading="processing" @click="install">{{ $t('ui.server.contao.install') }}</loading-button>
            </fieldset>

        </section>

    </boxed-layout>

    <boot-check v-else :progress="bootState" :title="$t('ui.server.contao.title')" :description="bootDescription">
        <button v-if="bootState === 'action'" @click="show" class="widget-button widget-button--primary widget-button--run">{{ 'ui.server.contao.setup' | translate }}</button>
    </boot-check>
</template>

<script>
    import views from '../../router/views';
    import boot from '../../mixins/boot';

    import BootCheck from '../fragments/BootCheck';
    import BoxedLayout from '../layouts/Boxed';
    import SelectMenu from '../widgets/SelectMenu';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
    import Checkbox from '../widgets/Checkbox';

    export default {
        mixins: [boot],
        components: { Checkbox, BootCheck, BoxedLayout, SelectMenu, LoadingButton },

        data: () => ({
            processing: false,
            version: '',
            coreOnly: 'no',
            noUpdate: false,
        }),

        computed: {
            versions() {
                return {
                    '4.8': 'Contao 4.8 (Latest)',
                    '4.4': 'Contao 4.4 (Long Term Support)',
                };
            },

            packages: vm => ({
                'no': vm.$t('ui.server.contao.coreOnlyNo'),
                'yes': vm.$t('ui.server.contao.coreOnlyYes'),
            }),
        },

        methods: {
            async boot() {
                this.bootDescription = this.$t('ui.server.running');

                try {
                    const result = await this.$store.dispatch('server/contao/get');

                    if (!result.version) {
                        this.bootState = 'action';
                        this.bootDescription = this.$t('ui.server.contao.empty');
                    } else if (!result.supported) {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.contao.old', result);
                    } else {
                        const composerConfig = await this.$store.dispatch('config/composer/get');

                        if (!composerConfig || composerConfig.length === 0) {
                            this.$store.dispatch('config/composer/writeDefaults');
                        }

                        this.bootState = 'success';
                        this.bootDescription = this.$t('ui.server.contao.found', result);

                        this.$store.commit('setVersions', result);
                    }
                } catch (response) {
                    if (response.status === 503) {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.prerequisite');
                    } else if (response.status === 502) {
                        window.localStorage.removeItem('contao_manager_booted');
                        this.$store.commit('setView', views.RECOVERY);
                    } else {
                        this.bootState = 'action';
                        this.bootDescription = this.$t('ui.server.error');
                    }
                }

                this.$emit('result', 'Contao', this.bootState);
            },

            show() {
                this.$emit('view', 'Contao');
            },

            async install() {
                this.processing = true;

                await this.$store.dispatch('contao/install', {
                    version: this.version,
                    coreOnly: this.coreOnly === 'yes',
                    noUpdate: this.noUpdate,
                });

                if (this.noUpdate) {
                    await this.$store.dispatch('tasks/deleteCurrent');
                    this.$store.commit('setSafeMode', true);
                    this.$store.commit('setView', views.READY);
                    return;
                }

                window.location.reload();
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .contao-check {
        &__header {
            max-width: 280px;
            margin-left: auto;
            margin-right: auto;
            padding: 40px 0;
            text-align: center;
        }

        &__headline {
            margin-top: 20px;
            margin-bottom: 25px;
            font-size: 36px;
            font-weight: $font-weight-light;
            line-height: 1;
        }

        &__description {
            text-align: justify;
        }

        &__version {
            margin: .5em 0;
            text-align: left;

            &--unavailable {
                text-decoration: line-through;
            }

            &--warning {
                color: $red-button;
            }
        }

        &__form {
            position: relative;
            max-width: 280px;
            margin: 0 auto 50px;

            .widget-select {
                margin-top: 20px;

                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: $font-weight-medium;
                }
            }

            .widget-checkbox {
                margin-top: 20px;
                font-weight: $font-weight-medium;
            }
        }

        &__fields {
            margin-bottom: 2em;
        }

        &__fieldtitle {
            margin-bottom: .5em;
            font-size: 18px;
            font-weight: $font-weight-bold;
            line-height: 30px;
        }

        &__fielddesc {
            margin-bottom: 1em;
        }

        @include screen(960) {
            padding-top: 100px;

            &__header {
                float: left;
                width: 470px;
                max-width: none;
                padding: 0 60px 100px;
            }

            &__form {
                float: left;
                width: 370px;
                max-width: none;
                margin: 60px 50px 0;
                padding-bottom: 100px;

                .widget-select {
                    label {
                        display: block;
                        float: left;
                        width: 120px;
                        padding-top: 10px;
                        font-weight: $font-weight-medium;
                    }

                    select {
                        width: 250px;
                    }
                }

                .widget-checkbox {
                    margin-left: 120px;
                }

                .widget-button {
                    width: 250px;
                    margin-left: 120px;
                }
            }
        }
    }
</style>
