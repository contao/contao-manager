<template>
    <boxed-layout v-if="current" :wide="true" slotClass="contao-check">
        <header class="contao-check__header">
            <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" class="contao-check__icon" />
            <h1 class="contao-check__headline">{{ 'ui.system.contao.headline' | translate }}</h1>
            <p class="contao-check__description">{{ 'ui.system.contao.description' | translate }}</p>
            <p class="contao-check__version"><strong>{{ 'ui.system.contao.ltsTitle' | translate }}:</strong> {{ 'ui.system.contao.ltsText' | translate }}</p>
            <p class="contao-check__version"><strong>{{ 'ui.system.contao.latestTitle' | translate }}:</strong> {{ 'ui.system.contao.latestText' | translate }}</p>
            <p class="contao-check__version" v-html="$t('ui.system.contao.releaseplan')"></p>
        </header>

        <section class="contao-check__form">

            <fieldset class="contao-check__fields">
                <legend class="contao-check__fieldtitle">{{ 'ui.system.contao.formTitle' | translate }}</legend>
                <p class="contao-check__fielddesc">{{ 'ui.system.contao.formText' | translate }}</p>
                <select-menu name="version" :label="$t('ui.system.contao.version')" class="inline" v-model="version" :options="versions" :disabled="processing"></select-menu>
            </fieldset>

            <fieldset class="contao-check__fields">
                <button class="widget-button widget-button--primary" @click="install" :disabled="processing">
                    <span v-if="!processing">{{ 'ui.system.contao.install' | translate }}</span>
                    <loader v-else></loader>
                </button>
            </fieldset>

        </section>

    </boxed-layout>

    <boot-check v-else :progress="bootState" :title="$t('ui.system.contao.title')" :description="bootDescription">
        <button v-if="bootState === 'info'" @click="show" class="widget-button widget-button--primary">{{ 'ui.system.contao.start' | translate }}</button>
    </boot-check>
</template>

<script>
    import api from '../../api';

    import BootCheck from '../fragments/BootCheck';
    import BoxedLayout from '../layouts/Boxed';
    import TextField from '../widgets/TextField';
    import SelectMenu from '../widgets/SelectMenu';
    import Loader from '../fragments/Loader';

    export default {
        components: { BootCheck, BoxedLayout, TextField, SelectMenu, Loader },

        props: {
            current: Boolean,
        },

        data: () => ({
            bootState: 'loading',
            bootDescription: '',


            processing: false,
            version: '',
            versions: { '4.5.*': 'Contao 4.5 (Latest)', '4.4.*': 'Contao 4.4 (Long Term Support)' },

            result: null,
        }),

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
            this.bootDescription = this.$t('ui.system.running');

            api.system.contao().then((result) => {
                if (!result.version) {
                    this.bootState = 'info';
                    this.bootDescription = this.$t('ui.system.contao.empty');
                } else {
                    this.bootState = 'success';
                    this.bootDescription = this.$t('ui.system.contao.found', result);
                }
            }).catch((response) => {
                if (response.status === 503) {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.system.prerequisite');
                } else if (response.status === 500) {
                    this.bootState = 'error';
                    this.bootDescription = response.body.output;
                } else {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.system.error');
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
        }

        &__form {
            position: relative;
            max-width: 280px;
            margin: 0 auto 50px;

            input,
            select {
                margin: 5px 0 10px;
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

                .widget-text label,
                .widget-select label {
                    display: block;
                    float: left;
                    width: 120px;
                    padding-top: 15px;
                    font-weight: $font-weight-medium;
                }

                input[type=text],
                select {
                    width: 250px;
                }

                .widget-button {
                    width: 250px;
                    margin-left: 120px;
                }
            }
        }
    }
</style>
