<template>
    <composer-package
        shave-description
        :hide-packagist="isUpload"
        :data="metadata || data"
        :name="nameOverride"
        :hint="hint"
    >
        <template slot="release" v-if="isUpload">
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

        <template slot="actions"><slot name="actions"/></template>
    </composer-package>
</template>

<script>
    import metadata from '../../../mixins/metadata';
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
        },

        computed: {
            isProvider: vm => vm.data.type === 'contao-provider',

            isUpload:
                vm => vm.data['installation-source'] === 'dist'
                    && vm.data.dist
                    && (new RegExp('/contao-manager/packages/[^/]+.zip$', 'i')).test(vm.data.dist.url),

            nameOverride() {
                if (this.isUpload) {
                    return '';
                }

                return null;
            },
        },
    };
</script>
