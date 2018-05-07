<template>
    <div class="button-group">
        <button :class="primaryClass" @click="e => $emit('click', e)" :disabled="disabled">{{ label }}</button>
        <button :class="moreClass" @click="toggle"><svg fill="#FFF" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"/><path d="M0 0h24v24H0z" fill="none"/></svg></button>
        <div ref="group" class="button-group__group" v-show="showGroup" tabindex="-1" @blur="close" @click="close">
            <slot/>
        </div>
    </div>
</template>

<script>
    export default {

        props: {
            label: {
                type: String,
                required: true,
            },
            type: String,
            icon: String,
            disabled: Boolean,
        },

        data: () => ({
            showGroup: false,
        }),

        computed: {
            primaryClass() {
                let className = 'widget-button button-group__primary';

                if (this.type) {
                    className += ` widget-button--${this.type}`;
                }

                if (this.icon) {
                    className += ` widget-button--${this.icon}`;
                }

                return className;
            },

            moreClass() {
                let className = 'widget-button button-group__more';

                if (this.type) {
                    className += ` widget-button--${this.type}`;
                }

                return className;
            },
        },

        methods: {
            open() {
                this.showGroup = true;
                this.$nextTick(() => this.$refs.group.focus());
            },

            close() {
                this.showGroup = false;
            },

            toggle() {
                if (this.showGroup) {
                    this.close();
                } else {
                    this.open();
                }
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .button-group {
        position: relative;

        &__primary.widget-button {
            float: left;
            width: calc(100% - 39px);
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        &__more.widget-button {
            float: right;
            width: 38px;
            padding: 7px;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;

            svg {
                width: 24px;
                height: 24px;
            }
        }

        &__group {
            position: absolute;
            top: 39px;
            width: 100%;
            z-index: 100;

            &:focus {
                outline: none;
            }

            .widget-button {
                margin-top: 1px;
                /*border-radius: 0;*/
            }

            .link-menu {
                margin-top: 3px;
            }
        }
    }
</style>
