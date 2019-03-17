<template>
    <composer-package
        shave-description
        :hide-packagist="isUpload"
        :data="metadata || data"
        :name="nameOverride"
        :title="titleOverride"
        :hint="hint"
    >
        <template slot="release" v-if="isProvider">
            <input type="text" class="disabled" :title="$t('ui.package.uploadConstraint')" :value="provided.constraint" disabled>
            <div class="package__version package__version--release" v-if="provided.version">
                <strong>{{ 'ui.package.version' | translate({ version: provided.version }) }}</strong>
                <time :dateTime="provided.time" v-if="provided.time">({{ provided.time | datimFormat }})</time>
            </div>
            <p class="package__release-description package__version--release" v-else>
                <img src="../../../assets/images/provider.svg" width="24" height="24" alt=""/>
                <strong>{{ $t('ui.package.providerTitle') }}</strong><br>
                {{ $t('ui.package.providerText') }}
            </p>
        </template>
        <template slot="release" v-else-if="isUpload">
            <input type="text" class="disabled" :title="$t('ui.package.uploadConstraint')" :value="data.version" disabled>
            <p class="package__release-description package__version--release">
                <img src="../../../assets/images/upload.svg" width="24" height="24" alt=""/>
                <strong>{{ $t('ui.package.uploadedTitle') }}</strong><br>
                {{ $t('ui.package.uploadedText') }}
            </p>
        </template>

        <template slot="actions"><slot name="actions"/></template>
    </composer-package>
</template>

<script>
    import { mapState } from 'vuex';
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
            ...mapState('packages', ['local']),

            isProvider: vm => vm.data.type === 'contao-provider',

            isUpload:
                vm => vm.data['installation-source'] === 'dist'
                    && vm.data.dist
                    && (new RegExp('/contao-manager/packages/[^/]+.zip$', 'i')).test(vm.data.dist.url),

            nameOverride() {
                if (this.isProvider) {
                    return `${this.data.name} (${this.$t('ui.package.version', { version: this.data.version })})`;
                }

                if (this.isUpload) {
                    return '';
                }

                return null;
            },

            titleOverride() {
                if (this.isProvider && !this.metadata) {
                    return this.provided.name;
                }

                return null;
            },

            provided() {
                const name = Object.keys(this.data.require)[0];
                const constraint = this.data.require[name];
                const installed = (this.local && this.local[name]) || {};

                return Object.assign({}, installed, {
                    name,
                    constraint,
                });
            },
        },
    };
</script>
