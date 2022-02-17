<template>
    <boxed-layout v-if="current" :wide="true" slotClass="contao-check">

        <template v-if="!isEmpty || (!isWeb && (!isPublic || !canUsePublicDir))">
            <header class="contao-check__header">
                <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" class="contao-check__icon" />
                <h1 class="contao-check__headline">{{ $t('ui.server.docroot.headline') }}</h1>
                <p class="contao-check__warning">{{ $t('ui.server.docroot.warning') }}</p>
                <i18n tag="p" path="ui.server.docroot.description1" class="contao-check__description">
                    <template #webDir><code>web</code></template>
                    <template #publicDir><code>public</code></template>
                </i18n>
                <p class="contao-check__description">{{ $t('ui.server.docroot.description2') }}</p>
                <a class="widget-button widget-button--inline widget-button--info widget-button--link" href="https://to.contao.org/webroot" target="_blank">{{ $t('ui.server.docroot.documentation') }}</a>
            </header>

            <transition name="animate-flip" type="transition" mode="out-in">
                <section class="contao-check__form contao-check__form--center" v-if="directoryUpdated" v-bind:key="'front'">
                    <div class="contao-check__fields">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" /></svg>
                        <p class="contao-check__fielddesc">{{ $t('ui.server.docroot.confirmation') }}</p>
                        <dl class="contao-check__directories">
                            <dt>{{ $t('ui.server.docroot.currentRoot') }}</dt>
                            <dd v-if="isWeb">{{ projectDir }}/web</dd>
                            <dd v-else-if="isPublic">{{ projectDir }}/public</dd>
                            <dd v-else>{{ projectDir }}</dd>
                            <dt>{{ $t('ui.server.docroot.newRoot') }}</dt>
                            <dd v-if="isEmpty && canUsePublicDir && usePublicDir">{{ projectDir }}<span>/public</span></dd>
                            <dd v-else-if="isEmpty">{{ projectDir }}<span>/web</span></dd>
                            <dd v-else-if="canUsePublicDir && usePublicDir">{{ projectDir }}<span>/{{ directory }}/public</span></dd>
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
                        <radio-button name="usePublicDir" :options="publicDirOptions" allow-html v-model="usePublicDir" v-if="canUsePublicDir"/>
                        <dl class="contao-check__directories">
                            <dt>{{ $t('ui.server.docroot.currentRoot') }}</dt>
                            <dd v-if="isWeb">{{ projectDir }}/web</dd>
                            <dd v-else-if="isPublic">{{ projectDir }}/public</dd>
                            <dd v-else>{{ projectDir }}</dd>
                            <dt>{{ $t('ui.server.docroot.newRoot') }}</dt>
                            <dd v-if="isEmpty && canUsePublicDir && usePublicDir">{{ projectDir }}<span>/public</span></dd>
                            <dd v-else-if="isEmpty">{{ projectDir }}<span>/web</span></dd>
                            <dd v-else-if="canUsePublicDir && usePublicDir">{{ projectDir }}<span>/{{ directory }}/public</span></dd>
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
                <p class="contao-check__version" v-if="phpVersionId >= 70200"><strong>{{ $t('ui.server.contao.ltsTitle') }}:</strong> {{ $t('ui.server.contao.ltsText') }}</p>
                <p class="contao-check__version" v-else><span class="contao-check__version--unavailable"><strong>{{ $t('ui.server.contao.ltsTitle') }}:</strong> {{ $t('ui.server.contao.ltsText') }}</span>&nbsp;<span class="contao-check__version--warning">{{ $t('ui.server.contao.noLatest', { version: '7.2' }) }}</span></p>
                <p class="contao-check__version" v-if="phpVersionId >= 70400"><strong>{{ $t('ui.server.contao.latestTitle') }}:</strong> {{ $t('ui.server.contao.latestText') }}</p>
                <p class="contao-check__version" v-else><span class="contao-check__version--unavailable"><strong>{{ $t('ui.server.contao.latestTitle') }}:</strong> {{ $t('ui.server.contao.latestText') }}</span>&nbsp;<span class="contao-check__version--warning">{{ $t('ui.server.contao.noLatest', { version: '7.4' }) }}</span></p>
                <i18n tag="p" path="ui.server.contao.releaseplan">
                    <template #contaoReleasePlan><a :href="`https://to.contao.org/release-plan?lang=${$i18n.locale}`" target="_blank" rel="noreferrer noopener">{{ $t('ui.server.contao.releaseplanLink') }}</a></template>
                </i18n>
            </header>

            <section class="contao-check__form">

                <div class="contao-check__fields">
                    <h2 class="contao-check__fieldtitle">{{ $t('ui.server.contao.formTitle') }}</h2>
                    <p class="contao-check__fielddesc">{{ $t('ui.server.contao.formText') }}</p>
                    <select-menu name="version" :label="$t('ui.server.contao.version')" :options="versions" :disabled="processing" v-model="version"/>
                    <select-menu name="coreOnly" :label="$t('ui.server.contao.coreOnly')" :options="packages" :disabled="processing" v-model="coreOnly"/>
                    <p class="contao-check__core-features"><a :href="`https://to.contao.org/core-extensions?lang=${$i18n.locale}`" target="_blank" rel="noreferrer noopener">{{ $t('ui.server.contao.coreOnlyFeatures') }}</a></p>
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
    import RadioButton from '../widgets/RadioButton';

    let result;

    export default {
        mixins: [boot],
        components: { RadioButton, Checkbox, BootCheck, BoxedLayout, SelectMenu, TextField, LoadingButton },

        data: () => ({
            processing: false,
            phpVersionId: 70400,
            version: '',
            coreOnly: 'no',
            noUpdate: false,
            usePublicDir: false,

            isEmpty: true,
            isWeb: true,
            isPublic: false,
            projectDir: null,
            autoconfig: false,
            directory: '',
            directoryExists: false,
            directoryUpdated: false,
        }),

        computed: {
            directoryError: vm => vm.directoryExists ? vm.$t('ui.server.docroot.directoryExists') : (vm.directory ? '' : vm.$t('ui.server.docroot.directoryInvalid')),
            currentHref: () => window.location.href,

            publicDirOptions: vm => [
                { label: vm.$t('ui.server.contao.publicDir', { dir: '<code>web</code>', version: '4.9+' }), value: false },
                { label: vm.$t('ui.server.contao.publicDir', { dir: '<code>public</code>', version: '4.13+' }), value: true }
            ],

            canUsePublicDir: vm => vm.phpVersionId >= 70400,

            versions() {
                if (this.phpVersionId < 70200) {
                    return {
                        '4.4': 'Contao 4.4',
                    };
                }

                if (this.phpVersionId < 70400) {
                    return {
                        '4.9': `Contao 4.9 (${this.$t('ui.server.contao.ltsTitle')})`,
                    };
                }

                if (!this.isWeb) {
                    return {
                        '4.13': `Contao 4.13 (${this.$t('ui.server.contao.latestTitle')} + ${this.$t('ui.server.contao.ltsTitle')})`,
                    };
                }

                return {
                    '4.13': `Contao 4.13 (${this.$t('ui.server.contao.latestTitle')} + ${this.$t('ui.server.contao.ltsTitle')})`,
                    '4.9': `Contao 4.9 (${this.$t('ui.server.contao.ltsTitle')})`,
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

                const response = await this.$store.dispatch('server/contao/get');
                result = response.body;

                if (response.status === 200) {
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
                } else if (response.status === 503) {
                    this.bootState = 'error';
                    this.bootDescription = this.$t('ui.server.prerequisite');
                } else if (response.status === 502) {
                    window.localStorage.removeItem('contao_manager_booted');
                    this.$store.commit('setView', views.RECOVERY);
                } else {
                    this.bootState = 'action';
                    this.bootDescription = this.$t('ui.server.error');
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
                const response = await this.$store.dispatch('server/contao/documentRoot', {
                    directory: this.directory,
                    usePublicDir: this.canUsePublicDir && this.usePublicDir,
                });

                // The target directory exists
                if (response.status === 403) {
                    this.directoryExists = true;
                    this.processing = false;
                    this.$refs.directory.focus();
                    return;
                }

                this.processing = false;
                this.directoryUpdated = true;
                this.$store.commit('auth/resetCountdown');
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
                this.phpVersionId = phpWeb.version_id;
            }

            if (result) {
                this.projectDir = result.project_dir;
                this.isEmpty = result.is_empty;
                this.isWeb = result.public_dir === 'web';
                this.isPublic = result.public_dir === 'public';
                this.usePublicDir = result.public_dir === 'public';
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

            .widget-radio-button {
                margin-top: 20px;
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

        &__core-features {
            margin: 5px 0 0 15px;
            font-size: 12px;
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

            &__core-features {
                margin-left: 135px;
            }
        }
    }
</style>
