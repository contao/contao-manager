<template>
    <boxed-layout :wide="true" slotClass="config-check">
        <header class="config-check__header">
            <img src="../../assets/images/server-config.svg" width="80" height="80" class="config-check__icon" alt="">
            <h1 class="config-check__headline">{{ $t('ui.server.config.title') }}</h1>
            <p class="config-check__description">{{ $t('ui.server.config.description') }}</p>
        </header>

        <main class="config-check__form">
            <form @submit.prevent="save">
                <fieldset class="config-check__fields">
                    <legend class="config-check__fieldtitle">{{ $t('ui.server.config.formTitle') }}</legend>
                    <p class="config-check__fielddesc">{{ $t('ui.server.config.formText') }}</p>
                    <text-field
                        ref="cli"
                        name="php_cli" :label="$t('ui.server.config.cli')"
                        required :disabled="processing" :error="error"
                        v-model="php_cli"
                    />
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

                    <check-box name="cloud" :label="$t('ui.server.config.cloud')" :disabled="processing" v-model="cloud"/>
                </fieldset>

                <loading-button submit color="primary" :disabled="!php_cli" :loading="processing">{{ $t('ui.server.config.save') }}</loading-button>
                <button type="button" class="widget-button" :disabled="processing" @click="cancel">{{ $t('ui.server.config.cancel') }}</button>
            </form>
        </main>
    </boxed-layout>
</template>

<script>
import BoxedLayout from '../layouts/BoxedLayout';
import TextField from '../widgets/TextField';
import CheckBox from '../widgets/CheckBox';
import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
import views from '../../router/views';

export default {
        components: { BoxedLayout, TextField, CheckBox, LoadingButton },

        data: () => ({
            processing: true,
            error: '',

            php_cli: '',
            cloud: true,
            cloudIssues: [],
        }),

        methods: {
            async save() {
                this.processing = true;
                this.error = '';

                const config = {
                    php_cli: this.php_cli,
                    cloud: this.cloud,
                };

                const data = await this.$store.dispatch('server/config/set', config);

                if (data.status === 400 && data.detail) {
                    this.error = data.detail;
                    this.processing = false;
                    setTimeout(() => {
                        this.$refs.cli.focus();
                    }, 0)
                } else {
                    window.location.reload(true);
                }
            },

            cancel() {
                this.$store.commit('setView', views.BOOT);
            }
        },

        async mounted() {
            const result = await this.$store.dispatch('server/config/get');

            this.php_cli = result.php_cli;
            this.cloud = result.cloud.enabled;
            this.cloudIssues = result.cloud.issues;

            this.processing = false;
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.config-check {
    &__header {
        max-width: 280px;
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
        margin-top: 20px;
        margin-bottom: 25px;
        font-size: 36px;
        font-weight: defaults.$font-weight-light;
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
                font-weight: defaults.$font-weight-medium;
            }
        }
    }

    &__fields {
        margin-bottom: 2em;
    }

    &__fieldtitle {
        margin-bottom: .5em;
        font-size: 18px;
        font-weight: defaults.$font-weight-bold;
        line-height: 30px;
    }

    &__fielddesc {
        margin-bottom: 1em;
    }

    &__issues {
        margin-bottom: 1em;
        color: var(--btn-alert);

        p {
            font-weight: defaults.$font-weight-bold;
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

    .widget-button {
        margin-bottom: .5em;
    }

    @include defaults.screen(960) {
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
                    font-weight: defaults.$font-weight-medium;
                }
            }
        }
    }
}
</style>
