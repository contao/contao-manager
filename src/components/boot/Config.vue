<template>
    <boxed-layout v-if="current" :wide="true" slotClass="config-check">
        <header class="config-check__header">
            <img src="../../assets/images/server-config.svg" width="80" height="80" class="config-check__icon">
            <h1 class="config-check__headline">{{ 'ui.system.config.title' | translate }}</h1>
            <p class="config-check__description" v-html="$t('ui.system.config.description')"></p>
        </header>

        <main class="config-check__form">
            <fieldset class="config-check__fields">
                <legend class="config-check__fieldtitle">{{ 'ui.system.config.formTitle' | translate }}</legend>
                <p class="config-check__fielddesc">{{ 'ui.system.config.formText' | translate }}</p>
                <p class="config-check__detected" v-if="detected && server !== 'custom'">{{ 'ui.system.config.detected' | translate }}</p>
                <select-menu name="server" :label="$t('Configuration')" class="inline" :disabled="processing" :error="errors.server" :options="servers" v-model="server" @input="detected = false"></select-menu>
            </fieldset>

            <fieldset v-if="showCustom" class="config-check__fields">
                <legend class="config-check__fieldtitle">{{ 'ui.system.config.customTitle' | translate }}</legend>
                <p class="config-check__fielddesc">{{ 'ui.system.config.customText' | translate }}</p>
                <p class="config-check__detected" v-if="detected">{{ 'ui.system.config.phpDetected' | translate }}</p>
                <text-field name="php_cli" :label="$t('ui.system.config.cli')" :disabled="processing" :error="errors.php_cli" v-model="php_cli" @enter="save"></text-field>
            </fieldset>

            <fieldset class="config-check__fields">
                <button class="widget-button widget-button--primary" @click="save" :disabled="!inputValid || processing">
                    <span v-if="!processing">{{ 'ui.system.config.save' | translate }}</span>
                    <loader v-else></loader>
                </button>
            </fieldset>

        </main>
    </boxed-layout>

    <boot-check v-else :progress="bootState" :title="$t('ui.system.config.title')" :description="bootDescription">
        <button class="widget-button widget-button--alert" v-if="bootState === 'error'" @click="showConfiguration">{{ 'ui.system.config.setup' | translate }}</button>
        <button class="widget-button" v-else-if="bootState !== 'loading'" @click="showConfiguration">{{ 'ui.system.config.change' | translate }}</button>
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
            bootResult: null,

            processing: false,
            detected: false,
            errors: {
                server: '',
                php_cli: '',
            },

            server: '',
            php_cli: '',
        }),

        computed: {
            servers() {
                if (!this.bootResult) {
                    return {};
                }

                const servers = {
                    '': this.$t('ui.system.config.blankOption'),
                };

                Object.keys(this.bootResult.configs).forEach((key) => {
                    servers[key] = this.bootResult.configs[key].name;
                });

                servers.custom = this.$t('ui.system.config.customOption');

                return servers;
            },

            showCustom() {
                return this.server === 'custom';
            },

            inputValid() {
                return this.server && (this.server !== 'custom' || this.php_cli);
            },
        },

        methods: {
            showConfiguration() {
                this.$emit('view', 'Config');
            },

            save() {
                this.processing = true;
                this.errors.server = '';
                this.errors.php_cli = '';

                api.system.setConfig(this.server, this.php_cli).then(() => {
                    this.$emit('view', null);
                    this.processing = false;
                }).catch((problem) => {
                    if (problem.status === 400 && problem.validation) {
                        problem.validation.forEach((error) => {
                            if (error.source === 'server' && !this.errors.server) {
                                this.errors.server = error.message;
                            } else if (error.source === 'php_cli' && !this.errors.php_cli) {
                                this.errors.php_cli = error.message;
                            }
                        });
                    }

                    this.processing = false;
                });
            },
        },

        created() {
            this.bootDescription = this.$t('ui.system.running');

            api.system.getConfig().then((result) => {
                this.bootResult = result;

                this.server = result.server;
                this.php_cli = result.php_cli;
                this.detected = result.detected;

                if (!result.server || result.detected || (result.server === 'custom' && !result.php_cli)) {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.system.config.stateError');
                } else if (result.server === 'custom') {
                    this.bootState = 'info';
                    this.bootDescription = this.$t('ui.system.config.stateCustom', result);
                } else {
                    const labels = Object.create({
                        server: result.configs[result.server].name,
                        php_cli: result.php_cli,
                    });

                    this.bootState = 'success';
                    this.bootDescription = this.$t('ui.system.config.stateSuccess', labels);
                }
            }).catch(() => {
                this.bootState = 'error';
                this.bootDescription = this.$t('ui.system.error');
            }).then(() => {
                if (this.bootState === 'error') {
                    this.$emit('error', 'Config');
                } else {
                    this.$emit('success', 'Config');
                }
            });
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .config-check {
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
            margin-top: 20px;
            margin-bottom: 25px;
            font-size: 36px;
            font-weight: $font-weight-light;
            line-height: 1;
        }

        &__description {
            text-align: justify;
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

        &__detected {
            margin-bottom: 1em;
            color: $green-button;
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
                margin: 20px 50px 0;
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
