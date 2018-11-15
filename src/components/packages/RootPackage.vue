<template>
    <composer-package :package="data" update-only>
        <img src="../../assets/images/logo.svg" slot="logo">

    </composer-package>
</template>

<script>
    import ComposerPackage from './ComposerPackage';

    export default {
        components: { ComposerPackage },

        props: {
            package: {
                type: Object,
                required: true,
            },
        },

        computed: {
            data() {
                return Object.assign(
                    {},
                    this.package,
                    {
                        title: 'Contao Open Source CMS',
                        description: 'Contao is an Open Source PHP Content Management System.',
                        homepage: 'https://contao.org',
                        support: {
                            docs: 'https://docs.contao.org',
                            forum: 'https://community.contao.org',
                            issues: 'https://github.com/contao/core-bundle/issues',
                            source: 'https://github.com/contao/core-bundle',
                        },
                    },
                );
            },
        },
    };
</script>
