<template>

    <div>
        <header class="setup__header">
            <img src="../../assets/images/create-project.svg" width="80" height="80" alt="" class="setup__icon" />
            <h1 class="setup__headline">{{ $t('ui.setup.create-project.headline') }}</h1>
            <i18n tag="p" path="ui.setup.create-project.description" class="setup__description">
                <template #semver><a href="https://semver.org" target="_blank" rel="noreferrer noopener">{{ $t('ui.setup.create-project.semver') }}</a></template>
            </i18n>

            <ul class="setup__versions">
                <template v-for="version in versions">
                    <li class="setup__version" :key="version.value" v-if="!version.disabled">
                        <strong>{{ version.label }}</strong><br>
                        {{ version.description }}
                    </li>
                    <li class="setup__version" :key="version.value" v-else>
                        <strong>{{ version.label }}</strong><br>
                        <span class="setup__version--warning">{{ version.problem }}</span>
                    </li>
                </template>
            </ul>

            <i18n tag="p" path="ui.setup.create-project.releaseplan" class="setup__releaseplan">
                <template #contaoReleasePlan><a :href="`https://to.contao.org/release-plan?lang=${$i18n.locale}`" target="_blank" rel="noreferrer noopener">{{ $t('ui.setup.create-project.releaseplanLink') }}</a></template>
            </i18n>
        </header>

        <main class="setup__form setup__form--center" v-if="!!contaoVersion">
            <div class="setup__fields setup__fields--center">
                <svg class="setup__check" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" /></svg>
                <p class="setup__fielddesc">Contao {{ contaoVersion }} is successfully installed on the server. Continue to set up your database or launch the Contao Manager to install a different version.</p>
            </div>
            <div class="setup__field setup__fields--center">
                <button class="widget-button widget-button--inline" @click="launch">{{ $t('ui.setup.manager') }}</button>
                <button class="widget-button widget-button--inline widget-button--primary" @click="$emit('continue')">{{ $t('ui.setup.continue') }}</button>
            </div>
        </main>

        <main class="setup__form" v-else>

            <div class="setup__fields">
                <h2 class="setup__fieldtitle">{{ $t('ui.setup.create-project.formTitle') }}</h2>
                <p class="setup__fielddesc">{{ $t('ui.setup.create-project.formText') }}</p>
                <select-menu name="version" :label="$t('ui.setup.create-project.version')" :options="versions" :disabled="processing" v-model="version"/>
                <select-menu name="coreOnly" :label="$t('ui.setup.create-project.coreOnly')" :options="packages" :disabled="processing" v-model="coreOnly"/>
                <p class="setup__core-features"><a :href="`https://to.contao.org/core-extensions?lang=${$i18n.locale}`" target="_blank" rel="noreferrer noopener">{{ $t('ui.setup.create-project.coreOnlyFeatures') }}</a></p>
                <checkbox name="noUpdate" :label="$t('ui.setup.create-project.noUpdate')" :disabled="processing" v-model="noUpdate"/>
            </div>

            <div class="setup__fields">
                <loading-button color="primary" icon="run" :loading="processing" @click="install">{{ $t('ui.setup.create-project.install') }}</loading-button>
            </div>

        </main>
    </div>

</template>

<script>
import { mapState } from 'vuex';

import views from '../../router/views';

import SelectMenu from '../widgets/SelectMenu';
import Checkbox from '../widgets/Checkbox';
import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

export default {
        components: { SelectMenu, Checkbox, LoadingButton },

        data: () => ({
            processing: false,
            isWeb: true,

            version: '',
            coreOnly: 'no',
            noUpdate: false,
        }),

        computed: {
            ...mapState('tasks', { taskStatus: 'status' }),
            ...mapState('server/php-web', ['phpVersionId', 'phpVersion']),
            ...mapState('server/contao', ['contaoVersion']),

            versions() {
                const versions = [];

                versions.push({
                    value: '5.1',
                    label: `Contao 5.1 (${this.$t('ui.setup.create-project.latestTitle')})`,
                    disabled: this.phpVersionId < 80100,
                    description: this.$t('ui.setup.create-project.latestQ3', { year: '2023' }),
                    problem: this.$t('ui.setup.create-project.requiresPHP', { version: '8.1.0', current: this.phpVersion }),
                });

                versions.push({
                    value: '4.13',
                    label: `Contao 4.13 (${this.$t('ui.setup.create-project.ltsTitle')})`,
                    disabled: this.phpVersionId < 70400,
                    description: this.$t('ui.setup.create-project.ltsText', { year: '2025' }),
                    problem: this.$t('ui.setup.create-project.requiresPHP', { version: '7.4.0', current: this.phpVersion }),
                });

                if (this.phpVersionId < 70400) {
                    versions.push({
                        value: '4.9',
                        label: `Contao 4.9 (${ this.$t('ui.setup.create-project.ltsTitle') })`,
                        disabled: !this.isWeb,
                        description: this.$t('ui.setup.create-project.pltsText', { year: '2023' }),
                        problem: this.$t('ui.setup.create-project.requiresDocroot', { folder: 'web', }),
                    });
                }

                return versions;
            },

            packages: vm => ([
                {
                    value: 'no',
                    label: vm.$t('ui.setup.create-project.coreOnlyNo'),
                },
                {
                    value: 'yes',
                    label: vm.$t('ui.setup.create-project.coreOnlyYes'),
                }
            ]),
        },

        methods: {
            async install() {
                this.processing = true;

                await this.$store.dispatch('contao/install', {
                    version: this.version,
                    coreOnly: this.coreOnly === 'yes',
                    noUpdate: this.noUpdate,
                });

                if (this.taskStatus !== 'complete') {
                    return;
                }

                this.$store.commit('tasks/setDeleting', true);

                if (this.noUpdate) {
                    this.$store.commit('setSafeMode', true);
                    this.$store.commit('setView', views.READY);
                } else {
                    this.isWeb = (await this.$store.dispatch('server/contao/get', false)).body.public_dir === 'web';

                    await Promise.all([
                        this.$store.dispatch('contao/install-tool/fetch', false),
                        this.$store.dispatch('server/database/get', false)
                    ]);

                    this.$store.commit('setup', 3);
                }

                this.$store.dispatch('tasks/deleteCurrent');
            },

            launch() {
                this.$store.commit('setView', views.READY);
            },
        },

        async mounted() {
            this.isWeb = (await this.$store.dispatch('server/contao/get')).body.public_dir === 'web';
        }
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .setup {
        &__versions {
            margin: 0;
            padding: 0 0 0 15px;
        }

        &__version {
            margin: .5em 0;
            text-align: left;

            &--warning {
                color: $red-button;
            }
        }

        &__releaseplan {
            margin-top: 1.5em;
        }

        &__core-features {
            margin: 5px 0 0 15px;
            font-size: 12px;
        }

        @include screen(960) {
            &__core-features {
                margin-left: 135px;
            }
        }
    }
</style>
