<template>
    <package-details :local="data" :dependents="dependents">
        <template #package-actions>
            <slot name="package-actions">
                <template v-if="isInstalled">
                    <package-constraint :data="data" v-if="!isFeature && isVisible"/>
                    <p class="package-popup__installed">
                        <strong>{{ $t('ui.package.installed') }}</strong>
                        <time :dateTime="installedTime" v-if="installedTime" :title="datimFormat(installedTime)">{{ $t('ui.package.version', { version: installedVersion }) }}</time>
                        <template v-else>{{ $t('ui.package.version', { version: installedVersion }) }}</template>
                    </p>
                </template>
                <template v-else-if="canBeInstalled || isRequired">
                    <install-button :data="data"/>
                    <package-constraint :data="data" v-if="isAdded || isRequired"/>
                </template>
                <a class="widget-button widget-button--primary widget-button--link" target="_blank" :href="metadata.homepage" v-else-if="isPrivate">{{ $t('ui.package.homepage') }}</a>
                <div v-else></div>
            </slot>
        </template>
        <template #package-update v-if="metadata.update && metadata.update.valid && !metadata.update.latest">
            <p class="package-popup__update"><strong>{{ $t('ui.package.update') }}:</strong> {{ $t('ui.package.version', { version: metadata.update.version}) }} ({{ $t('ui.package-details.released') }} {{ datimFormat(metadata.update.time, 'short', 'long') }})</p>
        </template>
        <template #package-update v-else-if="!isCompatible">
            <p class="package-popup__incompatible">{{ $t('ui.package.incompatible', { package: data.name, constraint: packageConstraint('contao/manager-bundle') }) }}</p>
        </template>
        <template #suggest-actions="{ name }">
            <install-button inline small :data="{ name }" v-if="packageSuggested(name)"/>
        </template>
        <template #features-actions="{ name }">
            <install-button inline small :data="{ name }" v-if="hasRoot && !packageInstalled(name) && !packageRoot(name)"/>
        </template>
    </package-details>
</template>

<script>
    import { mapGetters, mapState } from 'vuex';
    import datimFormat from 'contao-package-list/src/filters/datimFormat';
    import packageStatus from '../../mixins/packageStatus';

    import PackageDetails from 'contao-package-list/src/components/fragments/PackageDetails';
    import InstallButton from './InstallButton';
    import PackageConstraint from './PackageConstraint';

    export default {
        mixins: [packageStatus],
        components: { PackageConstraint, PackageDetails, InstallButton },

        computed: {
            ...mapState('packages', { allInstalled: 'installed' }),
            ...mapGetters('packages', ['packageConstraint']),

            current: vm => vm.$route.query.p,
            data: vm => vm.add[vm.current] || (vm.allInstalled && vm.allInstalled[vm.current]) || ({ name: vm.current }),

            dependents() {
                if (!this.allInstalled[this.data.name]?.dependents) {
                    return null;
                }

                const deps = {};
                const conditions = ['requires', 'replaces', 'provides', 'conflicts'];

                Object.values(this.allInstalled[this.data.name].dependents).forEach(dep => {
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
            datimFormat,
        }
    };
</script>

<style lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.package-popup {
    &__installed {
        strong {
            margin-right: 5px;
        }

        @include defaults.screen(600) {
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

    &__update {
        margin: 0 0 20px;
        padding: 10px 20px 10px 50px;
        color: var(--clr-btn);
        background: var(--btn-primary) url('../../assets/images/button-update.svg') 15px 50% no-repeat;
        background-size: 23px 23px;
        border-radius: var(--border-radius);
    }

    &__incompatible {
        margin: 0 0 20px;
        padding: 10px 20px 10px 50px;
        color: var(--clr-btn);
        background: var(--contao) url('../../assets/images/button-incompatible.svg') 15px 50% no-repeat;
        background-size: 23px 23px;
        border-radius: var(--border-radius);
    }

    &__funding + .package-popup__update {
        margin-top: -10px;
    }
}
</style>
