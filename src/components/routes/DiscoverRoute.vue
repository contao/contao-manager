<template>
    <discover-list :wrapper="wrapper" hide-themes>
        <template #package-hint="{ data }">
            <p v-if="!contaoSupported(data.contaoConstraint)">{{ $t('ui.package.incompatible', { package: data.name, constraint: packageConstraint('contao/manager-bundle') }) }}</p>
        </template>
        <template #package-actions="{ data }">
            <a class="widget-button widget-button--small widget-button--primary widget-button--link" target="_blank" :href="data.homepage" v-if="data && data.private && !packageSuggested(data.name)">{{ $t('ui.package.homepage') }}</a>
            <install-button small inline :data="data" v-else />
        </template>
    </discover-list>
</template>

<script>
import { mapGetters } from 'vuex';
import MainLayout from '../layouts/MainLayout';
import DiscoverList from 'contao-package-list/src/components/fragments/DiscoverList';
import InstallButton from '../fragments/InstallButton';

export default {
    components: { DiscoverList, InstallButton },

    computed: {
        ...mapGetters('packages', ['packageSuggested', 'contaoSupported', 'packageConstraint']),

        wrapper: () => MainLayout,
    },
};
</script>
