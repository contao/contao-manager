<template>
    <article class="package">

        <transition name="package__hint">
            <div class="package__hint" v-if="hint">
                <a href="#" class="error" @click.prevent="restore" v-if="isUpdated">{{ 'ui.package.hintNoupdate' | translate }}</a>
                <a href="#" class="error" @click.prevent="restore" v-else>{{ 'ui.package.hintRevert' | translate }}</a>
                <p>
                    {{ hint }}
                    <!--<a href="#">Help</a>-->
                </p>
            </div>
        </transition>

        <div class="package__inside">
            <figure class="package__icon" >
                <slot name="logo">
                    <img :src="package.logo" v-if="package.logo">
                    <img src="../../assets/images/placeholder.png" v-else>
                </slot>
            </figure>

            <div class="package__about">
                <h1 :class="{ package__headline: true, 'package__headline--badge': isIncompatible || package.abandoned }">
                    <span class="package__title" v-html="package._highlightResult && package._highlightResult.title.value || package.title || package.name"></span>
                    <span class="package__name" v-if="package.title && package.title !== package.name">{{ package.name }}</span>
                    <span class="package__badge" v-if="isIncompatible" :title="$t('ui.package.incompatibleText')">{{ 'ui.package.incompatibleTitle' | translate }}</span>
                    <span class="package__badge" v-else-if="package.abandoned" :title="package.replacement === true && $t('ui.package.abandonedText') || $t('ui.package.replacement', { replacement: package.replacement })">{{ 'ui.package.abandonedTitle' | translate }}</span>
                </h1>

                <div class="package__description">
                    <p v-html="package._highlightResult && package._highlightResult.description.value || package.description"></p>
                    <more :name="package.name" :homepage="package.url || package.homepage" :support="Object.assign({}, package.support)"/>
                </div>
                <p class="package__additional">
                    <strong class="package__version package__version--additional" v-if="package.version">{{ 'ui.package.version' | translate({ version: package.version }) }}</strong>
                    <span v-for="item in additional">{{ item }}</span>
                </p>
            </div>

            <div :class="{package__release: true, 'package__release--validating': this.constraintValidating, 'package__release--error': this.constraintError, 'package__release--disabled': (willBeRemoved || (!isInstalled && !willBeInstalled)) }">
                <fieldset>
                    <input ref="constraint" type="text" :placeholder="constraintPlaceholder" v-model="constraint" :disabled="!this.constraintEditable || willBeRemoved || (!isInstalled && !willBeInstalled)" @keypress.enter.prevent="saveConstraint" @keypress.esc.prevent="resetConstraint" @blur="saveConstraint">
                    <button class="widget-button" @click="editConstraint" :disabled="willBeRemoved || (!isInstalled && !willBeInstalled)">{{ 'ui.package.editConstraint' | translate }}</button>
                </fieldset>
                <div class="package__version package__version--release" v-if="package.version">
                    <strong>{{ 'ui.package.version' | translate({ version: package.version }) }}</strong>
                    <time :dateTime="package.time">({{ released }})</time>
                </div>
            </div>

            <fieldset class="package__actions" v-if="updateOnly">
                <button :class="{ 'widget-button': true, 'widget-button--update': !isModified, 'widget-button--check': isModified }" :disabled="isModified" @click="update">{{ 'ui.package.updateButton' | translate }}</button>
            </fieldset>
            <fieldset class="package__actions" v-else>
                <button-group :label="$t('ui.package.updateButton')" icon="update" v-if="isInstalled" :disabled="isModified" @click="update">
                    <!--<button class="widget-button widget-button&#45;&#45;primary widget-button&#45;&#45;power" key="enable" v-if="isModified">Enable</button>-->
                    <!--<button class="widget-button widget-button&#45;&#45;power" key="disable" v-if="!isModified">Disable</button>-->
                    <button class="widget-button widget-button--alert widget-button--trash" @click="uninstall" :disabled="willBeRemoved">{{ 'ui.package.removeButton' | translate }}</button>
                </button-group>
                <button class="widget-button widget-button--primary widget-button--add" v-else @click="install" :disabled="isIncompatible || isInstalled || willBeInstalled">{{ 'ui.package.installButton' | translate }}</button>
            </fieldset>

        </div>
    </article>
</template>

<script>
    import Vue from 'vue';

    import More from './More';
    import ButtonGroup from '../widgets/ButtonGroup';

    export default {
        components: { More, ButtonGroup },

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

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .package {
        margin-bottom: 14px;
        background: #fff;
        border-bottom: 3px solid #ddd3bc;
        border-radius: 2px;

        &__hint {
            position: relative;
            padding: 8px 150px 8px 56px;
            font-weight: $font-weight-medium;
            font-size: 12px;
            line-height: 1.8;
            border-radius: 2px 2px 0 0;
            background: #e8c8bc url('../../assets/images/hint.svg') 20px 5px no-repeat;
            background-size: 28px 28px;

            p a {
                display: inline-block;
                padding-right: 10px;

                &:first-child {
                    margin-left: 10px;
                }

                &:not(:first-child):before {
                    padding-right: 10px;
                    content: "|";
                }
            }

            .error {
                position: absolute;
                right: 20px;
                top: 8px;
                padding-left: 18px;
                color: #bd2e20;
                background: url('../../assets/images/close.svg') left center no-repeat;
                background-size: 14px 14px;
            }
        }

        &__inside {
            &:after {
                display: table;
                clear: both;
                content: "";
            }

            padding: 10px 20px 20px;

            @include screen(1024) {
                padding: 25px 20px;
            }
        }

        &__icon {
            display: none;

            img {
                width: 100%;
                height: 100%;
            }

            @include screen(1024) {
                display: block;
                float: left;
                width: 90px;
                height: 90px;
                margin-right: 20px;
            }
        }

        &__about {
            margin-bottom: 20px;

            @include screen(1024) {
                float: left;
                width: 370px;
                margin-bottom: 0;
            }

            @include screen(1200) {
                width: 590px;
            }
        }

        &__headline {
            position: relative;
            margin-bottom: 5px;

            em {
                background-color: $highlight-color;
                font-style: normal;
            }

            &--badge {
                padding-right: 100px;

                @include screen(1024) {
                    padding-right: 0;
                }
            }
        }

        &__title {
            padding-right: 10px;
        }

        &__name {
            position: relative;
            display: inline-block;
            top: -3px;
            padding: 0 8px;
            background: $border-color;
            border-radius: 2px;
            font-size: 12px;
            line-height: 19px;
            white-space: nowrap;
            color: #fff;

            em {
                background-color: $highlight-color;
                font-style: normal;
            }
        }

        &__badge {
            position: absolute;
            top: 6px;
            right: 0;
            padding: 0 8px;
            background: $red-button;
            border-radius: 2px;
            font-size: 12px;
            line-height: 19px;
            color: #fff;
            cursor: help;

            @include screen(800) {
                position: relative;
                display: inline-block;
                top: -3px;
                right: auto;
            }
        }

        &__description {
            margin-bottom: 1em;

            p {
                display: inline;
            }

            em {
                background-color: $highlight-color;
                font-style: normal;
            }
        }

        &__additional {
            margin-top: -5px;

            *:not(:last-child):after {
                margin: 0 10px;
                font-weight: $font-weight-normal;
                content: "|";
            }
        }

        // Fixes CSS override with basic input styling
        .package__release {
            text-align: right;
            margin-bottom: 10px;

            @include screen(600) {
                float: left;
                width: 50%;
            }

            @include screen(1024) {
                width: 180px;
                margin-left: 40px;
                margin-bottom: 0;
            }

            input[type=text] {
                float: left;
                width: calc(100% - 32px);
                height: 30px;
                margin-right: 2px;
                background: #fff;
                border: 2px solid $orange-button;
                color: #000;
                font-weight: $font-weight-bold;
                text-align: center;
                line-height: 30px;

                //noinspection CssInvalidPseudoSelector
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
            }

            button {
                width: 30px;
                height: 30px;
                padding: 6px;
                line-height: 20px;
                background: $orange-button url('../../assets/images/settings.svg') center no-repeat;
                background-size: 20px 20px;
                text-indent: -999em;
            }

            /*&.validating button {
                animation: release-validating 2s linear infinite;
            }*/

            &--error input {
                animation: input-error .15s linear 3;
            }

            &--disabled {
                input[type=text],
                input[type=text]:disabled {
                    background: $border-color;
                    border-color: $border-color;

                    &::placeholder {
                        text-decoration: line-through;
                    }
                }
            }
        }

        &__version {
            &--additional {
                @include screen(1024) {
                    display: none;
                }
            }

            &--release {
                display: none;

                @include screen(1024) {
                    display: block;
                    margin-top: 20px;
                    text-align: center;
                }

                time {
                    display: block;
                }
            }
        }

        &__actions {
            @include screen(600) {
                float: right;
                width: 50%;
                max-width: 500px;
                margin-top: -5px;
                padding-left: 40px;
                text-align: right;
            }

            @include screen(1024) {
                width: 180px;
                margin-left: 40px;
                padding-left: 0;
            }
        }
    }

    /*@keyframes release-validating {
        100% {
            transform: rotate(360deg);
        }
    }*/

    @include screen(960) {
        .package__hint {
            overflow: hidden;
            height: 37px;
            transition: height .4s ease;
        }
        .package__hint-enter, .package__hint-leave-to {
            height: 0;
        }
    }
</style>
