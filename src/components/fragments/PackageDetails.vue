<template>
    <package-details>
        <template #package-actions>
            <template v-if="isInstalled">
                <p class="package-popup__installed">
                    <strong>{{ $t('ui.package.installed') }}</strong>
                    {{ $t('ui.package.version', { version: installedVersion }) }}
                    <time :dateTime="installedTime">({{ installedTime | datimFormat }})</time>
                </p>
            </template>
            <a class="widget-button widget-button--primary widget-button--link" target="_blank" :href="metadata.homepage" v-else-if="isPrivate">{{ $t('ui.package.homepage') }}</a>
            <install-button :data="data" v-else-if="canBeInstalled"/>
            <div v-else></div>
        </template>
        <template #suggest-actions="{ name }">
            <install-button inline small :data="{ name }" v-if="isSuggested(name)"/>
        </template>
        <template #features-actions="{ name }">
            <install-button inline small :data="{ name }"/>
        </template>
    </package-details>
</template>

<script>
    import { mapState, mapGetters } from 'vuex';
    import packageStatus from '../../mixins/packageStatus';

    import PackageDetails from 'contao-package-list/src/components/fragments/PackageDetails';
    import InstallButton from './InstallButton';

    export default {
        mixins: [packageStatus],
        components: { PackageDetails, InstallButton },

        computed: {
            ...mapState('packages', ['installed']),

            ...mapGetters('packages', [
                'packageInstalled',
                'packageAdded',
                'isSuggested',
            ]),

            data: vm => ({ name: vm.$route.query.p }),
        }
    };
</script>

<style lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .package-popup {
        &__installed {
            strong {
                display: block;
            }

            @include screen(600) {
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                text-align: center;
            }
        }
    }
</style>
