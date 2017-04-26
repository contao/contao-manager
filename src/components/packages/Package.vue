<template>
    <section class="package">

        <transition name="hint">
            <div class="hint warning" v-if="hint">
                <a href="#" class="close" @click.prevent="reset">Revert Changes</a>
                <p>
                    {{ hint }}
                    <a href="#" v-if="false">Help</a>
                </p>
            </div>
        </transition>

        <div class="inside" v-if="isContao">
            <figure><img src="../../assets/images/logo.svg" /></figure>

            <div class="about">
                <h1>Contao Open Source CMS</h1>
                <p class="description">Contao is an Open Source PHP Content Management System. <a href="https://www.contao.org/" target="_blank" class="more">Project Website</a></p>
                <p class="additional" v-if="additional">
                    <span v-for="item in additional">{{ item }}</span>
                </p>
            </div>

            <div v-if="changed.get('constraint') !== undefined && changed.get('constraint') !== null" :class="{release: true, validating: this.constraintValidating, error: this.constraintError}">
                <fieldset>
                    <input ref="constraint" type="text" :placeholder="!original.get('constraint') ? 'latest version' : ''" v-model="constraint" :disabled="!constraintEditable" @keypress.enter.prevent="saveConstraint" @keypress.esc.prevent="resetConstraint" @blur="saveConstraint">
                    <button @click="editConstraint">Edit</button>
                </fieldset>
                <div class="version" v-if="package.version">
                    <strong>Version {{ package.version }}</strong>
                    <time :dateTime="package.time">({{ released }})</time>
                </div>
            </div>

            <fieldset class="actions">
                <button class="uninstall" disabled>Remove</button>
            </fieldset>
        </div>

        <div class="inside" v-else>
            <figure><img src="../../assets/images/placeholder.png" /></figure>

            <div class="about">
                <h1 class="badge" v-if="incompatible">{{ name }}<span title="This bundle could not be enabled because it does not provide a Contao Manager Plugin.">incompatible</span></h1>
                <h1 class="badge" v-else-if="package.abandoned">{{ name }}<span title="This package is marked as abandoned.">abandoned</span></h1>
                <h1 v-else>{{ name }}</h1>

                <p class="description">{{ package.description }} <a :href="package.url" target="_blank" class="more" v-if="package.url">More</a></p>
                <p class="additional" v-if="additional">
                    <span v-for="item in additional">{{ item }}</span>
                </p>
            </div>

            <div v-if="changed.get('constraint') !== undefined && changed.get('constraint') !== null" :class="{release: true, validating: this.constraintValidating, error: this.constraintError}">
                <fieldset>
                    <input ref="constraint" type="text" :placeholder="!original.get('constraint') ? 'latest version' : ''" v-model="constraint" :disabled="!constraintEditable" @keypress.enter.prevent="saveConstraint" @keypress.esc.prevent="resetConstraint" @blur="saveConstraint">
                    <button @click="editConstraint">Edit</button>
                </fieldset>
                <div class="version" v-if="package.version">
                    <strong>Version {{ package.version }}</strong>
                    <time :dateTime="package.time">({{ released }})</time>
                </div>
            </div>

            <fieldset class="actions">
                <button key="enable" class="enable" v-if="changed.get('enabled') === false">Enable</button>
                <button key="disable" class="disable" v-if="changed.get('enabled') === true">Disable</button>

                <button key="remove" class="uninstall" v-if="original.get('constraint')" @click="uninstall" :disabled="changed.get('constraint') === null">Remove</button>
                <button key="install" class="install" v-else @click="install" :disabled="changed.get('constraint')">Check &amp; Install</button>
            </fieldset>

        </div>
    </section>
</template>

<script>
    import api from '../../api';

    export default {
        props: ['name', 'package', 'original', 'changed'],

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
                    return 'This package will be installed in the best available version when you apply the changes.';
                }

                if (originalConstraint === undefined) {
                    return `This package will be installed with constraint "${changedConstraint}" when you apply the changes.`;
                }

                if (changedConstraint === null) {
                    return 'This package will be removed when you apply the changes.';
                }

                if (originalConstraint !== changedConstraint) {
                    return `The constraint for this package will be changed from "${this.original.get('constraint')}" to "${this.changed.get('constraint')}" when you apply the changes.`;
                }

                return null;
            },

            additional() {
                const additionals = [];

                if (this.package.license) {
                    additionals.push(this.package.license.join('/'));
                }

                if (this.package.downloads) {
                    additionals.push(`${this.package.downloads} Downloads`);
                }

                if (this.package.favers) {
                    additionals.push(`${this.package.favers} Stars`);
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
