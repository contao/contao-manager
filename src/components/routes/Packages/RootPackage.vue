<template>
    <composer-package hide-packagist :name="''" :update-only="packageInstalled('contao/manager-bundle')" :data="data">
        <img src="../../../assets/images/logo.svg" alt="" slot="logo">

        <template slot="actions" v-if="!packageInstalled('contao/manager-bundle')">
            <button class="widget-button widget-button--update" disabled>{{ $t('ui.package.updateButton') }}</button>
        </template>
    </composer-package>
</template>

<script>
    import { mapGetters } from 'vuex';

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
            ...mapGetters('packages', ['packageInstalled']),

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
                            issues: 'https://github.com/contao/contao/issues',
                            source: 'https://github.com/contao/contao',
                        },
                    },
                );
            },
        },
    };
</script>
