<template>
    <composer-package :data="data" :private="isPrivate">
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

            isPrivate() {
                const license = this.data.license;

                if (license instanceof Array) {
                    return this.data.license.includes('proprietary');
                }

                return String(license) === 'proprietary';
            },
        },
    };
</script>
