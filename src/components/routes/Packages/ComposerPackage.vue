<template>
    <package
        :class="{ 'package--contao': isContao }"
        :title="data.title || data.name"
        :logo="data.logo"
        :badge="badge"
        :description="data.description"
        :hint="packageHint"
        :hint-close="packageHintClose"
        @close-hint="restore"
    >
        <template #additional>
            <strong class="package__version package__version--additional" v-if="data.version">
                {{ $t('ui.package.version', { version: data.version }) }}
            </strong>

            <span class="composer-package__stats composer-package__stats--license">{{ license }}</span>
            <span class="composer-package__stats composer-package__stats--downloads" v-if="data.downloads">{{ data.downloads | numberFormat }}</span>
            <span class="composer-package__stats composer-package__stats--favers" v-if="data.favers">{{ data.favers | numberFormat }}</span>
            <router-link class="composer-package__stats composer-package__stats--funding" :to="{ query: { p: data.name } }" v-if="data.funding">&nbsp;</router-link>
        </template>

        <template #release>
            <slot name="release">
                <package-constraint class="package__constraint" :data="data" />
                <div class="package__version package__version--release" v-if="data.version">
                    <strong>{{ $t('ui.package.version', { version: data.version }) }}</strong>
                    <time :dateTime="data.time" v-if="data.time">({{ data.time | datimFormat }})</time>
                </div>
            </slot>
        </template>

        <template #actions v-if="isContao">
            <details-button :name="data.name" v-if="data.name"/>
            <button class="widget-button widget-button--update" :disabled="isModified" v-if="!isRequired" @click="update">{{ $t('ui.package.updateButton') }}</button>
        </template>
        <template #actions v-else>
            <slot name="actions">
                <details-button :name="data.name" v-if="data.name"/>
                <button class="widget-button widget-button--primary widget-button--add" v-if="isMissing" @click="install" :disabled="willBeInstalled">{{ $t('ui.package.installButton') }}</button>
                <button class="widget-button widget-button--alert widget-button--trash" v-else-if="isRequired" @click="uninstall" :disabled="willBeRemoved">{{ $t('ui.package.removeButton') }}</button>
                <button-group :label="$t('ui.package.updateButton')" icon="update" v-else-if="isRootInstalled" :disabled="isModified" @click="update">
                    <button class="widget-button widget-button--alert widget-button--trash" @click="uninstall" :disabled="willBeRemoved">{{ $t('ui.package.removeButton') }}</button>
                </button-group>
            </slot>
        </template>

        <template #features v-if="packageFeatures(data.name)">
            <section class="package__features">
                <template v-for="name in packageFeatures(data.name)">
                    <feature-package :key="name" :name="name" />
                </template>
            </section>
        </template>

    </package>
</template>

<script>
    import { mapGetters, mapMutations } from 'vuex';

    import packageStatus from '../../../mixins/packageStatus';

    import Package from './Package';
    import FeaturePackage from './FeaturePackage';
    import PackageConstraint from '../../fragments/PackageConstraint';
    import ButtonGroup from '../../widgets/ButtonGroup';
    import DetailsButton from 'contao-package-list/src/components/fragments/DetailsButton';

    export default {
        mixins: [packageStatus],
        components: { Package, FeaturePackage, PackageConstraint, ButtonGroup, DetailsButton },

        props: {
            data: {
                type: Object,
                required: true,
            },
            hint: String,
            uncloseableHint: Boolean,
        },

        computed: {
            ...mapGetters('packages', ['packageFeatures']),

            license: vm => vm.data.license instanceof Array ? vm.data.license.join('/') : vm.data.license,

            packageHint() {
                if (this.hint) {
                    return this.hint;
                }

                if (this.willBeRemoved || (this.isMissing && !this.willBeInstalled)) {
                    return this.$t('ui.package.hintRemoved');
                }

                if (this.isRequired) {
                    return this.$t('ui.package.hintConstraint', { constraint: this.constraintRequired });
                }

                if (this.willBeInstalled) {
                    if (this.constraintAdded) {
                        return this.$t('ui.package.hintConstraint', { constraint: this.constraintAdded });
                    }

                    return this.$t('ui.package.hintConstraintBest');
                }

                if (this.isChanged) {
                    return this.$t(
                        'ui.package.hintConstraintChange',
                        {
                            from: this.constraintInstalled,
                            to: this.constraintChanged,
                        },
                    );
                }

                if (this.isUpdated) {
                    return this.$t('ui.package.hintConstraintUpdate');
                }

                return null;
            },

            packageHintClose() {
                if (this.uncloseableHint || (this.isRequired && !this.willBeRemoved && !this.isChanged) || (this.isMissing && !this.willBeInstalled)) {
                    return null;
                }

                if (this.isUpdated) {
                    return this.$t('ui.package.hintNoupdate');
                }

                return this.$t('ui.package.hintRevert');
            },


            packageUpdates() {
                return this.isInstalled && (
                    Object.keys(this.$store.state.packages.add).length > 0
                    || Object.keys(this.$store.state.packages.change).length > 0
                    || this.$store.state.packages.update.length > 0
                    || this.$store.state.packages.remove.length > 0
                );
            },

            badge() {
                if (this.isRequired) {
                    return {
                        title: this.$t('ui.package.requiredText'),
                        text: this.$t('ui.package.requiredTitle'),
                    };
                }

                if (this.isMissing) {
                    return {
                        title: this.$t('ui.package.removedText'),
                        text: this.$t('ui.package.removedTitle'),
                    };
                }

                if (this.data.abandoned) {
                    return {
                        title: this.data.abandoned === true ? this.$t('ui.package.abandonedText') : this.$t('ui.package.abandonedReplace', { replacement: this.data.abandoned }),
                        text: this.$t('ui.package.abandoned'),
                    };
                }

                return null;
            },
        },

        methods: {
            restore() {
                this.$store.commit('packages/restore', this.data.name);
                this.$store.commit('packages/uploads/unconfirm', this.data.name);
            },

            methods: {
                ...mapMutations('packages/details', ['setCurrent']),
            },
        },
    };
</script>

<style lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .composer-package {
        &__stats {
            display: inline-block;
            margin-right: 15px;
            padding-left: 18px;
            font-size: 13px;
            background-position: 0 50%;
            background-repeat: no-repeat;
            background-size: 13px 13px;

            &--license {
                @include screen(1024) {
                    padding-left: 0;
                }
            }

            &--downloads {
                background-image: url("~contao-package-list/src/assets/images/downloads.svg");
            }

            &--favers {
                background-image: url("~contao-package-list/src/assets/images/favers.svg");
            }

            &--funding {
                width: 48px;
                background-image: url("~contao-package-list/src/assets/images/funding.svg");
                background-size: 16px 16px;
                background-repeat: repeat-x;
                text-decoration: none !important;
            }
        }
    }
</style>
