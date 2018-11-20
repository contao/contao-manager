<template>
    <composer-package :data="data" :is-private="isPrivate" :badge="badge">
        <template slot="release" v-if="isPrivate">
            <p class="package__proprietary">
                <img src="../../../assets/images/buy.svg" width="24" height="24"/>
                <strong>{{ $t('ui.package.proprietaryTitle') }}</strong><br>
                {{ $t('ui.package.proprietaryText') }}
            </p>
        </template>

        <template slot="actions" v-if="isPrivate">
            <a class="widget-button widget-button--primary widget-button--link" target="_blank" :href="data.homepage">{{ 'ui.package.homepage' | translate }}</a>
        </template>
        <template slot="actions" v-if="isIncompatible">
            <button class="widget-button widget-button--primary widget-button--add" disabled>{{ $t('ui.package.installButton') }}</button>
        </template>
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
                        title: this.package._highlightResult.title.value,
                        hideName: this.package.title === this.package.name,
                        description: this.package._highlightResult.description.value,
                    }
                );
            },

            badge() {
                if (this.isIncompatible) {
                    return {
                        title: this.$t('ui.package.incompatibleText'),
                        text: this.$t('ui.package.incompatibleTitle'),
                    };
                }
            },

            isPrivate() {
                const license = this.data.license;

                if (license instanceof Array) {
                    return this.data.license.includes('proprietary');
                }

                return String(license) === 'proprietary';
            },

            isIncompatible() {
                if (this.data.type === 'contao-bundle') {
                    return !this.data.extra || !this.data.extra['contao-manager-plugin'];
                }

                return !this.data.managed || !this.data.supported;
            },
        },
    };
</script>
