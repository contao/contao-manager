<template>
    <composer-package
        :data="packageData"
        :hint="hint"
        :uncloseable-hint="uncloseableHint"
    >
        <template #release v-if="isUpload">
            <package-constraint
                disabled
                :data="data"
                :input-title="$t('ui.package.privateTitle')"
                :input-value="$t('ui.package.private')"
                :button-title="$t('ui.package.uploadConstraint')"
                :button-value="$t('ui.package.private')"
            />
            <div class="package__version package__version--release" v-if="data.version">
                <strong>{{ $t('ui.package.version', { version: data.version }) }}</strong>
                <time :dateTime="data.time" v-if="data.time">({{ data.time | datimFormat }})</time>
            </div>
        </template>

        <slot name="actions" slot="actions"/>
    </composer-package>
</template>

<script>
    import { mapState } from 'vuex';

    import metadata from 'contao-package-list/src/mixins/metadata';
    import ComposerPackage from './ComposerPackage';
    import PackageConstraint from "../../fragments/PackageConstraint";

    export default {
        mixins: [metadata],
        components: {PackageConstraint, ComposerPackage },

        props: {
            data: {
                type: Object,
                required: true,
            },
            hint: String,
            uncloseableHint: Boolean,
        },

        computed: {
            ...mapState('packages', ['installed']),

            isUpload: vm => vm.data['installation-source'] === 'dist'
                && vm.data.dist
                && (new RegExp('/contao-manager/packages/[^/]+.zip$', 'i')).test(vm.data.dist.url),

            packageData: vm => Object.assign(
                {},
                vm.installed[vm.data.name] || {},
                vm.metadata || {},
                vm.data
            ),
        },
    };
</script>
