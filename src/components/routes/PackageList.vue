<template>
    <package-base @start-upload="openFileSelector()">
        <div class="package-list">
            <package-uploads ref="uploader" v-if="uploads !== false"/>

            <h2 class="package-list__headline" v-if="hasAdded">{{ $t('ui.packagelist.added') }}</h2>
            <local-package v-for="item in addedPackages" :data="item" :key="item.name"/>

            <h2 class="package-list__headline" v-if="showHeadline">{{ $t('ui.packagelist.installed') }}</h2>
            <local-package v-for="item in installedPackages" :data="item" :key="item.name"/>
        </div>

        <template #actions>
            <div class="package-actions__inner" v-if="hasUploads && !uploading">
                <p class="package-actions__text">{{ $t('ui.packages.uploadMessage', { total: totalUploads }, totalUploads) }}</p>
                <button class="package-actions__button widget-button widget-button--primary" :disabled="!canConfirmUploads || removingUploads" @click="confirmUploads">{{ $t('ui.packages.uploadApply') }}</button>
                <loading-button class="package-actions__button" color="alert" :loading="removingUploads" @click="removeUploads">{{ $t('ui.packages.uploadReset') }}</loading-button>
            </div>
            <div class="package-actions__inner" v-else-if="totalChanges && !uploading">
                <cloud-status button-class="package-actions__button"/>
                <p class="package-actions__text">{{ $t('ui.packages.changesMessage', { total: totalChanges }, totalChanges) }}</p>
                <button class="package-actions__button widget-button" @click="dryrunChanges">{{ $t('ui.packages.changesDryrun') }}</button>
                <button class="package-actions__button widget-button widget-button--primary" @click="applyChanges">{{ $t('ui.packages.changesApply') }}</button>
                <button class="package-actions__button widget-button widget-button--alert" :disabled="!canResetChanges && !confirmed.length" @click="resetChanges">{{ $t('ui.packages.changesReset') }}</button>
            </div>
        </template>
    </package-base>
</template>

<script>
    import { mapState, mapGetters } from 'vuex';

    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
    import PackageBase from './Packages/Base';
    import PackageUploads from './Packages/Uploads';
    import LocalPackage from './Packages/LocalPackage';
    import CloudStatus from '../fragments/CloudStatus';

    const sortPackages = (a, b) => {
        if (a.name === 'contao/manager-bundle') {
            return -1;
        }

        if (b.name === 'contao/manager-bundle') {
            return 1;
        }

        return 0;
    };

    export default {
        components: { PackageBase, PackageUploads, LocalPackage, LoadingButton, CloudStatus },

        computed: {
            ...mapState('packages', {
                'requiredPackages': 'required',
            }),
            ...mapState('packages/uploads', ['uploads', 'uploading', 'files', 'removing', 'confirmed']),
            ...mapGetters('packages', [
                'totalChanges',
                'packageMissing',
                'canResetChanges',
                'visibleRequired',
                'visibleInstalled',
                'visibleAdded',
            ]),
            ...mapGetters('packages/uploads', ['hasUploads', 'totalUploads', 'canConfirmUploads']),

            addedPackages: vm => vm.visibleRequired.concat(vm.visibleAdded).filter(pkg => !vm.packageMissing(pkg.name)).sort(sortPackages),
            installedPackages: vm => vm.visibleRequired.filter(pkg => vm.packageMissing(pkg.name)).concat(vm.visibleInstalled).sort(sortPackages),

            removingUploads: vm => vm.removing.length > 0,
            showHeadline: vm => vm.installedPackages.length > 0 && (vm.hasAdded || vm.hasUploads || vm.files.length),
            hasAdded: vm => vm.addedPackages.length,
        },

        methods: {
            openFileSelector() {
                if (!this.$refs.uploader) {
                    return;
                }

                this.$refs.uploader.openFileSelector();
            },

            confirmUploads() {
                this.$store.commit('packages/uploads/confirmAll');
            },

            async removeUploads() {
                await this.$store.dispatch('packages/uploads/removeAll');
            },

            dryrunChanges() {
                this.$store.dispatch('packages/apply', true);
            },

            async applyChanges() {
                await this.$store.dispatch('packages/apply');
                await this.$store.dispatch('packages/load');
            },

            resetChanges() {
                this.$store.commit('packages/reset');
                this.$store.commit('packages/uploads/unconfirmAll');
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .package-list {
        position: relative;

        &__status {
            margin: 100px 0;
            text-align: center;
            font-size: 20px;
            line-height: 1.5em;

            .sk-circle {
                width: 100px;
                height: 100px;
                margin: 0 auto 40px;
            }
        }

        &__headline {
            font-size: 18px;
            font-weight: $font-weight-normal;
            margin: 30px 0 10px;
        }
    }
</style>
