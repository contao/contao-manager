<template>
    <package-base>
        <div class="package-list">
            <loader v-if="packages === null" class="package-list__status">
                <p>{{ 'ui.packagelist.loading' | translate }}</p>
            </loader>

            <template v-else>
                <h2 class="package-list__headline" v-if="showHeadlines">{{ 'ui.packagelist.added' | translate }}</h2>
                <root-package :package="requiredPackages['contao/manager-bundle']" v-if="requiredPackages['contao/manager-bundle']"/>
                <local-package v-for="item in visibleRequired" :package="item" :key="item.name"/>
                <local-package v-for="item in addedPackages" :package="item" :key="item.name"/>

                <h2 class="package-list__headline" v-if="showHeadlines">{{ 'ui.packagelist.installed' | translate }}</h2>
                <root-package :package="packages['contao/manager-bundle']" v-if="packages['contao/manager-bundle']"/>
                <local-package v-for="item in visibleInstalled" :package="item" :key="item.name"/>
            </template>
        </div>

        <div class="package-actions__inner" slot="actions" v-if="totalChanges">
            <p class="package-actions__text">{{ $t('ui.packages.changesMessage', { total: totalChanges }, totalChanges) }}</p>
            <button class="package-actions__button widget-button" @click="dryrunChanges">{{ 'ui.packages.changesDryrun' | translate }}</button>
            <button class="package-actions__button widget-button widget-button--primary" @click="applyChanges">{{ 'ui.packages.changesApply' | translate }}</button>
            <button class="package-actions__button widget-button widget-button--alert" :disabled="!canResetChanges" @click="resetChanges">{{ 'ui.packages.changesReset' | translate }}</button>
        </div>
    </package-base>
</template>

<script>
    import { mapState, mapGetters } from 'vuex';

    import PackageBase from './Packages/Base';
    import Loader from '../fragments/Loader';
    import LocalPackage from './Packages/LocalPackage';
    import RootPackage from './Packages/RootPackage';

    export default {
        components: { PackageBase, Loader, RootPackage, LocalPackage },

        computed: {
            ...mapState('packages', {
                'packages': 'installed',
                'addedPackages': 'add',
                'requiredPackages': 'required',
            }),
            ...mapGetters('packages', [
                'totalChanges',
                'hasAdded',
                'packageAdded',
                'packageInstalled',
                'canResetChanges',
                'visibleRequired',
                'visibleInstalled'
            ]),

            showHeadlines: vm => vm.hasAdded && vm.packages.length,
        },

        methods: {
            dryrunChanges() {
                this.$store.dispatch('packages/apply', true);
            },

            applyChanges() {
                this.$store.dispatch('packages/apply').then(() => this.$store.dispatch('packages/load', true));
            },

            resetChanges() {
                this.$store.commit('packages/reset');
            },
        },

        mounted() {
            if (null === this.packages) {
                this.$store.dispatch('packages/load');
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
