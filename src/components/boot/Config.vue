<template>
    <boxed-layout v-if="current" :wide="true" slotClass="config-check">
        <header class="config-check__header">
            <img src="../../assets/images/server-config.svg" width="80" height="80" class="config-check__icon" alt="">
            <h1 class="config-check__headline">{{ $t('ui.server.config.title') }}</h1>
            <p class="config-check__description" v-html="$t('ui.server.config.description')"/>
        </header>

        <main class="config-check__form">
            <form @submit.prevent="save">
                <fieldset class="config-check__fields">
                    <legend class="config-check__fieldtitle">{{ $t('ui.server.config.formTitle') }}</legend>
                    <p class="config-check__fielddesc">{{ $t('ui.server.config.formText') }}</p>
                    <text-field name="php_cli" :label="$t('ui.server.config.cli')" :disabled="processing" :error="error" v-model="php_cli" />
                </fieldset>

                <fieldset class="config-check__fields">
                    <legend class="config-check__fieldtitle">{{ $t('ui.server.config.cloudTitle') }}</legend>
                    <p class="config-check__fielddesc">{{ $t('ui.server.config.cloudText') }}</p>

                    <div class="config-check__issues" v-if="cloudIssues && cloudIssues.length">
                        <p>{{ $t('ui.server.config.stateErrorCloud') }}</p>
                        <ul>
                            <li v-for="(issue,k) in cloudIssues" :key="k">{{ issue }}</li>
                        </ul>
                    </div>

                    <checkbox name="cloud" :label="$t('ui.server.config.cloud')" :disabled="processing" v-model="cloud"/>
                </fieldset>

                <fieldset class="config-check__fields">
                    <loading-button submit color="primary" :disabled="!php_cli" :loading="processing">{{ $t('ui.server.config.save') }}</loading-button>
                </fieldset>
            </form>
        </main>
    </boxed-layout>

    <boot-check v-else :progress="bootState" :title="$t('ui.server.config.title')" :description="bootDescription">
        <button class="widget-button widget-button--alert" v-if="bootState === 'error' || bootState === 'action'" @click="showConfiguration">{{ $t('ui.server.config.setup') }}</button>
        <button class="widget-button widget-button--edit" v-else-if="bootState !== 'loading'" @click="showConfiguration">{{ $t('ui.server.config.change') }}</button>
    </boot-check>
</template>

<script>
    import boot from '../../mixins/boot';

    import BootCheck from '../fragments/BootCheck';
    import BoxedLayout from '../layouts/Boxed';
    import TextField from '../widgets/TextField';
    import Checkbox from '../widgets/Checkbox';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        mixins: [boot],
        components: { BootCheck, BoxedLayout, TextField, Checkbox, LoadingButton },


        data: () => ({
            processing: false,
            error: '',

            php_cli: '',
            cloud: true,
            cloudIssues: [],
        }),

        methods: {
            boot() {
                this.bootDescription = this.$t('ui.server.running');

                this.$store.dispatch('server/config/get').then((result) => {
                    if (!result.php_cli) {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.config.stateErrorCli');
                    } else if (result.cloud.enabled && result.cloud.issues.length > 0) {
                        this.bootState = 'error';
                        this.bootDescription = this.$t('ui.server.config.stateErrorCloud');
                    } else {
                        this.bootState = 'success';
                        this.bootDescription = this.$t('ui.server.config.stateSuccess', { php_cli: result.php_cli });
                    }
                }).catch(() => {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.error');
                }).then(() => {
                    this.$emit('result', 'Config', this.bootState);
                });
            },

            showConfiguration() {
                this.$emit('view', 'Config');
            },

            save() {
                this.processing = true;
                this.error = '';

                const config = {
                    php_cli: this.php_cli,
                    cloud: this.cloud,
                };

                this.$store.dispatch('server/config/set', config).then(() => {
                    this.$emit('view', null);
                    this.processing = false;
                }).catch((problem) => {
                    if (problem.status === 400 && problem.error) {
                        this.error = problem.error;
                    }

                    this.processing = false;
                });
            },
        },

        mounted() {
            if (this.current) {
                this.$store.dispatch('server/config/get').then((result) => {
                    this.php_cli = result.php_cli;
                    this.cloud = result.cloud.enabled;
                    this.cloudIssues = result.cloud.issues;
                });
            }
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

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

            .widget-select,
            .widget-text {
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

        &__issues {
            margin-bottom: 1em;
            color: $red-button;

            p {
                font-weight: $font-weight-bold;
            }

            ul {
                margin: 0;
                padding: 0;
            }

            li {
                margin: .5em 0 0 25px;
                padding: 0;
            }
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

                .widget-select,
                .widget-text {
                    label {
                        display: block;
                        float: left;
                        width: 120px;
                        padding-top: 10px;
                        font-weight: $font-weight-medium;
                    }

                    select,
                    input[type=text] {
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
