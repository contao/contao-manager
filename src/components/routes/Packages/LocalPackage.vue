<template>
    <composer-package
        :data="packageData"
        :hint="hint"
        :uncloseable-hint="uncloseableHint"
    >
        <slot name="actions" slot="actions"/>
    </composer-package>
</template>

<script>
    import metadata from 'contao-package-list/src/mixins/metadata';
    import packageStatus from "../../../mixins/packageStatus";
    import ComposerPackage from './ComposerPackage';
    import PackageConstraint from "../../fragments/PackageConstraint";

    export default {
        mixins: [metadata, packageStatus],
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
            packageData: vm => Object.assign(
                {},
                vm.installed[vm.data.name] || {},
                vm.metadata || {},
                vm.data
            ),
        },
    };
</script>
