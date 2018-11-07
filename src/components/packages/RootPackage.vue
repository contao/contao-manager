<template>
    <package :package="data" update-only>
        <img src="../../assets/images/logo.svg" slot="logo">

    </package>
</template>

<script>
    import Package from './Package';

    export default {
        components: { Package },

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
                        name: 'Contao Open Source CMS',
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
