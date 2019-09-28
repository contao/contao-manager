<template>
    <package
        :title="upload.name"
        :hint="hintUploading"
        v-if="!upload.success || upload.error"
    >
        <template slot="hint" v-if="upload.error">
            <p>
                {{upload.error}}
                <template v-if="upload.exception">{{upload.exception}}</template>
            </p>
        </template>

        <template slot="release">
            <progress-bar :amount="progress"/>
            <div class="package__version package__version--release">
                <p><strong>{{ filesize }}</strong></p>
            </div>
        </template>

        <template slot="actions">
            <loading-button color="alert" icon="trash" :loading="removing" @click="removeUpload">{{ $t('ui.package.removeButton') }}</loading-button>
        </template>
    </package>

    <local-package
        :data="pkg"
        :hint="$t('ui.packages.uploadDuplicate')"
        v-else-if="isDuplicate(upload.id)"
    >
        <template slot="actions">
            <button class="widget-button widget-button--primary widget-button--add" disabled>{{ $t('ui.package.installButton') }}</button>
            <loading-button color="alert" icon="trash" :loading="removing" @click="removeUpload">{{ $t('ui.package.removeButton') }}</loading-button>
        </template>
    </local-package>

    <local-package
        :data="pkg"
        :hint="$t('ui.packages.uploadInstalled')"
        v-else-if="versionInstalled(pkg.name, pkg.version)"
    >
        <template slot="actions">
            <button class="widget-button widget-button--primary widget-button--add" disabled>{{ $t('ui.package.installButton') }}</button>
            <loading-button color="alert" icon="trash" :loading="removing" @click="removeUpload">{{ $t('ui.package.removeButton') }}</loading-button>
        </template>
    </local-package>

    <local-package
        :data="pkg"
        v-else
    >
        <template slot="actions">
            <button class="widget-button widget-button--primary widget-button--add" :disabled="!canBeAdded" @click="addPackage">{{ $t('ui.package.installButton') }}</button>
            <loading-button color="alert" icon="trash" :loading="removing" @click="removeUpload">{{ $t('ui.package.removeButton') }}</loading-button>
        </template>
    </local-package>

</template>

<script>
    import { mapGetters } from 'vuex';

    import metadata from 'contao-package-list/src/mixins/metadata';
    import Package from './Package';
    import LocalPackage from './LocalPackage';
    import ProgressBar from '../../fragments/ProgressBar';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        mixins: [metadata],
        components: { ProgressBar, Package, LocalPackage, LoadingButton },

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
            ...mapGetters('packages', ['packageRemoved', 'versionInstalled']),
            ...mapGetters('packages/uploads', ['isDuplicate', 'isRemoving']),

            removing: vm => vm.isRemoving(vm.upload.id),
            progress: vm => 100 / vm.upload.size * vm.upload.filesize,

            canBeAdded: vm => !vm.removing && !vm.packageRemoved(vm.pkg.name),

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

            filesize() {
                let sizes = ['KB', 'MB', 'GB'];
                let size = 'Bytes';
                let bytes = this.upload.size;

                while (bytes > 1024) {
                    bytes = bytes/1024;
                    size = sizes.shift();
                }

                return `${Math.round(bytes * 100) / 100} ${size}`;
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
                    additionals.push(this.$t('ui.package.additionalDownloads', { count: this.pkg.downloads }, this.pkg.downloads));
                }

                if (this.pkg.favers) {
                    additionals.push(this.$t('ui.package.additionalStars', { count: this.pkg.favers }, this.pkg.favers));
                }

                return additionals;
            },
        },

        methods: {
            addPackage() {
                this.$store.commit('packages/uploads/confirm', this.upload.id);
            },

            removeUpload() {
                this.$store.dispatch('packages/uploads/remove', this.upload.id);
            },
        },
    };
</script>
