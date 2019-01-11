<template>
    <package-base @start-upload="openFileSelector()">
        <div class="package-list">
            <loader v-if="packages === null || uploads === null" class="package-list__status">
                <p>{{ 'ui.packagelist.loading' | translate }}</p>
            </loader>

            <template v-else>
                <package-uploads ref="uploader" v-if="uploads !== false"/>

                <h2 class="package-list__headline" v-if="hasAdded">{{ 'ui.packagelist.added' | translate }}</h2>
                <local-package v-for="item in addedPackages" :data="item" :key="item.name"/>
                <local-package v-for="item in requiredNotAdded" :data="item" :key="item.name"/>

                <h2 class="package-list__headline" v-if="showHeadline">{{ 'ui.packagelist.installed' | translate }}</h2>
                <root-package :package="packages['contao/manager-bundle'] || requiredPackages['contao/manager-bundle']" v-if="packages"/>
                <local-package v-for="item in notRootInstalled" :data="item" :key="item.name"/>
            </template>
        </div>

        <div class="package-actions__inner" slot="actions" v-if="hasUploads && !uploading">
            <p class="package-actions__text">{{ $t('ui.packages.uploadMessage', { total: totalUploads }, totalUploads) }}</p>
            <button class="package-actions__button widget-button widget-button--primary" :disabled="!canConfirmUploads || removingUploads" @click="confirmUploads">{{ $t('ui.packages.uploadApply') }}</button>
            <loading-button class="package-actions__button" color="alert" :loading="removingUploads" @click="removeUploads">{{ $t('ui.packages.uploadReset') }}</loading-button>
        </div>
        <div class="package-actions__inner" slot="actions" v-else-if="totalChanges && !uploading">
            <p class="package-actions__text">{{ $t('ui.packages.changesMessage', { total: totalChanges }, totalChanges) }}</p>
            <button class="package-actions__button widget-button" @click="dryrunChanges">{{ 'ui.packages.changesDryrun' | translate }}</button>
            <button class="package-actions__button widget-button widget-button--primary" @click="applyChanges">{{ 'ui.packages.changesApply' | translate }}</button>
            <button class="package-actions__button widget-button widget-button--alert" :disabled="!canResetChanges && !confirmed.length" @click="resetChanges">{{ 'ui.packages.changesReset' | translate }}</button>
        </div>
    </package-base>
</template>

<script>
    import { mapState, mapGetters } from 'vuex';

    import Loader from '../fragments/Loader';
    import PackageBase from './Packages/Base';
    import PackageUploads from './Packages/Uploads';
    import LocalPackage from './Packages/LocalPackage';
    import RootPackage from './Packages/RootPackage';
    import LoadingButton from '../widgets/LoadingButton';

    export default {
        components: { Loader, PackageBase, PackageUploads, RootPackage, LocalPackage, LoadingButton },

        computed: {
            ...mapState('packages', {
                'packages': 'installed',
                'addedPackages': 'add',
                'requiredPackages': 'required',
            }),
            ...mapState('packages/uploads', ['uploads', 'uploading', 'files', 'removing', 'confirmed']),
            ...mapGetters('packages', ['totalChanges', 'hasAdded', 'packageAdded', 'packageInstalled', 'canResetChanges']),
            ...mapGetters('packages/uploads', ['hasUploads', 'totalUploads', 'canConfirmUploads']),

            notRootInstalled: vm => Object.values(vm.packages).filter(pkg => pkg.name !== 'contao/manager-bundle'),
            requiredNotAdded: vm => Object.values(vm.requiredPackages).filter(
                pkg => pkg.name !== 'contao/manager-bundle' && !Object.values(vm.addedPackages).find(add => add.name === pkg.name),
            ),
            removingUploads: vm => vm.removing.length > 0,
            showHeadline: vm => vm.hasAdded || vm.hasUploads || vm.files.length,
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

            applyChanges() {
                this.$store.dispatch('packages/apply').then(() => this.$store.dispatch('packages/load', true));
            },

            resetChanges() {
                this.$store.commit('packages/reset');
                this.$store.commit('packages/uploads/unconfirmAll');
            },
        },

        mounted() {
            if (null === this.packages) {
                this.$store.dispatch('packages/load');
            }

            if (null === this.packages) {
                this.$store.dispatch('packages/uploads/load');
            }
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

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
