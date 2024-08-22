<template>
    <base-package
        :title="upload.name"
        :hint="hintUploading"
        v-if="!upload.success || upload.error"
    >
        <template #hint v-if="upload.error">
            <p>
                {{upload.error}}
                <template v-if="upload.exception">{{upload.exception}}</template>
            </p>
        </template>

        <template #release>
            <progress-bar :amount="progress"/>
            <div class="package__version package__version--release">
                <p><strong>{{ upload.size|filesize }}</strong></p>
            </div>
        </template>

        <template #actions>
            <loading-button color="alert" icon="trash" :loading="removing" @click="removeUpload">{{ $t('ui.package.removeButton') }}</loading-button>
        </template>
    </base-package>

    <composer-package
        uncloseable-hint
        :data="pkg"
        v-else
    >
        <template #hint>
            <p v-if="isDuplicate(upload.id, pkg.name)">{{ $t('ui.packages.uploadDuplicate') }}</p>
            <p v-else-if="versionInstalled(pkg.name, pkg.version)">{{ $t('ui.packages.uploadInstalled') }}</p>
            <p v-else-if="!isCompatible">{{ $t('ui.package.incompatible', { package: pkg.name, constraint: packageConstraint('contao/manager-bundle') }) }}</p>
        </template>
        <template #actions>
            <button class="widget-button widget-button--primary widget-button--add" :disabled="!canBeInstalled" @click="addPackage">{{ $t('ui.package.installButton') }}</button>
            <loading-button color="alert" icon="trash" :loading="removing" @click="removeUpload">{{ $t('ui.package.removeButton') }}</loading-button>
        </template>
    </composer-package>

</template>

<script>
    import { mapGetters } from 'vuex';

    import metadata from 'contao-package-list/src/mixins/metadata';
    import BasePackage from './BasePackage';
    import ComposerPackage from './ComposerPackage';
    import ProgressBar from '../../fragments/ProgressBar';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        mixins: [metadata],
        components: { ProgressBar, BasePackage, ComposerPackage, LoadingButton },

        props: {
            upload: {
                type: Object,
                required: true,
            },
            uploader: {
                type: Object,
                required: true,
            },
        },

        computed: {
            ...mapGetters('packages', ['packageRemoved', 'versionInstalled', 'contaoSupported', 'packageConstraint']),
            ...mapGetters('packages/uploads', ['isDuplicate', 'isRemoving']),

            removing: vm => vm.isRemoving(vm.upload.id),
            progress: vm => 100 / vm.upload.size * vm.upload.filesize,

            isTheme: vm => vm.data.type === 'contao-theme' || (vm.metadata && vm.metadata.type === 'contao-theme'),
            isCompatible: vm => !vm.data.require || vm.contaoSupported(vm.data.require['contao/core-bundle'] || vm.data.require['contao/manager-bundle'] || '0'),

            canBeInstalled: vm => !vm.isDuplicate(vm.upload.id, vm.pkg.name)
                && !vm.versionInstalled(vm.pkg.name, vm.pkg.version)
                && !vm.removing
                && !vm.packageRemoved(vm.pkg.name)
                && !vm.isTheme
                && vm.isCompatible,

            data: vm => vm.upload.package || { name: '' },

            pkg: vm => Object.assign(
                { name: vm.upload.name, version: null },
                vm.upload.package || {}
            ),

            hintUploading() {
                if (this.upload.error) {
                    return this.upload.error;
                }

                if (this.upload.size !== this.upload.filesize) {
                    return this.$t('ui.packages.uploadIncomplete');
                }

                return '';
            },

            additional() {
                const additionals = [];

                if (this.pkg.license) {
                    if (this.pkg.license instanceof Array) {
                        additionals.push(this.pkg.license.join('/'));
                    } else {
                        additionals.push(this.pkg.license);
                    }
                }

                if (this.pkg.downloads) {
                    additionals.push(this.$tc('ui.package.additionalDownloads', this.pkg.downloads));
                }

                if (this.pkg.favers) {
                    additionals.push(this.$tc('ui.package.additionalStars', this.pkg.favers));
                }

                return additionals;
            },
        },

        methods: {
            addPackage() {
                this.$store.dispatch('packages/uploads/confirm', this.upload.id);
            },

            removeUpload() {
                this.$store.dispatch('packages/uploads/remove', this.upload.id);
            },
        },
    };
</script>
