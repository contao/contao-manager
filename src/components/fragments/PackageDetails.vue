<template>
    <package-details>
        <template #package-actions>
            <template v-if="isInstalled">
                <strong>{{ $t('ui.package.installed') }}</strong>
                <p>{{ $t('ui.package.version', { version: installedPackage.version }) }}</p>
                <time :dateTime="installedPackage.time">({{ installedPackage.time | datimFormat }})</time>
            </template>
            <a class="widget-button widget-button--primary widget-button--link" target="_blank" :href="metadata.homepage" v-else-if="metadata && metadata.private">{{ $t('ui.package.homepage') }}</a>
            <install-button inline :data="data" v-else-if="canBeInstalled"/>
        </template>
        <template #suggest-actions="{ name }">
            <install-button inline small :data="{ name }" v-if="isSuggested(name)"/>
        </template>
    </package-details>
</template>

<script>
    import { mapState, mapGetters } from 'vuex';
    import metadata from 'contao-package-list/src/mixins/metadata';

    import PackageDetails from 'contao-package-list/src/components/fragments/PackageDetails';
    import InstallButton from './InstallButton';

    export default {
        mixins: [metadata],
        components: { PackageDetails, InstallButton },

        computed: {
            ...mapState('packages', ['installed']),

            ...mapGetters('packages', [
                'packageInstalled',
                'packageAdded',
                'isSuggested',
            ]),

            data: vm => ({ name: vm.$route.query.p }),

            isInstalled: vm => vm.packageInstalled(vm.data.name),
            willBeInstalled: vm => vm.packageAdded(vm.data.name),
            canBeInstalled: vm => (vm.metadata && !vm.metadata.private)
                && ((vm.metadata && vm.metadata.supported) || vm.isSuggested(vm.data.name)),

            installedPackage: vm => vm.installed[vm.data.name],

        }
    };
</script>

<style lang="scss">

</style>
