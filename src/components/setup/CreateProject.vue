<template>
    <div>
        <file-upload
            name="package"
            ref="uploader"
            post-action="api/packages/uploads"
            :multiple="true"
            :drop="true"
            :drop-directory="false"
            :chunk-enabled="true"
            :chunk="{ action: 'api/packages/uploads' }"
            @input-file="uploadTheme"
            @input-filter="filterTheme"
        ></file-upload>

        <template v-if="theme">
            <header class="setup__header">
                <img class="setup__theme-image" :src="themeImage" :alt="theme.composerJson.name" v-if="themeImage" />
                <img src="../../assets/images/create-project.svg" width="80" height="80" alt="" class="setup__icon" v-else />
                <h1 class="setup__headline">{{ $t('ui.setup.create-project.headline') }}</h1>
                <p class="setup__description">{{ $t('ui.setup.create-project.theme.uploaded') }}</p>

                <div class="setup__version">
                    <strong>{{ $t('ui.setup.create-project.theme.packageName') }}:</strong>
                    {{ theme.composerJson.name }}
                </div>

                <div class="setup__version">
                    <strong>{{ $t('ui.setup.create-project.theme.version') }}:</strong>
                    {{ theme.composerJson.version }}
                </div>

                <div class="setup__version" v-if="theme.composerJson.authors || theme.authors">
                    <strong>{{ $t('ui.setup.create-project.theme.authors') }}: </strong>
                    <template v-if="theme.composerJson.authors">{{ theme.composerJson.authors.map((a) => a.name).join(', ') }}</template>
                    <template v-else>{{ theme.authors }}</template>
                </div>
                <div class="setup__version" v-if="theme.composerJson.license">
                    <strong>{{ $t('ui.package-details.license') }}:</strong>
                    {{ Array.isArray(theme.composerJson.license) ? theme.composerJson.license.join(', ') : theme.composerJson.license }}
                </div>
                <div class="setup__version" v-if="theme.composerJson.homepage">
                    <strong>{{ $t('ui.package.homepage') }}: </strong>
                    <a :href="theme.composerJson.homepage" target="_blank">{{ theme.composerJson.homepage }}</a>
                </div>

                <button class="widget-button widget-button--info widget-button--details" @click="themeDetails">{{ $t('ui.package.detailsButton') }}</button>
            </header>

            <main class="setup__form">
                <div class="setup__fields" v-if="theme.files">
                    <h2 class="setup__fieldtitle">{{ $t('ui.setup.create-project.themeTitle') }}</h2>
                    <p class="setup__fielddesc">{{ $t('ui.setup.create-project.themeDetails') }}</p>

                    <div class="setup__tabs">
                        <div class="setup__tab-controls">
                            <button class="setup__tab-control" :class="{ 'setup__tab-control--active': view === 'require' }" @click="view = 'require'">
                                {{ $tc('ui.setup.create-project.themeRequire', Object.keys(theme.composerJson.require).length) }}
                            </button>
                            <button class="setup__tab-control" :class="{ 'setup__tab-control--active': view === 'files' }" @click="view = 'files'">
                                {{ $tc('ui.setup.create-project.themeFiles', theme.files.length) }}
                            </button>
                        </div>
                        <div class="setup__tab" v-if="view === 'require'">
                            <table class="setup__requires">
                                <tbody>
                                    <!-- eslint-disable vue/no-v-for-template-key -->
                                    <template v-for="(version, name) in theme.composerJson.require" :key="name">
                                        <tr>
                                            <td>{{ name }}:</td>
                                            <td>{{ version }}</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="setup__tab setup__tab--files" v-if="view === 'files'">
                            <file-tree :files="themeFiles" />
                        </div>
                    </div>

                    <p class="setup__fielddesc setup__fielddesc--warning">{{ $t('ui.setup.create-project.themeWarning') }}</p>
                </div>

                <div class="setup__actions">
                    <loading-button color="primary" icon="run" :loading="processing" @click="installTheme">{{ $t('ui.setup.create-project.install') }}</loading-button>
                    <button class="widget-button" @click="cancelTheme" :disabled="processing">{{ $t('ui.setup.create-project.cancel') }}</button>
                </div>
            </main>
        </template>

        <template v-else>
            <header class="setup__header">
                <img src="../../assets/images/create-project.svg" width="80" height="80" alt="" class="setup__icon" />
                <h1 class="setup__headline">{{ $t('ui.setup.create-project.headline') }}</h1>
                <i18n-t tag="p" keypath="ui.setup.create-project.description" class="setup__description">
                    <template #semver>
                        <a href="https://semver.org" target="_blank" rel="noreferrer noopener">{{ $t('ui.setup.create-project.semver') }}</a>
                    </template>
                </i18n-t>

                <ul class="setup__versions">
                    <template v-for="version in versions">
                        <template v-if="version.description">
                            <li class="setup__version" :key="version.value" v-if="!version.disabled">
                                <strong>{{ version.label }}</strong>
                                <br />
                                {{ version.description }}
                            </li>
                            <li class="setup__version" :key="version.value" v-else>
                                <strong>{{ version.label }}</strong>
                                <br />
                                <span class="setup__version--warning">{{ version.problem }}</span>
                            </li>
                        </template>
                    </template>
                </ul>

                <i18n-t tag="p" keypath="ui.setup.create-project.releaseplan" class="setup__releaseplan">
                    <template #contaoReleasePlan>
                        <a :href="`https://to.contao.org/release-plan?lang=${$i18n.locale}`" target="_blank" rel="noreferrer noopener">{{
                            $t('ui.setup.create-project.releaseplanLink')
                        }}</a>
                    </template>
                </i18n-t>
            </header>

            <main class="setup__form" v-if="!!contaoVersion">
                <div class="setup__fields setup__fields--center">
                    <svg class="setup__check" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <path
                            d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z"
                        />
                    </svg>
                    <p class="setup__fielddesc">{{ $t('ui.setup.create-project.installed', { version: contaoVersion }) }}</p>
                </div>
                <div class="setup__actions setup__actions--center">
                    <button class="widget-button widget-button--inline" @click="launch">{{ $t('ui.setup.manager') }}</button>
                    <button class="widget-button widget-button--inline widget-button--primary" @click="$emit('continue')">{{ $t('ui.setup.continue') }}</button>
                </div>
            </main>

            <main class="setup__form" v-else>
                <div class="setup__fields">
                    <h2 class="setup__fieldtitle">{{ $t('ui.setup.create-project.formTitle') }}</h2>
                    <p class="setup__fielddesc setup__fielddesc--version">{{ $t('ui.setup.create-project.formText') }}</p>
                    <radio-button name="version" :options="versions" :disabled="processing" v-model="version" />

                    <div class="setup__theme" v-if="version === 'theme'">
                        <p>{{ $t('ui.setup.create-project.themeInstall') }}</p>
                        <i18n-t keypath="ui.setup.create-project.themeBuy" tag="p">
                            <template #store>
                                <a href="https://themes.contao.org" target="_blank">{{ $t('ui.setup.create-project.themeStore') }}</a>
                            </template>
                        </i18n-t>
                        <div v-show="$refs.uploader && $refs.uploader.dropActive" class="package-uploads__overlay">
                            <div>
                                <img src="../../assets/images/button-upload.svg" alt="" width="128" height="128" />
                                <p>{{ $t('ui.packages.uploadOverlay') }}</p>
                            </div>
                        </div>
                    </div>

                    <template v-else>
                        <check-box name="demo" :label="$t('ui.setup.create-project.demo')" :disabled="processing" v-model="demo">
                            <template #description>
                                <i18n-t tag="p" keypath="ui.setup.create-project.demoDescription">
                                    <template #store>
                                        <a href="https://themes.contao.org" target="_blank">{{ $t('ui.setup.create-project.themeStore') }}</a>
                                    </template>
                                </i18n-t>
                            </template>
                        </check-box>
                    </template>
                </div>

                <div class="setup__fields">
                    <template v-if="version === 'theme'">
                        <loading-button color="primary" icon="upload" :loading="processing" @click="openFileSelector">
                            {{ $t('ui.setup.create-project.themeUpload') }}
                        </loading-button>
                        <div class="setup__or">
                            <span>{{ $t('ui.setup.create-project.theme.or') }}</span>
                        </div>
                        <search-input :placeholder="$t('ui.setup.create-project.theme.search')" :disabled="processing" />
                    </template>
                    <button-group
                        color="primary"
                        icon="run"
                        :disabled="!version"
                        :loading="processing"
                        @click="() => install()"
                        :label="$t('ui.setup.create-project.install')"
                        v-else
                    >
                        <button class="widget-button" :disabled="!version || processing" @click="installCoreOnly" v-if="!demo">{{ $t('ui.setup.create-project.coreOnly') }}</button>
                        <button class="widget-button" :disabled="!version || processing" @click="installNoUpdate">{{ $t('ui.setup.create-project.noUpdate') }}</button>
                    </button-group>
                </div>
            </main>

            <div class="clearfix"></div>
            <div class="setup__themes" v-if="searching || results || offline">
                <loading-spinner v-if="searching && !results" class="setup__theme-search setup__theme-search--loader">
                    <p>{{ $t('ui.discover.loading') }}</p>
                </loading-spinner>

                <div v-else-if="offline" class="setup__theme-search setup__theme-search--offline">
                    <p>{{ $t('ui.discover.offline') }}</p>
                    <p>{{ $t('ui.discover.offlineExplain') }}</p>
                    <button class="widget-button widget-button--inline widget-button--update" @click="searchThemes">{{ $t('ui.discover.offlineButton') }}</button>
                </div>

                <div v-else-if="isSearching && results && !Object.keys(results).length" class="setup__theme-search setup__theme-search--empty">
                    <i18n-t tag="p" keypath="ui.setup.create-project.theme.empty">
                        <template #query>
                            <i>{{ query }}</i>
                        </template>
                    </i18n-t>
                </div>

                <template v-else-if="isSearching && results">
                    <div class="setup__themes-results">
                        <discover-package class="setup__themes-item" v-for="item in results" :data="item" :key="item.name" />
                    </div>
                    <div class="setup__themes-more">
                        <loading-button inline icon="search" :loading="searching" v-if="hasMore" @click="loadMore">{{ $t('ui.setup.create-project.theme.more') }}</loading-button>
                    </div>
                </template>
            </div>
        </template>
    </div>
</template>

<script>
import { mapMutations, mapState } from 'vuex';
import treeifyPaths from 'treeify-paths';
import views from '../../router/views';
import search from 'contao-package-list/src/mixins/search';
import CheckBox from '../widgets/CheckBox';
import FileUpload from 'vue-upload-component';
import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
import RadioButton from '../widgets/RadioButton';
import ThemeDetails from '../fragments/ThemeDetails';
import SearchInput from 'contao-package-list/src/components/fragments/SearchInput';
import LoadingSpinner from 'contao-package-list/src/components/fragments/LoadingSpinner';
import DiscoverPackage from 'contao-package-list/src/components/fragments/DiscoverPackage';
import FileTree from '../fragments/FileTree.vue';
import ButtonGroup from '../widgets/ButtonGroup.vue';

export default {
    mixins: [search],
    components: { ButtonGroup, FileTree, DiscoverPackage, LoadingSpinner, SearchInput, FileUpload, RadioButton, CheckBox, LoadingButton },

    data: () => ({
        processing: false,
        isWeb: true,

        version: '5.5',
        demo: false,

        view: 'require',
        theme: null,
        themeImage: null,

        searching: false,
        results: null,
        hasMore: false,
        offline: false,
    }),

    computed: {
        ...mapState('tasks', { taskStatus: 'status' }),
        ...mapState('server/php-web', ['phpVersionId', 'phpVersion']),
        ...mapState('server/contao', ['contaoVersion']),
        ...mapState('contao', { themeName: 'package', themeVersion: 'version' }),

        themeFiles: (vm) => treeifyPaths(vm.theme.files, { directoriesFirst: true }).children,

        versions() {
            const versions = [];

            versions.push({
                value: '5.5',
                label: `Contao 5.5 (${this.$t('ui.setup.create-project.latestTitle')})`,
                disabled: this.phpVersionId < 80200,
                description: this.$t('ui.setup.create-project.latestQ3', { year: '2025' }),
                problem: this.$t('ui.setup.create-project.requiresPHP', { version: '8.2.0', current: this.phpVersion }),
            });

            versions.push({
                value: '5.3',
                label: `Contao 5.3 (${this.$t('ui.setup.create-project.ltsTitle')})`,
                disabled: this.phpVersionId < 80100,
                description: this.$t('ui.setup.create-project.ltsText', { year: '2027' }),
                problem: this.$t('ui.setup.create-project.requiresPHP', { version: '8.1.0', current: this.phpVersion }),
            });

            versions.push({
                value: '4.13',
                label: `Contao 4.13 (${this.$t('ui.setup.create-project.ltsTitle')})`,
                disabled: this.phpVersionId < 70400,
                description: this.$t('ui.setup.create-project.pltsText', { year: '2025' }),
                problem: this.$t('ui.setup.create-project.requiresPHP', { version: '7.4.0', current: this.phpVersion }),
            });

            versions.push({
                value: 'theme',
                label: this.$t('ui.setup.create-project.theme'),
            });

            return versions;
        },
    },

    watch: {
        version() {
            this.stopSearch();
            this.searching = false;
            this.results = null;
            this.hasMore = false;
        },

        sorting() {
            this.searchThemes();
        },

        query() {
            this.results = null;

            if (!this.query) {
                this.searching = false;
                this.hasMore = false;
            } else {
                this.searchThemes();
            }
        },

        pages() {
            this.searchThemes();
        },

        themeName() {
            if (!this.themeName) {
                return;
            }

            this.closePopup();
            this.install({
                package: this.themeName,
                version: this.themeVersion,
            });
        },
    },

    methods: {
        ...mapMutations('packages/details', { closePopup: 'clearCurrent' }),

        async searchThemes() {
            this.searching = true;
            this.offline = false;

            try {
                const params = {
                    facetFilters: ['type:contao-theme'],
                    hitsPerPage: 10 * this.pages,
                };

                if (this.query) {
                    params.query = this.query;
                } else if (this.sorting) {
                    params.sorting = this.sorting;
                }

                const response = await this.$store.dispatch('algolia/findPackages', params);

                this.hasMore = response.nbPages > 1;

                if (response.nbHits === 0) {
                    this.results = {};
                    return;
                }

                const packages = {};

                response.hits.forEach((pkg) => {
                    packages[pkg.name] = pkg;
                });

                this.results = packages;
            } catch (err) {
                this.offline = true;
            }

            this.searching = false;
        },

        async install(data, mode = null) {
            this.processing = true;

            let config;

            if (data) {
                config = data;
            } else if (this.theme) {
                config = {
                    upload: this.theme.upload.id,
                };
            } else if (this.demo) {
                config = {
                    package: 'contao/contao-demo',
                    version: this.version,
                    'no-update': mode === 'no-update' ? '1' : '0',
                };
            } else {
                config = {
                    version: this.version,
                    'core-only': mode === 'core-only' ? '1' : '0',
                    'no-update': mode === 'no-update' ? '1' : '0',
                };
            }

            try {
                await this.$store.dispatch('contao/install', config);
            } catch (err) {
                // taskStatus will not be "complete"
            }

            this.processing = false;

            if (this.taskStatus !== 'complete') {
                return;
            }

            this.$store.commit('tasks/setDeleting', true);

            if (mode === 'no-update') {
                this.$store.commit('setSafeMode', true);
                this.$store.commit('setView', views.READY);
            } else {
                this.isWeb = (await this.$store.dispatch('server/contao/get', false)).data.public_dir === 'web';

                await Promise.all([
                    this.$store.dispatch('contao/install-tool/fetch', false),
                    this.$store.dispatch('server/database/get', false),
                    this.$store.dispatch('contao/backup/fetch', false),
                ]);

                this.$store.commit('contao/backup/setRestore', true);
                this.$store.commit('setup', 3);
            }

            this.$store.dispatch('tasks/deleteCurrent');
        },

        installNoUpdate() {
            this.install(null, 'no-update');
        },

        installCoreOnly() {
            this.install(null, 'core-only');
        },

        launch() {
            this.$store.commit('setView', views.READY);
        },

        openFileSelector() {
            if (!this.$refs.uploader) {
                return;
            }

            this.$refs.uploader.$el.querySelector('input').click();
        },

        async filterTheme(newFile, oldFile, prevent) {
            // New file has been added
            if (newFile && !oldFile) {
                if (!/(\.cto|\.zip)$/i.test(newFile.name)) {
                    console && console.debug(`${newFile.name} is not a .zip or .cto`);
                    alert(this.$t('ui.setup.create-project.themeInvalid'));

                    return prevent();
                }
            }
        },

        async uploadTheme(newFile, oldFile) {
            if (!newFile) {
                return;
            }

            this.processing = true;

            if (this.$refs.uploader.uploaded && newFile && oldFile && !newFile.active && oldFile.active) {
                this.theme.upload = newFile.response.data;

                if (newFile.success) {
                    this.install();
                }

                return;
            }

            newFile.url = URL.createObjectURL(newFile.file);

            try {
                const file = new File([newFile.file], newFile.name, { type: newFile.type });
                const JSZip = (await import('jszip')).default;
                const zip = await JSZip.loadAsync(file);
                const files = zip
                    .filter((path) => {
                        return !['composer.json', 'theme.xml'].includes(path) && !path.startsWith('__MACOSX/') && !path.includes('.DS_Store') && !path.endsWith('/');
                    })
                    .map((f) => f.name);

                let root = files[0].substring(0, files[0].indexOf('/') + 1);
                if (files.find((file) => file.substr(0, root.length) !== root)) {
                    root = '';
                }

                const composerJson = JSON.parse(await zip.file(`${root}composer.json`).async('string'));
                const theme = await zip.file(`${root}theme.xml`).async('string');

                const parser = new DOMParser();
                const doc = parser.parseFromString(theme, 'application/xml');

                this.theme = {
                    tstamp: doc.querySelector('table[name="tl_theme"] field[name="tstamp"]')?.innerHTML,
                    name: doc.querySelector('table[name="tl_theme"] field[name="name"]')?.innerHTML,
                    author: doc.querySelector('table[name="tl_theme"] field[name="author"]')?.innerHTML,
                    screenshot: doc.querySelector('table[name="tl_theme"] field[name="screenshot"]')?.innerHTML,
                    composerJson,
                    files,
                };

                if (this.theme.screenshot && this.theme.screenshot !== 'NULL') {
                    try {
                        const image = await zip.file(`${root}${this.theme.screenshot}`);
                        this.themeImage = image ? URL.createObjectURL(await image.async('blob')) : null;
                    } catch (err) {
                        // Ignore invalid theme image
                    }
                }
            } catch (err) {
                console && console.debug(err);
                alert(this.$t('ui.setup.create-project.themeInvalid'));
                this.cancelTheme();
            }

            this.processing = false;
        },

        cancelTheme() {
            this.theme = null;
            this.$refs.uploader.clear();
        },

        themeDetails() {
            this.$store.commit('packages/setInstalled', {
                local: {
                    [this.theme.composerJson.name]: Object.assign({}, this.theme.composerJson, { uploaded: true }),
                },
            });

            this.$store.commit('packages/details/setCurrent', this.theme.composerJson.name);
        },

        installTheme() {
            if (!this.$refs.uploader.active) {
                this.processing = true;
                this.$refs.uploader.active = true;
            }
        },
    },

    async mounted() {
        await this.$router.isReady();

        // remove existing package query parameters
        if (Object.keys(this.$route.query).length) {
            this.$router.replace({ query: null });
        }

        await this.$store.dispatch('packages/details/init', { vue: this, component: ThemeDetails });
        this.$store.commit('packages/setInstalled', {});
        this.isWeb = (await this.$store.dispatch('server/contao/get')).data.public_dir === 'web';
        this.version = this.versions.find((v) => !v.disabled).value;
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use '~contao-package-list/src/assets/styles/defaults';

.setup {
    &__versions {
        margin: 0;
        padding: 0 0 0 15px;
    }

    &__version {
        margin: 0.5em 0;
        text-align: left;

        &--warning {
            color: var(--btn-alert);
        }
    }

    &__releaseplan {
        margin-top: 1.5em;
    }

    &__fielddesc--version {
        margin-bottom: -1em !important;
    }

    &__core-features {
        margin: 5px 0 0 5px;
        font-size: 12px;
    }

    &__theme-image {
        max-width: 100%;
        height: auto;
        border: 1px solid var(--border);
    }

    &__theme {
        p {
            margin: 1em 0;
        }
    }

    &__theme-upload {
        position: absolute !important;
        visibility: hidden;
    }

    &__themes {
        padding: 0 14px;

        &-results {
            display: grid;
            gap: 14px;

            @include defaults.screen(1024) {
                grid-template-columns: repeat(2, 1fr);

                &-item {
                    flex-basis: calc(50% - 16px);
                    margin-left: 8px;
                    margin-right: 8px;
                }
            }
        }

        &-item {
            border: 1px solid var(--tiles-bdr);
        }

        &-more {
            margin-top: 20px;
            text-align: center;
        }
    }

    &__theme-search {
        margin: 20px 0 0;
        text-align: center;

        &--empty {
            padding-top: 60px;
            background: url('~contao-package-list/src/assets/images/sad.svg') top center no-repeat;
            background-size: 50px 50px;
        }

        &--offline {
            padding-top: 60px;
            background: url('~contao-package-list/src/assets/images/offline.svg') top center no-repeat;
            background-size: 50px 50px;
        }

        &--loader {
            .sk-circle {
                width: 50px;
                height: 50px;
                margin: 0 auto 20px;
            }
        }

        button {
            margin-top: 2em;
        }
    }

    &__fielddesc--warning {
        padding: 10px 10px 10px 40px;
        background: var(--hint-bg) url('~contao-package-list/src/assets/images/hint.svg') 10px 10px no-repeat;
    }

    &__tabs {
        margin: 1em 0;
    }

    &__tab-controls {
        display: flex;
    }

    &__tab-control {
        flex-grow: 1;
        padding: 4px 10px;
        border: none;
        border-top: 1px solid var(--border);
        border-left: 1px solid var(--border);
        background: none;
        cursor: pointer;

        &:last-child {
            border-right: 1px solid var(--border);
        }

        &--active {
            border-color: var(--btn);
            background: var(--btn);
            color: #fff;
        }
    }

    &__tab {
        border: 1px solid var(--border);

        &--files {
            white-space: pre;
            overflow: scroll;
            height: 200px;
        }
    }

    &__requires {
        width: 100%;
        border-collapse: collapse;

        th,
        td {
            margin: 0;
            padding: 3px 10px;
            text-align: start;
            vertical-align: top;
        }

        th {
            background: var(--btn);
            color: #fff;
        }

        td {
            border-bottom: 1px solid var(--border);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:nth-child(odd) td {
            background: var(--table-odd-bg);
        }
    }
}
</style>
