<template>
    <package-details>
        <template #package-actions>
            <template v-if="isTheme && !data.uploaded && !isPrivate">
                <button class="widget-button widget-button--primary widget-button--run" @click="install">{{ $t('ui.setup.create-project.install') }}</button>
                <package-constraint class="theme-details__constraint" :emit="true" :data="data" v-model="version"/>
            </template>
            <a class="widget-button widget-button--primary widget-button--link" target="_blank" :href="data.homepage || metadata.homepage" v-else-if="data.homepage || metadata.homepage">{{ $t('ui.package.homepage') }}</a>
            <a class="widget-button widget-button--primary widget-button--link" target="_blank" :href="`https://packagist.org/packages/${data.name}`" v-else-if="!isPrivate">{{ $t('ui.package-details.packagist') }}</a>
            <div v-else></div>
        </template>
    </package-details>
</template>

<script>
    import { mapState } from 'vuex';
    import PackageDetails from './PackageDetails';
    import PackageConstraint from './PackageConstraint';
    import metadata from 'contao-package-list/src/mixins/metadata';
    import packageStatus from '../../mixins/packageStatus';

    export default {
        mixins: [metadata, packageStatus],
        components: { PackageConstraint, PackageDetails },

        data: () => ({
            version: ''
        }),

        computed: {
            ...mapState('packages', { allInstalled: 'installed' }),
            ...mapState('packages/details', ['current']),

            data: vm =>  vm.allInstalled[vm.current] || { name: vm.current },
            isPrivate: vm => vm.metadata && !!vm.metadata.private,
        },

        methods: {
            install() {
                this.$store.commit('contao/installTheme', { package: this.data.name, version: this.version });
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
.theme-details {
    &__constraint {
        flex-grow: 1;
    }
}
</style>
