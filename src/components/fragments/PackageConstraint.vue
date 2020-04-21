<template>
    <fieldset class="package-constraint" v-if="!isFeature && isVisible">
        <input
            ref="constraint"
            type="text"
            :placeholder="constraintPlaceholder"
            :title="inputTitle || constraint"
            v-model="constraint"
            :class="{ disabled: disabled || willBeRemoved || (!isInstalled && !willBeInstalled && !isRequired), error: constraintError }"
            :disabled="disabled || !constraintEditable || willBeRemoved || (!isInstalled && !willBeInstalled && !isRequired)"
            @keypress.enter.prevent="saveConstraint"
            @keypress.esc.prevent="resetConstraint"
            @blur="saveConstraint"
        >
        <button
            :class="{ 'widget-button widget-button--gear': true, rotate: constraintValidating }"
            :title="buttonTitle"
            @click="editConstraint"
            :disabled="disabled || willBeRemoved || (!isInstalled && !willBeInstalled && !isRequired)"
        >{{ buttonValue || $t('ui.package.editConstraint') }}</button>
    </fieldset>
</template>

<script>
    import Vue from 'vue';

    import packageStatus from '../../mixins/packageStatus';

    export default {
        mixins: [packageStatus],

        props: {
            data: {
                type: Object,
                required: true,
            },
            disabled: Boolean,
            inputTitle: String,
            inputValue: String,
            buttonTitle: String,
            buttonValue: String,
        },

        data: () => ({
            constraint: '',
            constraintEditable: false,
            constraintValidating: false,
            constraintError: false,
        }),

        computed: {
            constraintPlaceholder() {
                if (this.inputValue) {
                    return '';
                }

                if (!Object.keys(this.$store.state.packages.root.require).includes(this.data.name)) {
                    return this.$t('ui.package.latestConstraint');
                }

                return '';
            },
        },

        methods: {
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
                    this.$store.commit('packages/restore', this.data.name);
                    this.$store.commit('packages/uploads/unconfirm', this.data.name);
                    this.resetConstraint();
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
                            if (this.isRootInstalled || this.isRequired) {
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
                if (this.inputValue) {
                    this.constraint = this.inputValue;
                    return;
                }

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
            inputValue() {
                this.resetConstraint();
            },

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

<style rel="stylesheet/scss" lang="scss" scoped>
    @import "~contao-package-list/src/assets/styles/defaults";

    .package-constraint {

        input[type=text] {
            height: 30px;
            margin-right: 2px;
            background: #fff;
            border: 2px solid $orange-button;
            color: #000;
            font-weight: $font-weight-bold;
            text-align: center;
            line-height: 30px;

            &::placeholder {
                color: #fff;
                opacity: 1;
            }

            &:disabled {
                color: #fff;
                opacity: 1;
                background: $orange-button;
                -webkit-text-fill-color: #fff;
            }

            &.disabled {
                background: $border-color;
                border-color: $border-color;
            }

            &.error {
                animation: input-error .15s linear 3;
            }
        }

        & > input[type=text],
        & > input[type=text]:disabled {
            float: left;
            width: calc(100% - 32px);
        }

        button {
            position: relative;
            width: 30px;
            height: 30px;
            background: $orange-button;
            line-height: 20px;
            text-indent: -999em;

            &:hover {
                background: darken($orange-button, 5);
                border-color: darken($orange-button, 10);
            }

            &:before {
                position: absolute;
                left: 50%;
                top: 50%;
                margin: -10px 0 0 -10px;
            }

            &.rotate:before {
                animation: release-validating 2s linear infinite;
            }
        }

        @keyframes release-validating {
            100% {
                transform: rotate(360deg);
            }
        }
    }
</style>
