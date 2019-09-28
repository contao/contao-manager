<template>
    <package
        :name="packageName"
        :title="packageTitle"
        :logo="data.logo"
        :badge="badge"
        :description="data.description"
        :hint="packageHint"
        :hint-close="hintClose"
        @close-hint="restore"
    >
        <template slot="additional">
            <strong class="package__version package__version--additional" v-if="data.version">
                {{ 'ui.package.version' | translate({ version: data.version }) }}
            </strong>
            <span v-for="(item,k) in additional" :key="k">{{ item }}</span>
        </template>

        <template slot="release">
            <slot name="release">
                <fieldset>
                    <input
                        ref="constraint"
                        type="text"
                        :placeholder="constraintPlaceholder"
                        v-model="constraint"
                        :class="{ disabled: willBeRemoved || (!isInstalled && !willBeInstalled && !isRequired), error: constraintError }"
                        :disabled="!constraintEditable || willBeRemoved || (!isInstalled && !willBeInstalled && !isRequired)"
                        @keypress.enter.prevent="saveConstraint"
                        @keypress.esc.prevent="resetConstraint"
                        @blur="saveConstraint"
                    >
                    <button
                        :class="{ 'widget-button widget-button--gear': true, rotate: constraintValidating }"
                        @click="editConstraint"
                        :disabled="willBeRemoved || (!isInstalled && !willBeInstalled && !isRequired)"
                    >{{ 'ui.package.editConstraint' | translate }}</button>
                </fieldset>
                <div class="package__version package__version--release" v-if="data.version">
                    <strong>{{ 'ui.package.version' | translate({ version: data.version }) }}</strong>
                    <time :dateTime="data.time" v-if="data.time">({{ data.time | datimFormat }})</time>
                </div>
            </slot>
        </template>

        <template slot="actions" v-if="updateOnly">
            <details-button :name="data.name" v-if="data.name"/>
            <button class="widget-button widget-button--update" :disabled="isModified" @click="update">{{ 'ui.package.updateButton' | translate }}</button>
        </template>
        <template slot="actions" v-else>
            <slot name="actions">
                <details-button :name="data.name" v-if="data.name"/>
                <button class="widget-button widget-button--alert widget-button--trash" v-if="isRequired" @click="uninstall" :disabled="willBeRemoved">{{ 'ui.package.removeButton' | translate }}</button>
                <button-group :label="$t('ui.package.updateButton')" icon="update" v-else-if="isInstalled" :disabled="isModified" @click="update">
                    <button class="widget-button widget-button--alert widget-button--trash" @click="uninstall" :disabled="willBeRemoved">{{ 'ui.package.removeButton' | translate }}</button>
                </button-group>
                <install-button :data="data" v-else/>
            </slot>
        </template>

    </package>
</template>

<script>
    import Vue from 'vue';
    import { mapGetters } from 'vuex';

    import Package from './Package';
    import ButtonGroup from '../../widgets/ButtonGroup';
    import DetailsButton from 'contao-package-list/src/components/fragments/DetailsButton';
    import InstallButton from '../../fragments/InstallButton';

    export default {
        components: { Package, ButtonGroup, InstallButton, DetailsButton },

        props: {
            data: {
                type: Object,
                required: true,
            },
            name: String,
            title: String,
            hint: String,
            updateOnly: Boolean,
            hidePackagist: Boolean,
        },

        data: () => ({
            constraint: '',
            constraintEditable: false,
            constraintValidating: false,
            constraintError: false,
        }),

        computed: {
            ...mapGetters('packages', [
                'installed',
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

            packageName() {
                if (this.name || this.name === '') {
                    return this.name;
                }

                if (this.data.name === this.data.title) {
                    return '';
                }

                return this.data.name;
            },

            packageTitle() {
                if (this.title || this.title === '') {
                    return this.title;
                }

                return this.data.title || this.data.name;
            },

            packageHint() {
                if (this.hint) {
                    return this.hint;
                }

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

                if (this.data.abandoned) {
                    return {
                        title: this.data.replacement && this.$t('ui.package.abandonedReplace', { replacement: this.data.replacement }) || this.$t('ui.package.abandonedText'),
                        text: this.$t('ui.package.abandoned'),
                    };
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
                    additionals.push(this.$t('ui.package.additionalDownloads', { count: Vue.filter('numberFormat')(this.data.downloads) }, this.data.downloads));
                }

                if (this.data.favers) {
                    additionals.push(this.$t('ui.package.additionalStars', { count: this.data.favers }, this.data.favers));
                }

                return additionals;
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

                return this.installed[this.data.name].constraint;
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
                this.$store.commit('packages/uploads/unconfirm', this.data.name);
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
                    this.$store.commit('packages/uploads/unconfirm', this.data.name);
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
