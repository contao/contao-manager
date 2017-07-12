<template>
    <section class="package">

        <transition name="hint">
            <div class="hint warning" v-if="hint">
                <a href="#" class="close" @click.prevent="reset">{{ 'ui.package.hintRevert' | translate }}</a>
                <p>
                    {{ hint }}
                    <!--<a href="#" v-if="false">Help</a>-->
                </p>
            </div>
        </transition>

        <div class="inside" v-if="isContao">
            <figure><img src="../../assets/images/logo.svg" /></figure>

            <div class="about">
                <h1>Contao Open Source CMS</h1>
                <p class="description">Contao is an Open Source PHP Content Management System. <a href="https://www.contao.org/" target="_blank" class="more">Project Website</a></p>
                <p class="additional">
                    <strong class="version">{{ 'ui.package.version' | translate({ version: package.version }) }}</strong>
                    <span v-for="item in additional">{{ item }}</span>
                </p>
            </div>

            <div :class="{release: true, validating: this.constraintValidating, error: this.constraintError, disabled: this.disableUpdate}">
                <fieldset>
                    <input ref="constraint" type="text" :placeholder="!original.get('constraint') ? $t('ui.package.latestConstraint') : ''" v-model="constraint" :disabled="!constraintEditable" @keypress.enter.prevent="saveConstraint" @keypress.esc.prevent="resetConstraint" @blur="saveConstraint">
                    <button @click="editConstraint" :disabled="disableUpdate">{{ 'ui.package.editConstraint' | translate }}</button>
                </fieldset>
                <div class="version" v-if="package.version">
                    <strong>{{ 'ui.package.version' | translate({ version: package.version }) }}</strong>
                    <time :dateTime="package.time">({{ released }})</time>
                </div>
            </div>

            <fieldset class="actions">
                <button class="uninstall" disabled>{{ 'ui.package.removeButton' | translate }}</button>
            </fieldset>
        </div>

        <div class="inside" v-else>
            <figure><img src="../../assets/images/placeholder.png" /></figure>

            <div class="about">
                <h1 class="badge" v-if="incompatible">{{ name }}<span :title="$t('ui.package.incompatibleText')">{{ 'ui.package.incompatibleTitle' | translate }}</span></h1>
                <h1 class="badge" v-else-if="package.abandoned">{{ name }}<span :title="$t('ui.package.abandonedText')">{{ 'ui.package.abandonedTitle' | translate }}</span></h1>
                <h1 v-else>{{ name }}</h1>

                <p class="description">{{ package.description }} <a :href="package.url" target="_blank" class="more" v-if="package.url">{{ 'ui.package.homepage' | translate }}</a></p>
                <p class="additional">
                    <strong class="version">{{ 'ui.package.version' | translate({ version: package.version }) }}</strong>
                    <span v-for="item in additional">{{ item }}</span>
                </p>
            </div>

            <div :class="{release: true, validating: this.constraintValidating, error: this.constraintError, disabled: this.disableUpdate}">
                <fieldset>
                    <input ref="constraint" type="text" :placeholder="constraintPlaceholder" v-model="constraint" :disabled="!this.constraintEditable" @keypress.enter.prevent="saveConstraint" @keypress.esc.prevent="resetConstraint" @blur="saveConstraint">
                    <button @click="editConstraint" :disabled="disableUpdate">{{ 'ui.package.editConstraint' | translate }}</button>
                </fieldset>
                <div class="version" v-if="package.version">
                    <strong>{{ 'ui.package.version' | translate({ version: package.version }) }}</strong>
                    <time :dateTime="package.time">({{ released }})</time>
                </div>
            </div>

            <fieldset class="actions">
                <!--<button key="enable" class="enable" v-if="changed.get('enabled') === false">Enable</button>-->
                <!--<button key="disable" class="disable" v-if="changed.get('enabled') === true">Disable</button>-->

                <button key="remove" class="uninstall" v-if="original.get('constraint')" @click="uninstall" :disabled="disableRemove || changed.get('constraint') === null">{{ 'ui.package.removeButton' | translate }}</button>
                <button key="install" class="install" v-else @click="install" :disabled="changed.get('constraint')">{{ 'ui.package.installButton' | translate }}</button>
            </fieldset>

        </div>
    </section>
</template>

<script>
    import api from '../../api';

    export default {
        props: ['name', 'package', 'original', 'changed', 'disableUpdate', 'disableRemove'],

        data: () => ({
            constraint: '',
            constraintEditable: false,
            constraintValidating: false,
            constraintError: false,
        }),

        computed: {
            hint() {
                if (this.original === this.changed) {
                    return null;
                }

                const originalConstraint = this.original.get('constraint');
                const changedConstraint = this.changed.get('constraint');

                if (originalConstraint === undefined && changedConstraint === '') {
                    return this.$t('ui.package.hintConstraintBest');
                }

                if (originalConstraint === undefined) {
                    return this.$t('ui.package.hintConstraint', { constraint: changedConstraint });
                }

                if (changedConstraint === null) {
                    return this.$t('ui.package.hintRemoved');
                }

                if (originalConstraint !== changedConstraint) {
                    return this.$t(
                        'ui.package.hintConstraintChange',
                        {
                            from: this.original.get('constraint'),
                            to: this.changed.get('constraint'),
                        },
                    );
                }

                return null;
            },

            additional() {
                const additionals = [];

                if (this.package.license) {
                    additionals.push(this.package.license.join('/'));
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

            incompatible() {
                if (this.package.type === 'contao-bundle') {
                    return !this.package.extra || !this.package.extra['contao-manager-plugin'];
                }

                return false;
            },

            isContao() {
                return this.name === 'contao/manager-bundle';
            },

            constraintPlaceholder() {
                if (!this.original.get('constraint')) {
                    return this.$t('ui.package.latestConstraint');
                }

                if (this.disableUpdate) {
                    return this.original.get('constraint');
                }

                return '';
            },
        },

        methods: {
            reset() {
                this.$emit('change', this.name, this.original);
            },

            install() {
                this.$emit('change', this.name, this.original.set('constraint', ''));
            },

            uninstall() {
                this.$emit('change', this.name, this.original.set('constraint', null));
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

                if (this.constraint === this.original.get('constraint')
                    || (this.original.get('constraint') === undefined && this.constraint === '')) {
                    this.$emit('change', this.name, this.original.set('constraint', this.constraint));
                    return;
                }

                this.$refs.constraint.blur();
                this.constraintValidating = true;

                api.validateConstraint(this.constraint).then(
                    (valid) => {
                        this.constraintValidating = false;
                        if (valid) {
                            this.$emit('change', this.name, this.original.set('constraint', this.constraint));
                        } else {
                            this.constraintError = true;
                            this.$nextTick(() => this.editConstraint());
                        }
                    },
                );
            },

            resetConstraint() {
                if (!this.constraintEditable) {
                    return;
                }

                this.constraintEditable = false;
                this.constraintError = false;
                this.constraintValidating = false;
                this.constraint = this.changed.get('constraint');
            },
        },

        watch: {
            changed(value) {
                this.constraint = value.get('constraint');
            },
        },

        mounted() {
            this.constraint = this.original.get('constraint');
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
    @import "../../assets/styles/defaults";

    @include screen(960) {
        .hint {
            overflow: hidden;
            height: 37px;
            transition: height .4s ease;
        }
        .hint-enter, .hint-leave-to {
            height: 0;
        }
    }
</style>
