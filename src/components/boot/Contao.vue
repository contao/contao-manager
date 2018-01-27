<template>
    <boxed-layout v-if="current" :wide="true" slotClass="contao-check">
        <header class="contao-check__header">
            <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" class="contao-check__icon" />
            <h1 class="contao-check__headline">{{ 'ui.server.contao.headline' | translate }}</h1>
            <p class="contao-check__description">{{ 'ui.server.contao.description' | translate }}</p>
            <p class="contao-check__version"><strong>{{ 'ui.server.contao.ltsTitle' | translate }}:</strong> {{ 'ui.server.contao.ltsText' | translate }}</p>
            <p class="contao-check__version" v-if="supportsLatest"><strong>{{ 'ui.server.contao.latestTitle' | translate }}:</strong> {{ 'ui.server.contao.latestText' | translate }}</p>
            <p class="contao-check__version" v-else><span class="contao-check__version--unavailable"><strong>{{ 'ui.server.contao.latestTitle' | translate }}:</strong> {{ 'ui.server.contao.latestText' | translate }}</span> <span class="contao-check__version--warning">{{ 'ui.server.contao.noLatest' | translate }}</span></p>
            <p class="contao-check__version" v-html="$t('ui.server.contao.releaseplan')"></p>
        </header>

        <section class="contao-check__form">

            <fieldset class="contao-check__fields">
                <legend class="contao-check__fieldtitle">{{ 'ui.server.contao.formTitle' | translate }}</legend>
                <p class="contao-check__fielddesc">{{ 'ui.server.contao.formText' | translate }}</p>
                <select-menu name="version" :label="$t('ui.server.contao.version')" class="inline" v-model="version" :options="versions" :disabled="processing"/>
            </fieldset>

            <fieldset class="contao-check__fields">
                <button class="widget-button widget-button--primary widget-button--run" @click="install" :disabled="processing">
                    <span v-if="!processing">{{ 'ui.server.contao.install' | translate }}</span>
                    <loader v-else/>
                </button>
            </fieldset>

        </section>

    </boxed-layout>

    <boot-check v-else :progress="bootState" :title="$t('ui.server.contao.title')" :description="bootDescription">
        <button v-if="bootState === 'info'" @click="show" class="widget-button widget-button--primary widget-button--run">{{ 'ui.server.contao.setup' | translate }}</button>
    </boot-check>
</template>

<script>
    import BootCheck from '../fragments/BootCheck';
    import BoxedLayout from '../layouts/Boxed';
    import SelectMenu from '../widgets/SelectMenu';
    import Loader from '../fragments/Loader';

    export default {
        components: { BootCheck, BoxedLayout, SelectMenu, Loader },

        props: {
            current: Boolean,
        },

        data: () => ({
            bootState: 'loading',
            bootDescription: '',

            processing: false,
            supportsLatest: true,
            version: '',
        }),

        computed: {
            versions() {
                if (!this.supportsLatest) {
                    return {
                        '4.4.*': 'Contao 4.4 (Long Term Support)',
                    };
                }

                return {
                    '4.5.*': 'Contao 4.5 (Latest)',
                    '4.4.*': 'Contao 4.4 (Long Term Support)',
                };
            },
        },

        methods: {
            show() {
                this.$emit('view', 'Contao');
            },

            install() {
                this.processing = true;

                this.$store.dispatch('install', this.version).then(() => {
                    window.location.reload();
                });
            },
        },

        created() {
            this.bootDescription = this.$t('ui.server.running');

            this.$store.dispatch('server/contao/get').then((result) => {
                if (!result.version) {
                    this.bootState = 'info';
                    this.bootDescription = this.$t('ui.server.contao.empty');

                    this.$store.dispatch('server/php-web/get').then((phpWeb) => {
                        if (phpWeb.version_id < 70100) {
                            this.supportsLatest = false;
                        }
                    });
                } else {
                    this.bootState = 'success';
                    this.bootDescription = this.$t('ui.server.contao.found', result);

                    this.$store.commit('setVersions', result);
                }
            }).catch((response) => {
                if (response.status === 503) {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.prerequisite');
                } else if (response.status === 500) {
                    this.bootState = 'error';
                    this.bootDescription = response.body.output;
                } else {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.error');
                }
            }).then(() => {
                if (this.bootState === 'success') {
                    this.$emit('success', 'Contao');
                } else if (this.bootState === 'error') {
                    this.$emit('error', 'Contao');
                }
            });
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

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

                .widget-button {
                    width: 250px;
                    margin-left: 120px;
                }
            }
        }
    }
</style>
