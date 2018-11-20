<template>
    <package
        :title="data.name"
        :hint="hintUploading"
        v-if="!data.success || data.error"
    >
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

    <package
        :title="pkg.title || pkg.name"
        :name="data.name"
        :description="pkg.description"
        :hint="hintDuplicate"
        release-disabled
        v-else
    >
        <more private :name="pkg.name" :homepage="pkg.homepage" :support="Object.assign({}, pkg.support)" slot="more"/>

        <template slot="additional">
            <strong class="package__version package__version--additional">{{ 'ui.package.version' | translate({ version: pkg.version }) }}</strong>
            <span v-for="(item,k) in additional" :key="k">{{ item }}</span>
        </template>

        <template slot="release">
            <fieldset>
                <input type="text" :placeholder="$t('ui.package.latestConstraint')" disabled>
                <button class="widget-button" disabled>{{ 'ui.package.editConstraint' | translate }}</button>
            </fieldset>
            <div class="package__version package__version--release">
                <strong>{{ 'ui.package.version' | translate({ version: pkg.version }) }}</strong>
            </div>
        </template>

        <template slot="actions">
            <button class="widget-button widget-button--primary widget-button--add" :disabled="!canBeAdded" @click="addPackage">{{ $t('ui.package.installButton') }}</button>
            <loading-button color="alert" icon="trash" :loading="removing" @click="removeUpload">{{ $t('ui.package.removeButton') }}</loading-button>
        </template>
    </package>

</template>

<script>
    import { mapGetters } from 'vuex';

    import Package from './Package';
    import More from './More';
    import ProgressBar from '../../fragments/ProgressBar';
    import LoadingButton from '../../widgets/LoadingButton';

    export default {
        components: { ProgressBar, Package, More, LoadingButton },

        props: {
            data: {
                type: Object,
                required: true,
            },
            uploader: {
                type: Object,
                required: true,
            },
        },

        computed: {
            ...mapGetters('packages', ['packageRemoved']),
            ...mapGetters('packages/uploads', ['isDuplicate', 'isRemoving']),

            removing: vm => vm.isRemoving(vm.data.id),
            progress: vm => 100 / vm.data.size * vm.data.filesize,

            canBeAdded: vm => !vm.removing && !vm.isDuplicate(vm.data.id) && !vm.packageRemoved(vm.pkg.name),

            pkg: vm => Object.assign(
                { name: vm.data.name, },
                vm.data.package || {}
            ),

            hintUploading() {
                if (this.data.error) {
                    return this.data.error;
                }

                if (this.data.size !== this.data.filesize) {
                    return this.$t('ui.packages.uploadIncomplete');
                }

                return '';
            },

            hintDuplicate() {
                if (!this.isDuplicate(this.data.id)) {
                    return '';
                }

                return this.$t('ui.packages.uploadDuplicate');
            },

            filesize() {
                let sizes = ['KB', 'MB', 'GB'];
                let size = 'Bytes';
                let bytes = this.data.size;

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
                this.$store.commit('packages/uploads/confirm', this.data.id);
            },

            removeUpload() {
                this.removing = true;
                this.$store.dispatch('packages/uploads/remove', this.data.id);
            },
        },
    };
</script>
