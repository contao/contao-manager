<template>
    <package
        :title="package.title"
        :name="package.hideName ? '' : package.name"
        :logo="package.logo"
        :badge="badge"
        :description="package.description"
        :hint="hint"
        :hint-close="$t(isUpdated ? 'ui.package.hintNoupdate' : 'ui.package.hintRevert')"

        :release-validating="!isPrivate && constraintValidating"
        :release-error="!isPrivate && constraintError"
        :release-disabled="!isPrivate && (willBeRemoved || (!isInstalled && !willBeInstalled))"

        @close-hint="restore"
    >
        <more :name="package.name" :homepage="package.homepage" :support="Object.assign({}, package.support)" :private="isPrivate" slot="more"/>

        <template slot="additional">
            <strong class="package__version package__version--additional" v-if="package.version">{{ 'ui.package.version' | translate({ version: package.version }) }}</strong>
            <strong class="package__version package__version--additional" v-else-if="package.version === false"><span class="package__version--missing">{{ $t('ui.package.versionMissing') }}</span></strong>
            <span v-for="item in additional">{{ item }}</span>
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
                <input ref="constraint" type="text" :placeholder="constraintPlaceholder" v-model="constraint" :disabled="!this.constraintEditable || willBeRemoved || (!isInstalled && !willBeInstalled)" @keypress.enter.prevent="saveConstraint" @keypress.esc.prevent="resetConstraint" @blur="saveConstraint">
                <button class="widget-button" @click="editConstraint" :disabled="willBeRemoved || (!isInstalled && !willBeInstalled)">{{ 'ui.package.editConstraint' | translate }}</button>
            </fieldset>
            <div class="package__version package__version--release" v-if="package.version">
                <strong>{{ 'ui.package.version' | translate({ version: package.version }) }}</strong>
                <time :dateTime="package.time">({{ released }})</time>
            </div>
            <div class="package__version package__version--release package__version--missing" v-else-if="package.version === false">{{ $t('ui.package.versionMissing') }}</div>
        </template>

        <template slot="actions" v-if="isPrivate">
            <a class="widget-button widget-button--primary widget-button--link" target="_blank" :href="package.homepage">{{ 'ui.package.homepage' | translate }}</a>
        </template>
        <template slot="actions" v-else-if="updateOnly">
            <button :class="{ 'widget-button': true, 'widget-button--update': !isModified, 'widget-button--check': isModified }" :disabled="isModified" @click="update">{{ 'ui.package.updateButton' | translate }}</button>
        </template>
        <template slot="actions" v-else>
            <button-group :label="$t('ui.package.updateButton')" icon="update" v-if="isInstalled" :disabled="isModified" @click="update">
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

    import Package from './Package';
    import More from './More';
    import ButtonGroup from '../../widgets/ButtonGroup';

    export default {
        components: { Package, More, ButtonGroup },

        props: {
            package: {
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
            packageUpdates() {
                return this.isInstalled && (
                    Object.keys(this.$store.state.packages.add).length > 0
                    || Object.keys(this.$store.state.packages.change).length > 0
                    || this.$store.state.packages.update.length > 0
                    || this.$store.state.packages.remove.length > 0
                );
            },

            isPrivate() {
                if (this.package.version || this.package.version !== false) {
                    return false;
                }

                const license = this.package.license;

                if (license instanceof Array) {
                    return this.package.license.includes('proprietary');
                }

                return String(license) === 'proprietary';
            },

            isModified() {
                return this.isUpdated || this.isChanged || this.willBeRemoved || this.willBeInstalled;
            },

            isInstalled() {
                return Object.keys(this.$store.state.packages.installed).includes(this.package.name);
            },

            isChanged() {
                return Object.keys(this.$store.state.packages.change).includes(this.package.name);
            },

            isUpdated() {
                return this.$store.state.packages.update.includes(this.package.name);
            },

            willBeRemoved() {
                return this.$store.state.packages.remove.includes(this.package.name);
            },

            willBeInstalled() {
                return Object.keys(this.$store.state.packages.add).includes(this.package.name);
            },

            isIncompatible() {
                if (this.updateOnly) {
                    return false;
                }

                if (this.package.type === 'contao-bundle') {
                    return !this.package.extra || !this.package.extra['contao-manager-plugin'];
                }

                if (!this.isInstalled && (!this.package.managed || !this.package.supported)) {
                    return true;
                }

                return false;
            },

            badge() {
                if (this.isIncompatible) {
                    return {
                        title: this.$t('ui.package.incompatibleText'),
                        text: this.$t('ui.package.incompatibleTitle'),
                    };
                }

                if (this.package.abandoned) {
                    return {
                        title: this.package.replacement === true && this.$t('ui.package.abandonedText') || this.$t('ui.package.replacement', { replacement: this.package.replacement }),
                        text: this.$t('ui.package.abandonedTitle'),
                    };
                }
            },

            hint() {
                if (this.willBeRemoved) {
                    return this.$t('ui.package.hintRemoved');
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

            additional() {
                const additionals = [];

                if (this.package.license) {
                    if (this.package.license instanceof Array) {
                        additionals.push(this.package.license.join('/'));
                    } else {
                        additionals.push(this.package.license);
                    }
                }

                if (this.package.downloads) {
                    additionals.push(this.$t('ui.package.additionalDownloads', { count: this.package.downloads }, this.package.downloads));
                }

                if (this.package.favers) {
                    additionals.push(this.$t('ui.package.additionalStars', { count: this.package.favers }, this.package.favers));
                }

                return additionals;
            },

            released() {
                if (this.package.time === undefined) {
                    return '';
                }

                return new Date(this.package.time).toLocaleString();
            },

            constraintPlaceholder() {
                if (!Object.keys(this.$store.state.packages.installed).includes(this.package.name)) {
                    return this.$t('ui.package.latestConstraint');
                }

                return '';
            },

            constraintInstalled() {
                if (!this.isInstalled) {
                    return null;
                }

                return this.$store.state.packages.installed[this.package.name].constraint;
            },

            constraintAdded() {
                if (!this.willBeInstalled) {
                    return null;
                }

                return this.$store.state.packages.add[this.package.name].constraint;
            },

            constraintChanged() {
                if (!this.isChanged) {
                    return null;
                }

                return this.$store.state.packages.change[this.package.name];
            },
        },

        methods: {
            restore() {
                this.$store.commit('packages/restore', this.package.name);
                this.resetConstraint();
            },

            install() {
                /* eslint-disable no-underscore-dangle */
                const data = Object.assign({}, this.package);
                delete data._highlightResult;

                this.$store.commit('packages/add', data);
            },

            update() {
                this.$store.commit('packages/update', this.package.name);
            },

            uninstall() {
                if (this.willBeInstalled) {
                    this.$store.commit('packages/restore', this.package.name);
                } else {
                    this.$store.commit('packages/remove', this.package.name);
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

                if (this.isInstalled &&
                    (!this.constraint || this.constraintInstalled === this.constraint)
                ) {
                    this.restore();
                    return;
                }

                if (this.willBeInstalled && !this.constraint) {
                    this.$store.commit(
                        'packages/add',
                        Object.assign({}, this.package, { constraint: null }),
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
                            if (this.isInstalled) {
                                this.$store.commit('packages/change', { name: this.package.name, version: this.constraint });
                            } else {
                                this.$store.commit(
                                    'packages/add',
                                    Object.assign({}, this.package, { constraint: this.constraint }),
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
                this.constraint = value || this.constraintInstalled;
            },
        },

        mounted() {
            this.resetConstraint();
        },
    };
</script>
