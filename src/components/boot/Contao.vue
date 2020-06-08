<template>
    <boxed-layout v-if="current" :wide="true" slotClass="contao-check">
        <header class="contao-check__header">
            <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" class="contao-check__icon" />
            <h1 class="contao-check__headline">{{ $t('ui.server.contao.headline') }}</h1>
            <p class="contao-check__description">{{ $t('ui.server.contao.description') }}</p>
            <p class="contao-check__version"><strong>{{ $t('ui.server.contao.ltsTitle') }}:</strong> {{ $t('ui.server.contao.ltsText') }}</p>
            <p class="contao-check__version" v-if="supportsLatest"><strong>{{ $t('ui.server.contao.latestTitle') }}:</strong> {{ $t('ui.server.contao.latestText') }}</p>
            <p class="contao-check__version" v-else><span class="contao-check__version--unavailable"><strong>{{ $t('ui.server.contao.latestTitle') }}:</strong> {{ $t('ui.server.contao.latestText') }}</span>&nbsp;<span class="contao-check__version--warning">{{ $t('ui.server.contao.noLatest', { version: '7.2' }) }}</span></p>
            <i18n tag="p" path="ui.server.contao.releaseplan">
                <a href="https://contao.org/en/release-plan.html" target="_blank">{{ $t('ui.server.contao.releaseplanLink') }}</a>
            </i18n>
        </header>

        <section class="contao-check__form">

            <fieldset class="contao-check__fields">
                <legend class="contao-check__fieldtitle">{{ $t('ui.server.contao.formTitle') }}</legend>
                <p class="contao-check__fielddesc">{{ $t('ui.server.contao.formText') }}</p>
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
        <button v-if="bootState === 'action'" @click="show" class="widget-button widget-button--primary widget-button--run">{{ $t('ui.server.contao.setup') }}</button>
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
            supportsLatest: true,
            version: '',
            coreOnly: 'no',
            noUpdate: false,
        }),

        computed: {
            versions() {
                if (!this.supportsLatest) {
                    return {
                        '4.4': `Contao 4.4 (${this.$t('ui.server.contao.ltsTitle')})`,
                    };
                }

                return {
                    '4.9': `Contao 4.9 (${this.$t('ui.server.contao.latestTitle')} / ${this.$t('ui.server.contao.ltsTitle')})`,
                    '4.4': `Contao 4.4 (${this.$t('ui.server.contao.ltsTitle')})`,
                };
            },

            packages: vm => ({
                'no': vm.$t('ui.server.contao.coreOnlyNo'),
                'yes': vm.$t('ui.server.contao.coreOnlyYes'),
            }),

            releasePlan() {
                switch (this.$i18n.locale) {
                    case 'de':
                        return 'https://contao.org/de/release-plan.html';

                    case 'es':
                        return 'https://contao.org/es/plan-de-publicacion.html';

                    case 'fr':
                        return 'https://contao.org/fr/plan-de-publication.html';

                    default:
                        return 'https://contao.org/en/release-plan.html';
                }
            }
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

        async mounted() {
            if (this.current) {
                const phpWeb = await this.$store.dispatch('server/php-web/get');

                if (phpWeb.version_id < 70200) {
                    this.supportsLatest = false;
                }
            }
        }
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
