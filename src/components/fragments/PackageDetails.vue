<template>
    <package-details :filter-features="filterFeatures" :dependents="dependents">
        <template #package-actions>
            <template v-if="isInstalled">
                <package-constraint :data="data" v-if="!isFeature && isVisible"/>
                <p class="package-popup__installed">
                    <strong>{{ $t('ui.package.installed') }}</strong>
                    <time :dateTime="installedTime" v-if="installedTime" :title="installedTime | datimFormat">{{ $t('ui.package.version', { version: installedVersion }) }}</time>
                    <template v-else>{{ $t('ui.package.version', { version: installedVersion }) }}</template>
                </p>
            </template>
            <template v-else-if="canBeInstalled || isRequired">
                <install-button :data="data"/>
                <package-constraint :data="data" v-if="isAdded || isRequired"/>
            </template>
            <a class="widget-button widget-button--primary widget-button--link" target="_blank" :href="metadata.homepage" v-else-if="isPrivate">{{ $t('ui.package.homepage') }}</a>
            <div v-else></div>
        </template>
        <template #suggest-actions="{ name }">
            <install-button inline small :data="{ name }" v-if="packageSuggested(name)"/>
        </template>
        <template #features-actions="{ name }">
            <install-button inline small :data="{ name }" v-if="!packageInstalled(name)"/>
        </template>
    </package-details>
</template>

<script>
    import { mapState } from 'vuex';
    import packageStatus from '../../mixins/packageStatus';

    import PackageDetails from 'contao-package-list/src/components/fragments/PackageDetails';
    import InstallButton from './InstallButton';
    import PackageConstraint from './PackageConstraint';

    export default {
        mixins: [packageStatus],
        components: { PackageConstraint, PackageDetails, InstallButton },

        computed: {
            ...mapState('packages', ['installed']),

            data: vm => ({ name: vm.$route.query.p }),

            dependents() {
                if (!this.installed[this.data.name]) {
                    return null;
                }

                const deps = {};
                const conditions = ['requires', 'replaces', 'provides', 'conflicts'];

                Object.values(this.installed[this.data.name].dependents).forEach(dep => {
                    if (dep.source === '__root__'
                        || !conditions.includes(dep.description)
                        || (dep.source === this.data.name && dep.description === 'replaces')
                    ) {
                        return;
                    }

                    const description = this.$t(`ui.package-details.link${dep.description[0].toUpperCase()}${dep.description.slice(1)}`);
                    let target = dep.target;

                    if (target === this.data.name && this.metadata && this.metadata.title) {
                        target = this.metadata.title;
                    }

                    deps[dep.source] = `${description} ${target} ${dep.constraint}`;
                });

                return deps;
            },
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
