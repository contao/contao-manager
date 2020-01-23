<template>
    <package-details :filter-features="filterFeatures">
        <template #package-actions>
            <template v-if="isInstalled">
                <install-button small :data="data" v-if="isFeature"/>
                <package-constraint :data="data" v-else/>
                <p class="package-popup__installed">
                    <strong>{{ $t('ui.package.installed') }}</strong>
                    <time :dateTime="installedTime" v-if="installedTime" :title="installedTime | datimFormat">{{ $t('ui.package.version', { version: installedVersion }) }}</time>
                    <template v-else>{{ $t('ui.package.version', { version: installedVersion }) }}</template>
                </p>
            </template>
            <install-button :data="data" v-else-if="canBeInstalled || isRequired"/>
            <a class="widget-button widget-button--primary widget-button--link" target="_blank" :href="metadata.homepage" v-else-if="isPrivate">{{ $t('ui.package.homepage') }}</a>
            <div v-else></div>
        </template>
        <template #suggest-actions="{ name }">
            <install-button inline small :data="{ name }" v-if="isSuggested(name)"/>
        </template>
        <template #features-actions="{ name }">
            <install-button inline small :data="{ name }" v-if="!packageInstalled(name)"/>
        </template>
    </package-details>
</template>

<script>
    import { mapState, mapGetters } from 'vuex';
    import packageStatus from '../../mixins/packageStatus';

    import PackageDetails from 'contao-package-list/src/components/fragments/PackageDetails';
    import InstallButton from './InstallButton';
    import PackageConstraint from './PackageConstraint';

    export default {
        mixins: [packageStatus],
        components: { PackageConstraint, PackageDetails, InstallButton },

        computed: {
            ...mapState('packages', ['installed']),

            ...mapGetters('packages', [
                'packageInstalled',
                'packageAdded',
                'isSuggested',
            ]),

            data: vm => ({ name: vm.$route.query.p }),
        },

        methods: {
            filterFeatures(features) {
                return features.filter(name => !this.packageInstalled(name));
            }
        },
    };
</script>

<style lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .package-popup {
        &__installed {
            margin-top: 1em;

            strong {
                margin-right: 5px;
            }

            @include screen(600) {
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                text-align: center;

                strong {
                    display: block;
                    margin: 0;
                }
            }
        }
    }
</style>
