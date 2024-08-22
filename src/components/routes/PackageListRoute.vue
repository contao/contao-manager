<template>
    <package-base @start-upload="openFileSelector()">
        <div class="package-list">
            <package-uploads ref="uploader" v-if="uploads !== false"/>

            <h2 class="package-list__headline" v-if="hasAdded">{{ $t('ui.packagelist.added') }}</h2>
            <composer-package v-for="item in addedPackages" :data="item" :key="item.name"/>

            <h2 class="package-list__headline" v-if="showHeadline">{{ $t('ui.packagelist.installed') }}</h2>
            <composer-package v-for="item in installedPackages" :data="item" :key="item.name"/>
        </div>

        <template #actions>
            <div class="package-actions__inner" v-if="hasUploads && !uploading">
                <p class="package-actions__text">{{ $tc('ui.packages.uploadMessage', totalUploads) }}</p>
                <button class="package-actions__button widget-button widget-button--primary" :disabled="!canConfirmUploads || removingUploads" @click="confirmUploads">{{ $t('ui.packages.uploadApply') }}</button>
                <loading-button class="package-actions__button" color="alert" :loading="removingUploads" @click="removeUploads">{{ $t('ui.packages.uploadReset') }}</loading-button>
            </div>
            <div class="package-actions__inner" v-else-if="totalChanges && !uploading">
                <cloud-status button-class="package-actions__button package-actions__button--cloud"/>
                <p class="package-actions__text">{{ $tc('ui.packages.changesMessage', totalChanges) }}</p>
                <button-group class="package-actions__button-group" align-top type="primary" icon="update" :loading="cloudLoading" :disabled="cloudError" :more-disabled="cloudLoading || cloudError" :label="$t('ui.packages.changesApply')" @click="hasLockFile ? applyChanges() : applyChangesAll()">
                    <link-menu align="right" valign="top" :items="applyActions" color="primary"/>
                </button-group>
                <button class="package-actions__button widget-button widget-button--alert" :disabled="!canResetChanges && !confirmed.length" @click="resetChanges">{{ $t('ui.packages.changesReset') }}</button>
            </div>
        </template>
    </package-base>
</template>

<script>
    import { mapState, mapGetters } from 'vuex';

    import PackageBase from './Packages/PackageBase';
    import PackageUploads from './Packages/PackageUploads';
    import ComposerPackage from './Packages/ComposerPackage';
    import CloudStatus from '../fragments/CloudStatus';
    import ButtonGroup from '../widgets/ButtonGroup';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
    import LinkMenu from 'contao-package-list/src/components/fragments/LinkMenu';

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
        components: { PackageBase, PackageUploads, ComposerPackage, LoadingButton, CloudStatus, ButtonGroup, LinkMenu },

        data: () => ({
            hasLockFile: true,
        }),

        computed: {
            ...mapGetters('cloud', { cloudLoading: 'isLoading', cloudError: 'hasError' }),
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

            applyActions() {
                if (!this.hasLockFile) {
                    return [
                        {
                            label: this.$t('ui.packages.changesDryrun'),
                            action: this.dryrunChanges,
                        }
                    ];
                }

                return [
                    {
                        label: this.$t('ui.packages.changesDryrun'),
                        action: this.dryrunChanges,
                    },
                    {
                        label: this.$t('ui.packages.changesDryrunAll'),
                        action: this.dryrunChangesAll,
                    },
                    {
                        label: this.$t('ui.packages.changesApplyAll'),
                        action: this.applyChangesAll,
                    },
                ];
            },
        },

        methods: {
            openFileSelector() {
                if (!this.$refs.uploader) {
                    return;
                }

                this.$refs.uploader.openFileSelector();
            },

            confirmUploads() {
                this.$store.dispatch('packages/uploads/confirmAll');
            },

            async removeUploads() {
                await this.$store.dispatch('packages/uploads/removeAll');
            },

            dryrunChanges() {
                this.$store.dispatch('packages/apply', { dry_run: true });
            },

            dryrunChangesAll() {
                this.$store.dispatch('packages/apply', { dry_run: true, update_all: true });
            },

            async applyChanges() {
                await this.$store.dispatch('packages/apply');
                await this.$store.dispatch('packages/load');
            },

            async applyChangesAll() {
                await this.$store.dispatch('packages/apply', { update_all: true });
                await this.$store.dispatch('packages/load');
            },

            resetChanges() {
                this.$store.commit('packages/reset');
                this.$store.dispatch('packages/uploads/unconfirmAll');
            },
        },

        mounted() {
            this.$store.dispatch('server/composer/get').then((result) => {
                this.hasLockFile = result.lock.found;
            });
        }
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
