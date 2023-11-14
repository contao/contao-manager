<template>

    <section>
        <header class="setup__header">
            <img src="../../assets/images/document-root.svg" width="80" height="80" alt="" class="setup__icon" />
            <h1 class="setup__headline">{{ $t('ui.setup.document-root.headline') }}</h1>
            <p class="setup__warning" v-if="needsFix">{{ $t('ui.setup.document-root.warning') }}</p>
            <p class="setup__description">{{ $t('ui.setup.document-root.description1') }}</p>
            <p class="setup__description">{{ $t('ui.setup.document-root.description2') }}</p>
            <a class="widget-button widget-button--inline widget-button--info widget-button--link" :href="`https://to.contao.org/docs/webroot?lang=${$i18n.locale}`" target="_blank">{{ $t('ui.setup.document-root.documentation') }}</a>
        </header>

        <transition :name="forceInstall ? 'none' : 'animate-flip'" type="transition" mode="out-in" v-if="projectDir !== null">

            <template v-if="needsFix || wantsFix">

                <main class="setup__form setup__form--center" v-if="directoryUpdated" v-bind:key="'updated'">
                    <div class="setup__fields">
                        <svg class="setup__check" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" /></svg>
                        <p class="setup__fielddesc">{{ $t('ui.setup.document-root.confirmation') }}</p>
                        <dl class="setup__directories">
                            <dt>{{ $t('ui.setup.document-root.currentRoot') }}</dt>
                            <dd v-if="isWeb">{{ projectDir }}/web</dd>
                            <dd v-else-if="isPublic">{{ projectDir }}/public</dd>
                            <dd v-else>{{ projectDir }}</dd>
                            <dt>{{ $t('ui.setup.document-root.newRoot') }}</dt>
                            <dd v-if="isEmpty && (!wantsFix || !directory) && canUsePublicDir && usePublicDir">{{ projectDir }}<span>/public</span></dd>
                            <dd v-else-if="isEmpty && (!wantsFix || !directory)">{{ projectDir }}<span>/web</span></dd>
                            <dd v-else-if="canUsePublicDir && usePublicDir">{{ projectDir }}<span>/{{ directory }}/public</span></dd>
                            <dd v-else>{{ projectDir }}<span>/{{ directory }}/web</span></dd>
                        </dl>
                    </div>
                    <div class="setup__actions setup__actions--center">
                        <loading-button inline :loading="processing" color="primary" icon="update" @click="reload">{{ $t('ui.setup.document-root.reload') }}</loading-button>
                    </div>
                </main>

                <main class="setup__form" v-else-if="conflicts.length" v-bind:key="'conflicts'">
                    <div class="setup__fields">
                        <h2 class="setup__fieldtitle">{{ $t('ui.setup.document-root.conflictsTitle') }}</h2>
                        <p class="setup__fielddesc">{{ $t('ui.setup.document-root.conflictsDirectory', { count: conflicts.length }) }}</p>
                        <ul>
                            <li v-for="file in conflicts.slice(0, 5)" :key="file">{{ file }}</li>
                            <li v-if="conflicts.length > 5">...</li>
                        </ul>
                        <checkbox name="ignoreConflicts" :label="$t('ui.setup.document-root.ignoreConflicts')" :disabled="processing" v-if="isPublic || isWeb" v-model="forceInstall"/>
                    </div>
                    <div class="setup__actions setup__actions--center">
                        <button class="widget-button widget-button--alert widget-button--run" v-if="forceInstall" @click="$emit('continue')">{{ $t('ui.server.contao.setup') }}</button>
                        <template v-else>
                            <loading-button inline icon="update" :loading="processing" @click="init(false)">{{ $t('ui.setup.document-root.check') }}</loading-button>
                            <button class="widget-button widget-button--inline widget-button--primary widget-button--gear" :disabled="processing" @click="conflicts=[]">{{ $t('ui.setup.document-root.create') }}</button>
                        </template>
                    </div>
                </main>

                <main class="setup__form" v-else v-bind:key="'setup'">
                    <img src="../../assets/images/button-update.svg" class="invisible" alt=""> <!-- prefetch the update icon for the confirmation page -->
                    <div class="setup__fields">
                        <h2 class="setup__fieldtitle">{{ $t('ui.setup.document-root.formTitle') }}</h2>
                        <p class="setup__fielddesc">{{ $t('ui.setup.document-root.formText1') }} <u>{{ $t('ui.setup.document-root.formText2') }}</u></p>

                        <text-field
                            ref="directory" name="directory" :label="$t('ui.setup.document-root.directory')"
                            :error="directoryError" :required="!isEmpty" pattern="^[^/]+$" validate
                            v-model="directory"
                            v-if="!isEmpty || wantsFix"
                        />

                        <radio-button name="usePublicDir" :options="publicDirOptions" allow-html v-model="usePublicDir" v-if="canUsePublicDir"/>
                        <dl class="setup__directories">
                            <dt>{{ $t('ui.setup.document-root.currentRoot') }}</dt>
                            <dd v-if="isWeb">{{ projectDir }}{{ directorySeparator }}web</dd>
                            <dd v-else-if="isPublic">{{ projectDir }}{{ directorySeparator }}public</dd>
                            <dd v-else>{{ projectDir }}</dd>
                            <dt>{{ $t('ui.setup.document-root.newRoot') }}</dt>
                            <dd v-if="isEmpty && (!wantsFix || !directory) && canUsePublicDir && usePublicDir">{{ projectDir }}<span>{{ directorySeparator }}public</span></dd>
                            <dd v-else-if="isEmpty && (!wantsFix || !directory)">{{ projectDir }}<span>{{ directorySeparator }}web</span></dd>
                            <dd v-else-if="canUsePublicDir && usePublicDir">{{ projectDir }}<span>{{ directorySeparator }}{{ directory }}{{ directorySeparator }}public</span></dd>
                            <dd v-else>{{ projectDir }}<span>{{ directorySeparator }}{{ directory }}{{ directorySeparator }}web</span></dd>
                        </dl>
                        <checkbox name="autoconfig" :label="$t('ui.setup.document-root.autoconfig')" :disabled="processing" v-model="autoconfig"/>
                    </div>
                    <div class="setup__actions setup__actions--center">
                        <loading-button color="primary" icon="run" :loading="processing" :disabled="!autoconfig || !!directoryError || (wantsFix && !directory && ((isPublic && usePublicDir) || (isWeb && !usePublicDir)))" @click="setupDocroot">{{ $t('ui.setup.document-root.finish') }}</loading-button>
                        <button type="button" class="widget-button" :disabled="processing" @click="init" v-if="wantsFix">{{ $t('ui.setup.cancel') }}</button>
                    </div>
                </main>
            </template>

            <template v-else>
                <main class="setup__form" v-bind:key="'confirmation'">
                    <div class="setup__fields setup__fields--center">
                        <svg class="setup__check" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" /></svg>
                        <p class="setup__fielddesc">{{ $t('ui.setup.document-root.success') }}</p>
                        <i18n tag="p" class="setup__fielddesc" :path="`ui.setup.document-root.${contaoVersion ? 'installed' : 'installing'}ProjectDir`">
                            <template #dir><code>{{ projectDir }}</code></template>
                        </i18n>
                        <i18n tag="p" class="setup__fielddesc" :path="`ui.setup.document-root.${contaoVersion ? 'installed' : 'installing'}PublicDir`">
                            <template #dir><code>{{ publicDir }}</code></template>
                        </i18n>
                    </div>
                    <div class="setup__actions setup__actions--center">
                        <button class="widget-button widget-button--inline widget-button--gear" @click="wantsFix = true" v-if="!contaoVersion">{{ $t('ui.setup.document-root.change') }}</button>
                        <button class="widget-button widget-button--inline widget-button--primary" @click="$emit('continue')">{{ $t('ui.setup.continue') }}</button>
                    </div>
                </main>
            </template>
        </transition>

    </section>

</template>

<script>
    import { mapState } from 'vuex';

    import TextField from '../widgets/TextField';
    import RadioButton from '../widgets/RadioButton';
    import Checkbox from '../widgets/Checkbox';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { TextField, RadioButton, Checkbox, LoadingButton },

        data: () => ({
            processing: false,
            usePublicDir: false,
            wantsFix: false,

            conflicts: [],
            isEmpty: true,
            isWeb: true,
            isPublic: false,
            projectDir: null,
            autoconfig: false,
            forceInstall: false,
            directory: '',
            directoryExists: false,
            directoryUpdated: false,
            directorySeparator: '/',
        }),

        computed: {
            ...mapState('server/php-web', ['phpVersionId']),
            ...mapState('server/contao', ['contaoVersion']),

            needsFix: vm => !vm.isEmpty || (!vm.isWeb && (!vm.isPublic || !vm.canUsePublicDir)),

            publicDirOptions: vm => [
                { label: vm.$t('ui.setup.document-root.publicDir', { dir: '<code>web</code>', version: '4.9+' }), value: false },
                { label: vm.$t('ui.setup.document-root.publicDir', { dir: '<code>public</code>', version: '4.13+' }), value: true }
            ],

            canUsePublicDir: vm => vm.phpVersionId >= 70400,

            publicDir: vm => vm.isWeb ? `${vm.projectDir}${vm.directorySeparator}web` : `${vm.projectDir}${vm.directorySeparator}public`,

            directoryError() {
                if (this.directoryExists) {
                    return this.$t('ui.setup.document-root.directoryExists');
                }

                if (this.directory && !this.directory.match(/^[^/]+$/)) {
                    return this.$t('ui.setup.document-root.directoryInvalid')
                }

                if (!this.wantsFix && !this.isEmpty && !this.directory) {
                    return this.$t('ui.setup.document-root.directoryInvalid')
                }

                return '';
            },
        },

        methods: {
            reload() {
                this.processing = true;
                window.location.reload();
            },

            async setupDocroot() {
                this.processing = true;
                const response = await this.$store.dispatch('server/contao/documentRoot', {
                    directory: (!this.isEmpty || this.wantsFix) ? this.directory : null,
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

                // Stop the logout countdown when moving the manager files to prevent 404 error
                this.$store.commit('auth/resetCountdown');
            },

            async init(cache = true) {
                this.processing = true;
                const response = await this.$store.dispatch('server/contao/get', cache);

                this.projectDir = response.body.project_dir;
                this.conflicts = response.body.conflicts;
                this.isEmpty = response.body.conflicts.length === 0;
                this.isWeb = response.body.public_dir === 'web';
                this.isPublic = response.body.public_dir === 'public';
                this.usePublicDir = response.body.public_dir === 'public';
                this.wantsFix = false;
                this.directory = this.isEmpty ? '' : location.hostname;
                this.directorySeparator = response.body.directory_separator;

                this.processing = false;
            },
        },

        watch: {
            directory() {
                this.directoryExists = false;
            },
        },

        async mounted () {
            this.init();
        }
    };
</script>


<style rel="stylesheet/scss" lang="scss">
@import "~contao-package-list/src/assets/styles/defaults";

.setup {
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
}
</style>
