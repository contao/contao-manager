<template>
    <composer-package :data="data" :hide-packagist="isPrivate">
        <template slot="release" v-if="isPrivate">
            <p class="package__release-description">
                <img src="../../../assets/images/buy.svg" width="24" height="24" alt=""/>
                <strong>{{ $t('ui.package.proprietaryTitle') }}</strong><br>
                {{ $t('ui.package.proprietaryText') }}
            </p>
        </template>
        <template slot="release" v-else-if="isIncompatible">
            <p class="package__release-description">
                <img src="../../../assets/images/incompatible.svg" width="24" height="24" alt=""/>
                <strong>{{ $t('ui.package.incompatibleTitle') }}</strong><br>
                {{ $t('ui.package.incompatibleText') }}
            </p>
        </template>

        <template slot="actions" v-if="isPrivate">
            <a class="widget-button widget-button--primary widget-button--link" target="_blank" :href="data.homepage">{{ 'ui.package.homepage' | translate }}</a>
        </template>
        <template slot="actions" v-else-if="isIncompatible">
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

                return null;
            },

            isPrivate() {
                return !!this.data.private;
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
