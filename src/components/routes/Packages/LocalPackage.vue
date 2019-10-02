<template>
    <composer-package
        :data="packageData"
        :name="nameOverride"
        :hint="hint"
        :uncloseable-hint="uncloseableHint"
        :update-only="updateOnly"
    >
        <template #release v-if="isUpload">
            <input type="text" class="disabled" :title="$t('ui.package.uploadConstraint')" :value="data.version" disabled>
            <p class="package__release-description package__version--release" v-if="isProvider">
                <img src="../../../assets/images/lock.svg" width="24" height="24" alt=""/>
                <strong>{{ $t('ui.package.providerTitle') }}</strong><br>
                {{ $t('ui.package.providerText') }}
            </p>
            <p class="package__release-description package__version--release" v-else>
                <img src="../../../assets/images/upload.svg" width="24" height="24" alt=""/>
                <strong>{{ $t('ui.package.uploadedTitle') }}</strong><br>
                {{ $t('ui.package.uploadedText') }}
            </p>
        </template>

        <slot name="actions" slot="actions"/>
    </composer-package>
</template>

<script>
    import { mapState } from 'vuex';

    import metadata from 'contao-package-list/src/mixins/metadata';
    import ComposerPackage from './ComposerPackage';

    export default {
        mixins: [metadata],
        components: { ComposerPackage },

        props: {
            data: {
                type: Object,
                required: true,
            },
            hint: String,
            uncloseableHint: Boolean,
            updateOnly: Boolean,
        },

        computed: {
            ...mapState('packages', ['installed']),

            isProvider: vm => vm.data.type === 'contao-provider',

            isUpload: vm => vm.data['installation-source'] === 'dist'
                && vm.data.dist
                && (new RegExp('/contao-manager/packages/[^/]+.zip$', 'i')).test(vm.data.dist.url),

            nameOverride: vm => vm.isUpload ? '' : null,

            packageData: vm => Object.assign(
                {},
                vm.installed[vm.data.name] || {},
                vm.metadata || {},
                vm.data
            ),
        },
    };
</script>
