<template>
    <composer-package
        :data="packageData"
        :hint="hint"
        :uncloseable-hint="uncloseableHint"
        :update-only="updateOnly"
    >
        <template #release v-if="isUpload">
            <fieldset>
                <input type="text" class="disabled" :title="$t('ui.package.privateTitle')" :value="$t('ui.package.private')" disabled>
                <button class="widget-button widget-button--gear" :title="$t('ui.package.uploadConstraint')" disabled>{{ $t('ui.package.private') }}</button>
            </fieldset>
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
