<template>
    <base-package
        :class="{ 'package--contao': isContao }"
        :title="packageData.title || data.name"
        :logo="packageData.logo"
        :badge="badge"
        :description="packageData.description"
        :hint="packageHint"
        :hint-close="packageHintClose"
        @close-hint="restore"
    >
        <template #hint v-if="!slotEmpty($slots.hint)"><slot name="hint" /></template>
        <template #additional>
            <div class="package__version package__version--additional" v-if="packageData.version">
                <strong :title="packageData.time ? datimFormat(packageData.time) : ''">{{ $t('ui.package.version', { version: packageData.version }) }}</strong>
                <template v-if="packageData.update">
                    <div class="package__version-update package__version-update--error" v-if="!packageData.update.valid">{{ $t('ui.package.updateUnknown') }}</div>
                    <div class="package__version-update package__version-update--available" v-else-if="!packageData.update.latest">
                        {{ $t('ui.package.updateAvailable', { version: packageData.update.version }) }}
                    </div>
                    <div class="package__version-update package__version-update--none" v-else>
                        {{ $t('ui.package.updateLatest') }}
                        <span class="package__version-latest" :title="$t('ui.package.updateConstraint')" v-if="packageData.latest && !packageData.latest.active"></span>
                    </div>
                </template>
            </div>
            <span class="composer-package__stats composer-package__stats--license" v-if="license">{{ license }}</span>
            <ul
                class="composer-package__stats composer-package__stats--versions"
                :title="`${$t('ui.package.contaoVersion')} ${packageData.contaoVersions.join(', ')}`"
                v-if="packageData.contaoVersions && !isContao"
            >
                <li class="composer-package__stats--version" v-for="(version, i) in packageData.contaoVersions" :key="i">{{ version }}</li>
            </ul>
            <router-link class="composer-package__stats composer-package__stats--funding" :to="{ query: { p: data.name } }" v-if="packageData.funding">&nbsp;</router-link>
        </template>

        <template #release>
            <slot name="release">
                <package-constraint class="package__constraint" :data="data" />
                <div class="package__version package__version--release" v-if="packageData.version">
                    <strong :title="packageData.time ? datimFormat(packageData.time) : ''">{{ $t('ui.package.version', { version: packageData.version }) }}</strong>
                    <template v-if="packageData.update">
                        <div class="package__version-update package__version-update--error" v-if="!packageData.update.valid">{{ $t('ui.package.updateUnknown') }}</div>
                        <div class="package__version-update package__version-update--available" v-else-if="!packageData.update.latest">
                            {{ $t('ui.package.updateAvailable', { version: packageData.update.version }) }}
                        </div>
                        <div class="package__version-update package__version-update--none" v-else>
                            {{ $t('ui.package.updateLatest') }}
                            <span class="package__version-latest" :title="$t('ui.package.updateConstraint')" v-if="packageData.latest && !packageData.latest.active"></span>
                        </div>
                    </template>
                </div>
            </slot>
        </template>

        <template #actions>
            <slot name="actions">
                <details-button :name="data.name" v-if="data.name" />
                <template v-if="isGranted(scopes.UPDATE)">
                    <template v-if="isContao">
                        <button class="widget-button widget-button--update" :disabled="isModified" v-if="!isRequired" @click="update">{{ $t('ui.package.updateButton') }}</button>
                    </template>
                    <template v-else>
                        <button class="widget-button widget-button--primary widget-button--add" :disabled="willBeInstalled" @click="install" v-if="isMissing">
                            {{ $t('ui.package.installButton') }}
                        </button>
                        <button class="widget-button widget-button--alert widget-button--trash" :disabled="willBeRemoved" @click="uninstall" v-else-if="isRequired">
                            {{ $t('ui.package.removeButton') }}
                        </button>
                        <template v-else-if="isRootInstalled">
                            <button-group :label="$t('ui.package.updateButton')" icon="update" :disabled="isModified" @click="update" v-if="isGranted(scopes.INSTALL)">
                                <button class="widget-button widget-button--alert widget-button--trash" :disabled="willBeRemoved" @click="uninstall">
                                    {{ $t('ui.package.removeButton') }}
                                </button>
                            </button-group>
                            <button class="widget-button widget-button--update" :disabled="isModified" @click="update" v-else>
                                {{ $t('ui.package.updateButton') }}
                            </button>
                        </template>
                    </template>
                </template>
            </slot>
        </template>

        <template #features v-if="Object.keys(packageFeatures(data.name) || {}).length">
            <section class="package__features">
                <feature-package :name="name" v-for="name in Object.keys(packageFeatures(data.name))" :key="name" />
            </section>
        </template>
    </base-package>
</template>

<script>
import { mapGetters } from 'vuex';
import scopes from '../../../scopes';
import datimFormat from 'contao-package-list/src/filters/datimFormat';
import numberFormat from 'contao-package-list/src/filters/numberFormat';
import slotEmpty from 'contao-package-list/src/filters/slotEmpty';
import packageStatus from '../../../mixins/packageStatus';

import BasePackage from './BasePackage';
import FeaturePackage from './FeaturePackage';
import PackageConstraint from '../../fragments/PackageConstraint';
import ButtonGroup from '../../widgets/ButtonGroup';
import DetailsButton from 'contao-package-list/src/components/fragments/DetailsButton';

export default {
    mixins: [packageStatus],
    components: { BasePackage, FeaturePackage, PackageConstraint, ButtonGroup, DetailsButton },

    props: {
        data: {
            type: Object,
            required: true,
        },
        hint: String,
        uncloseableHint: Boolean,
    },

    computed: {
        ...mapGetters('auth', ['isGranted']),
        ...mapGetters('packages', ['packageFeatures']),
        scopes: () => scopes,

        packageData: (vm) => Object.assign({}, vm.data, vm.installed[vm.data.name] || {}, vm.metadata || {}),

        license: (vm) => (vm.packageData.license instanceof Array ? vm.packageData.license.join('/') : vm.packageData.license),

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

                if (!this.isCompatible) {
                    return this.$t('ui.package.incompatibleConstraint', { constraint: this.packageConstraint('contao/manager-bundle') });
                }

                return this.$t('ui.package.hintConstraintBest');
            }

            if (this.isChanged) {
                return this.$t('ui.package.hintConstraintChange', {
                    from: this.constraintInstalled,
                    to: this.constraintChanged,
                });
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
            return (
                this.isInstalled &&
                (Object.keys(this.$store.state.packages.add).length > 0 ||
                    Object.keys(this.$store.state.packages.change).length > 0 ||
                    this.$store.state.packages.update.length > 0 ||
                    this.$store.state.packages.remove.length > 0)
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

            if (this.packageData.abandoned) {
                return {
                    title:
                        this.packageData.abandoned === true
                            ? this.$t('ui.package.abandonedText')
                            : this.$t('ui.package.abandonedReplace', { replacement: this.packageData.abandoned }),
                    text: this.$t('ui.package.abandoned'),
                };
            }

            return null;
        },
    },

    methods: {
        datimFormat,
        numberFormat,
        slotEmpty,

        restore() {
            this.$store.commit('packages/restore', this.data.name);
            this.$store.dispatch('packages/uploads/unconfirm', this.data.name);
        },
    },
};
</script>

<style lang="scss">
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
            padding-left: 0;
        }

        &--versions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 5px;
            list-style: none;
            margin: 0 15px 0 0;
            padding: 0;
        }

        &--version {
            padding: 3px 5px;
            line-height: 1;
            border: 1px solid var(--border);
            border-radius: 4px;
        }

        &--funding {
            width: 16px;
            background-image: url('~contao-package-list/src/assets/images/funding.svg');
            background-size: 16px 16px;
            background-repeat: no-repeat;
            text-decoration: none !important;
        }
    }
}
</style>
