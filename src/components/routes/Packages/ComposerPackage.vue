<template>
    <package
        :title="data.title"
        :name="data.hideName ? '' : data.name"
        :logo="data.logo"
        :badge="badge"
        :description="data.description"
        :hint="hint"
        :hint-close="hintClose"

        :release-validating="!isPrivate && constraintValidating"
        :release-error="!isPrivate && constraintError"
        :release-disabled="!isPrivate && (willBeRemoved || (!isInstalled && !willBeInstalled && !isRequired))"

        @close-hint="restore"
    >
        <template slot="logo"><slot name="logo"/></template>

        <more :name="data.name" :homepage="data.homepage" :support="Object.assign({}, data.support)" :private="isPrivate" slot="more"/>

        <template slot="additional">
            <strong class="package__version package__version--additional" v-if="data.version">{{ 'ui.package.version' | translate({ version: data.version }) }}</strong>
            <span v-for="(item,k) in additional" :key="k">{{ item }}</span>
        </template>

        <template slot="release" v-if="isPrivate">
            <p class="package__proprietary">
                <img src="../../../assets/images/buy.svg" width="24" height="24"/>
                <strong>{{ $t('ui.package.proprietaryTitle') }}</strong><br>
                {{ $t('ui.package.proprietaryText') }}
            </p>
        </template>
        <template slot="release" v-else>
            <fieldset>
                <input ref="constraint" type="text" :placeholder="constraintPlaceholder" v-model="constraint" :disabled="!constraintEditable || willBeRemoved || (!isInstalled && !willBeInstalled && !isRequired)" @keypress.enter.prevent="saveConstraint" @keypress.esc.prevent="resetConstraint" @blur="saveConstraint">
                <button class="widget-button" @click="editConstraint" :disabled="willBeRemoved || (!isInstalled && !willBeInstalled && !isRequired)">{{ 'ui.package.editConstraint' | translate }}</button>
            </fieldset>
            <div class="package__version package__version--release" v-if="data.version">
                <strong>{{ 'ui.package.version' | translate({ version: data.version }) }}</strong>
                <time :dateTime="data.time">({{ released }})</time>
            </div>
        </template>

        <template slot="actions" v-if="isPrivate">
            <a class="widget-button widget-button--primary widget-button--link" target="_blank" :href="data.homepage">{{ 'ui.package.homepage' | translate }}</a>
        </template>
        <template slot="actions" v-else-if="updateOnly">
            <button :class="{ 'widget-button': true, 'widget-button--update': !isModified, 'widget-button--check': isModified }" :disabled="isModified" @click="update">{{ 'ui.package.updateButton' | translate }}</button>
        </template>
        <template slot="actions" v-else>
            <button class="widget-button widget-button--alert widget-button--trash" v-if="isRequired" @click="uninstall" :disabled="willBeRemoved">{{ 'ui.package.removeButton' | translate }}</button>
            <button-group :label="$t('ui.package.updateButton')" icon="update" v-else-if="isInstalled" :disabled="isModified" @click="update">
                <!--<button class="widget-button widget-button&#45;&#45;primary widget-button&#45;&#45;power" key="enable" v-if="isModified">Enable</button>-->
                <!--<button class="widget-button widget-button&#45;&#45;power" key="disable" v-if="!isModified">Disable</button>-->
                <button class="widget-button widget-button--alert widget-button--trash" @click="uninstall" :disabled="willBeRemoved">{{ 'ui.package.removeButton' | translate }}</button>
            </button-group>
            <button class="widget-button widget-button--primary widget-button--add" v-else @click="install" :disabled="isIncompatible || isInstalled || willBeInstalled">{{ 'ui.package.installButton' | translate }}</button>
        </template>

    </package>
</template>

<script>
    import Vue from 'vue';
    import { mapGetters } from 'vuex';

    import Package from './Package';
    import More from './More';
    import ButtonGroup from '../../widgets/ButtonGroup';

    export default {
        components: { Package, More, ButtonGroup },

        props: {
            data: {
                type: Object,
                required: true,
            },
            updateOnly: {
                type: Boolean,
                default: false,
            },
        },

        data: () => ({
            constraint: '',
            constraintEditable: false,
            constraintValidating: false,
            constraintError: false,
        }),

        computed: {
            ...mapGetters('packages', [
                'packageInstalled',
                'packageRequired',
                'packageAdded',
                'packageUpdated',
                'packageChanged',
                'packageRemoved'
            ]),

            isInstalled: vm => vm.packageInstalled(vm.data.name),
            isRequired: vm => vm.packageRequired(vm.data.name),
            isChanged: vm => vm.packageChanged(vm.data.name),
            isUpdated: vm => vm.packageUpdated(vm.data.name),
            willBeRemoved: vm => vm.packageRemoved(vm.data.name),
            willBeInstalled: vm => vm.packageAdded(vm.data.name),
            isModified: vm => vm.isUpdated || vm.isChanged || vm.willBeRemoved || vm.willBeInstalled,

            packageUpdates() {
                return this.isInstalled && (
                    Object.keys(this.$store.state.packages.add).length > 0
                    || Object.keys(this.$store.state.packages.change).length > 0
                    || this.$store.state.packages.update.length > 0
                    || this.$store.state.packages.remove.length > 0
                );
            },

            isPrivate() {
                if (this.data.version || this.data.version !== false) {
                    return false;
                }

                const license = this.data.license;

                if (license instanceof Array) {
                    return this.data.license.includes('proprietary');
                }

                return String(license) === 'proprietary';
            },

            isIncompatible() {
                if (this.updateOnly || this.isRequired) {
                    return false;
                }

                if (this.data.type === 'contao-bundle') {
                    return !this.data.extra || !this.data.extra['contao-manager-plugin'];
                }

                if (!this.isInstalled && (!this.data.managed || !this.data.supported)) {
                    return true;
                }

                return false;
            },

            badge() {
                if (this.isRequired) {
                    return {
                        title: this.$t('ui.package.requiredText'),
                        text: this.$t('ui.package.requiredTitle'),
                    };
                }

                if (this.isIncompatible) {
                    return {
                        title: this.$t('ui.package.incompatibleText'),
                        text: this.$t('ui.package.incompatibleTitle'),
                    };
                }

                if (this.data.abandoned) {
                    return {
                        title: this.data.replacement === true && this.$t('ui.package.abandonedText') || this.$t('ui.package.replacement', { replacement: this.data.replacement }),
                        text: this.$t('ui.package.abandonedTitle'),
                    };
                }
            },

            hint() {
                if (this.willBeRemoved) {
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

            hintClose() {
                if (this.isRequired && !this.willBeRemoved && !this.isChanged) {
                    return null;
                }

                if (this.isUpdated) {
                    return this.$t('ui.package.hintNoupdate');
                }

                return this.$t('ui.package.hintRevert');
            },

            additional() {
                const additionals = [];

                if (this.data.license) {
                    if (this.data.license instanceof Array) {
                        additionals.push(this.data.license.join('/'));
                    } else {
                        additionals.push(this.data.license);
                    }
                }

                if (this.data.downloads) {
                    additionals.push(this.$t('ui.package.additionalDownloads', { count: this.data.downloads }, this.data.downloads));
                }

                if (this.data.favers) {
                    additionals.push(this.$t('ui.package.additionalStars', { count: this.data.favers }, this.data.favers));
                }

                return additionals;
            },

            released() {
                if (this.data.time === undefined) {
                    return '';
                }

                return new Date(this.data.time).toLocaleString();
            },

            constraintPlaceholder() {
                if (!Object.keys(this.$store.state.packages.installed).includes(this.data.name)) {
                    return this.$t('ui.package.latestConstraint');
                }

                return '';
            },

            constraintInstalled() {
                if (!this.isInstalled) {
                    return null;
                }

                return this.$store.state.packages.installed[this.data.name].constraint;
            },

            constraintRequired() {
                if (!this.isRequired) {
                    return null;
                }

                if (this.isChanged) {
                    return this.constraintChanged;
                }

                return this.$store.state.packages.required[this.data.name].constraint;
            },

            constraintAdded() {
                if (!this.willBeInstalled) {
                    return null;
                }

                return this.$store.state.packages.add[this.data.name].constraint;
            },

            constraintChanged() {
                if (!this.isChanged) {
                    return null;
                }

                return this.$store.state.packages.change[this.data.name];
            },
        },

        methods: {
            restore() {
                this.$store.commit('packages/restore', this.data.name);
                this.resetConstraint();
            },

            install() {
                /* eslint-disable no-underscore-dangle */
                const data = Object.assign({}, this.data);
                delete data._highlightResult;

                this.$store.commit('packages/add', data);
            },

            update() {
                this.$store.commit('packages/update', this.data.name);
            },

            uninstall() {
                if (this.willBeInstalled && !this.isInstalled) {
                    this.$store.commit('packages/restore', this.data.name);
                } else {
                    this.$store.commit('packages/restore', this.data.name);
                    this.$store.commit('packages/remove', this.data.name);
                }
            },

            editConstraint() {
                if (this.constraintValidating) {
                    return;
                }

                this.constraintEditable = true;

                this.$nextTick(() => {
                    this.$refs.constraint.focus();
                });
            },

            saveConstraint() {
                if (!this.constraintEditable) {
                    return;
                }

                this.constraintEditable = false;
                this.constraintError = false;

                if ((this.isInstalled && (!this.constraint || this.constraintInstalled === this.constraint))
                    || (this.isRequired && (!this.constraint || this.constraintRequired === this.constraint))
                ) {
                    this.restore();
                    return;
                }

                if (!this.isRequired && this.willBeInstalled && !this.constraint) {
                    this.$store.commit(
                        'packages/add',
                        Object.assign({}, this.data, { constraint: null }),
                    );
                    this.resetConstraint();
                    return;
                }

                this.$refs.constraint.blur();
                this.constraintValidating = true;

                Vue.http.post('api/constraint', { constraint: this.constraint }).then(
                    (response) => {
                        this.constraintValidating = false;
                        if (response.body.valid) {
                            if (this.isInstalled || this.isRequired) {
                                this.$store.commit('packages/change', { name: this.data.name, version: this.constraint });
                            } else {
                                this.$store.commit(
                                    'packages/add',
                                    Object.assign({}, this.data, { constraint: this.constraint }),
                                );
                            }
                        } else {
                            this.constraintError = true;
                            this.$nextTick(() => this.editConstraint());
                        }
                    },
                );
            },

            resetConstraint() {
                if (this.willBeInstalled) {
                    this.constraint = this.constraintAdded;
                } else if (this.isChanged) {
                    this.constraint = this.constraintChanged;
                } else if (this.isInstalled) {
                    this.constraint = this.constraintInstalled;
                } else if (this.isRequired) {
                    this.constraint = this.constraintRequired;
                }

                if (!this.constraintEditable) {
                    return;
                }

                this.constraintEditable = false;
                this.constraintError = false;
                this.constraintValidating = false;
            },
        },

        watch: {
            constraintAdded(value) {
                this.constraint = value;
            },

            constraintChanged(value) {
                this.constraint = value || this.constraintInstalled || this.constraintRequired;
            },
        },

        mounted() {
            this.resetConstraint();
        },
    };
</script>
