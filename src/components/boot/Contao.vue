<template>
    <boxed-layout v-if="current" :wide="true" slotClass="contao-check">

        <template v-if="!isEmpty || !isWeb">
            <header class="contao-check__header">
                <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" class="contao-check__icon" />
                <h1 class="contao-check__headline">{{ $t('ui.server.docroot.headline') }}</h1>
                <p class="contao-check__warning">{{ $t('ui.server.docroot.warning') }}</p>
                <p class="contao-check__description">{{ $t('ui.server.docroot.description1') }}</p>
                <p class="contao-check__description">{{ $t('ui.server.docroot.description2') }}</p>
                <a class="widget-button widget-button--inline widget-button--info widget-button--link" href="#" target="_blank">{{ $t('ui.server.docroot.documentation') }}</a>
            </header>

            <transition name="animate-flip" type="transition" mode="out-in">
                <section class="contao-check__form contao-check__form--center" v-if="directoryUpdated" v-bind:key="'front'">
                    <div class="contao-check__fields">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" /></svg>
                        <p class="contao-check__fielddesc">{{ $t('ui.server.docroot.confirmation') }}</p>
                        <dl class="contao-check__directories">
                            <dt>{{ $t('ui.server.docroot.currentRoot') }}</dt>
                            <dd>{{ projectDir }}</dd>
                            <dt>{{ $t('ui.server.docroot.newRoot') }}</dt>
                            <dd v-if="isEmpty">{{ projectDir }}<span>/web</span></dd>
                            <dd v-else>{{ projectDir }}<span>/{{ directory }}/web</span></dd>
                        </dl>
                    </div>
                    <div class="contao-check__fields contao-check__fields--center">
                        <loading-button inline :href="currentHref" :loading="processing" color="primary" icon="update" @click="reload">{{ $t('ui.server.docroot.reload') }}</loading-button>
                    </div>
                </section>
                <section class="contao-check__form contao-check__form--center" v-else v-bind:key="'back'">
                    <img src="../../assets/images/button-update.svg" class="invisible" alt=""> <!-- prefetch the update icon for the confirmation page -->
                    <div class="contao-check__fields">
                        <h2 class="contao-check__fieldtitle">{{ $t('ui.server.docroot.formTitle') }}</h2>
                        <p class="contao-check__fielddesc">{{ $t('ui.server.docroot.formText1') }} <u>{{ $t('ui.server.docroot.formText2') }}</u></p>
                        <text-field ref="directory" name="directory" :label="$t('ui.server.docroot.directory')" v-model="directory" :error="directoryError" v-if="!isEmpty"/>
                        <dl class="contao-check__directories">
                            <dt>{{ $t('ui.server.docroot.currentRoot') }}</dt>
                            <dd>{{ projectDir }}</dd>
                            <dt>{{ $t('ui.server.docroot.newRoot') }}</dt>
                            <dd v-if="isEmpty">{{ projectDir }}<span>/web</span></dd>
                            <dd v-else>{{ projectDir }}<span>/{{ directory }}/web</span></dd>
                        </dl>
                        <checkbox name="autoconfig" :label="$t('ui.server.docroot.autoconfig')" :disabled="processing" v-model="autoconfig"/>
                    </div>
                    <div class="contao-check__fields contao-check__fields--center">
                        <loading-button inline color="primary" icon="run" :loading="processing" :disabled="!autoconfig || !!directoryError" @click="setupDocroot">{{ $t('ui.server.docroot.finish') }}</loading-button>
                    </div>
                </section>
            </transition>
        </template>

        <template v-else>
            <header class="contao-check__header">
                <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" class="contao-check__icon" />
                <h1 class="contao-check__headline">{{ $t('ui.server.contao.headline') }}</h1>
                <p class="contao-check__description">{{ $t('ui.server.contao.description') }}</p>
                <p class="contao-check__version"><strong>{{ $t('ui.server.contao.ltsTitle') }}:</strong> {{ $t('ui.server.contao.ltsText') }}</p>
                <p class="contao-check__version" v-if="supportsLatest"><strong>{{ $t('ui.server.contao.latestTitle') }}:</strong> {{ $t('ui.server.contao.latestText') }}</p>
                <p class="contao-check__version" v-else><span class="contao-check__version--unavailable"><strong>{{ $t('ui.server.contao.latestTitle') }}:</strong> {{ $t('ui.server.contao.latestText') }}</span>&nbsp;<span class="contao-check__version--warning">{{ $t('ui.server.contao.noLatest', { version: '7.2' }) }}</span></p>
                <i18n tag="p" path="ui.server.contao.releaseplan">
                    <template #contaoReleasePlan><a :href="`https://to.contao.org/release-plan?lang=${$i18n.locale}`" target="_blank">{{ $t('ui.server.contao.releaseplanLink') }}</a></template>
                </i18n>
            </header>

            <section class="contao-check__form">

                <div class="contao-check__fields">
                    <h2 class="contao-check__fieldtitle">{{ $t('ui.server.contao.formTitle') }}</h2>
                    <p class="contao-check__fielddesc">{{ $t('ui.server.contao.formText') }}</p>
                    <select-menu name="version" :label="$t('ui.server.contao.version')" :options="versions" :disabled="processing" v-model="version"/>
                    <select-menu name="coreOnly" :label="$t('ui.server.contao.coreOnly')" :options="packages" :disabled="processing" v-model="coreOnly"/>
                    <checkbox name="noUpdate" :label="$t('ui.server.contao.noUpdate')" :disabled="processing" v-model="noUpdate"/>
                </div>

                <div class="contao-check__fields">
                    <loading-button color="primary" icon="run" :loading="processing" @click="install">{{ $t('ui.server.contao.install') }}</loading-button>
                </div>

            </section>
        </template>
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
    import TextField from '../widgets/TextField';

    let result;

    export default {
        mixins: [boot],
        components: { Checkbox, BootCheck, BoxedLayout, SelectMenu, TextField, LoadingButton },

        data: () => ({
            processing: false,
            supportsLatest: true,
            version: '',
            coreOnly: 'no',
            noUpdate: false,

            isEmpty: true,
            isWeb: true,
            projectDir: null,
            autoconfig: false,
            directory: '',
            directoryExists: false,
            directoryUpdated: false,
        }),

        computed: {
            directoryError: vm => vm.directoryExists ? vm.$t('ui.server.docroot.directoryExists') : (vm.directory ? '' : vm.$t('ui.server.docroot.directoryInvalid')),
            currentHref: () => window.location.href,

            versions() {
                if (!this.supportsLatest) {
                    return {
                        '4.4': `Contao 4.4 (${this.$t('ui.server.contao.ltsTitle')})`,
                    };
                }

                return {
                    '4.10': `Contao 4.10 (${this.$t('ui.server.contao.latestTitle')})`,
                    '4.9': `Contao 4.9 (${this.$t('ui.server.contao.ltsTitle')})`,
                    '4.4': `Contao 4.4 (${this.$t('ui.server.contao.ltsTitle')})`,
                };
            },

            packages: vm => ({
                'no': vm.$t('ui.server.contao.coreOnlyNo'),
                'yes': vm.$t('ui.server.contao.coreOnlyYes'),
            }),
        },

        methods: {
            reload() {
                this.processing = true;
                window.location.reload()
            },

            async boot() {
                this.bootDescription = this.$t('ui.server.running');

                try {
                    result = await this.$store.dispatch('server/contao/get');

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
                        this.bootDescription = this.$t('ui.server.contao.found', { version: result.version, api: result.api.version });

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

            async setupDocroot() {
                this.processing = true;
                const response = await this.$store.dispatch('server/contao/documentRoot', this.directory);

                // The target directory exists
                if (response.status === 403) {
                    this.directoryExists = true;
                    this.processing = false;
                    this.$refs.directory.focus();
                    return;
                }

                this.processing = false;
                this.directoryUpdated = true;
            },
        },

        watch: {
            directory() {
                this.directoryExists = false;
            },
        },

        async mounted() {
            if (this.current) {
                const phpWeb = await this.$store.dispatch('server/php-web/get');

                if (phpWeb.version_id < 70200) {
                    this.supportsLatest = false;
                }
            }

            if (result) {
                this.projectDir = result.project_dir;
                this.isEmpty = result.is_empty;
                this.isWeb = result.is_web;
            }

            this.directory = location.hostname;
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

            .widget-button {
                margin-top: 1em;
            }
        }

        &__headline {
            margin-top: 20px;
            margin-bottom: 25px;
            font-size: 36px;
            font-weight: $font-weight-light;
            line-height: 1;
        }

        &__warning,
        &__description {
            margin: 1em 0;
            text-align: justify;
        }

        &__warning {
            color: $red-button;
            font-weight: $font-weight-bold;
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
            opacity: 1;

            svg {
                display: block;
                width: 100px;
                height: 100px;
                margin: 0 auto 2em;
                fill: $green-button;
            }

            .widget-select,
            .widget-text {
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

            &--center {
                text-align: center;
            }
        }

        &__fieldtitle {
            margin-bottom: .5em;
            font-size: 18px;
            font-weight: $font-weight-bold;
            line-height: 30px;
        }

        &__fielddesc {
            margin-bottom: 1em;
            text-align: justify;
        }

        &__directories {
            margin-top: 2em;

            > dt {
                margin-top: 1em;
                font-weight: $font-weight-bold;
            }

            > dd {
                margin: 0;
                word-break: break-all;

                span {
                    background-color: $highlight-color;
                    font-weight: $font-weight-medium;
                }
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
                margin: 0 50px;
                padding-bottom: 50px;

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
                    input {
                        width: 250px !important;
                    }
                }

                .widget-checkbox {
                    margin-left: 120px;
                }

                .widget-button {
                    width: 250px;
                    margin-left: 120px;
                }

                &--center {
                    .widget-checkbox,
                    .widget-button {
                        margin-left: 0;
                    }
                }
            }
        }
    }
</style>
