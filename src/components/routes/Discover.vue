<template>
    <discover :wrapper="wrapper" hide-themes>

        <template #package-actions="{ data }">
            <a class="widget-button widget-button--small widget-button--primary widget-button--link" target="_blank" :href="data.homepage" v-if="data && data.private">{{ $t('ui.package.homepage') }}</a>
            <install-button small inline :data="data" v-else/>
        </template>

    </discover>
</template>

<script>
    import MainLayout from '../layouts/Main';
    import Discover from 'contao-package-list/src/components/fragments/Discover';
    import InstallButton from '../fragments/InstallButton';

    export default {
        components: { Discover, InstallButton },

        computed: {
            wrapper: () => MainLayout,
        }
    };
</script>
