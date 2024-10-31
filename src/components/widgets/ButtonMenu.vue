<template>
    <div class="button-menu">
        <button
            :class="classes"
            @click="toggle"
        ></button>
        <div ref="menu" class="button-menu__menu" v-show="showMenu" tabindex="-1" @blur="close" @click="close">
            <slot/>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'ButtonMenu',
        props: {
            buttonClass: String,
            type: String,
            icon: {
                type: String,
                default: 'more',
            },
            transparent: Boolean,
            disabled: Boolean,
        },

        data: () => ({
            showMenu: false,
        }),

        computed: {
            classes() {
                let className = `widget-button widget-button--${this.icon} button-menu__button ${this.buttonClass}`;

                if (this.type) {
                    className += ` widget-button--${this.type}`;
                }

                if (this.transparent) {
                    className += ` widget-button--transparent`;
                }

                return className;
            },
        },

        methods: {
            open() {
                this.showMenu = true;
                this.$nextTick(() => this.$refs.menu.focus());
            },

            close() {
                this.$refs.menu.blur();
                setTimeout(() => {
                    this.showMenu = false;
                }, 300);
            },

            toggle() {
                if (this.showMenu) {
                    this.close();
                } else {
                    this.open();
                }
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
.button-menu {
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

    &__menu {
        display: flex;
        flex-direction: column;
        position: absolute;
        top: 38px;
        right: 0;
        width: auto;
        z-index: 100;
        background: var(--form-bg);
        border-radius: var(--border-radius);

        &:before {
            content: "";
            position: absolute;
            top: -5px;
            right: 15px;
            width: 0;
            height: 0;
            border-right: none;
            border-bottom: none;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-bottom: 5px solid var(--form-bg);
        }

        &:focus {
            outline: none;
        }

        button {
            padding: 8px 16px;
            background: none;
            border: none;
            text-align: left;
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
            cursor: pointer;

            &:hover {
                color: var(--text);
                background: var(--focus);
            }

            &:first-child {
                border-top-left-radius: 2px;
                border-top-right-radius: 2px;
            }

            &:last-child {
                border-bottom: none;
                border-bottom-left-radius: 2px;
                border-bottom-right-radius: 2px;
            }
        }

        .link-menu {
            margin-top: 3px;
        }
    }
}
</style>
