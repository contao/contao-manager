<template>
    <div class="button-group">
        <loading-button :href="href" :to="to" :class="primaryClass" @click="e => $emit('click', e)" :disabled="disabled" :loading="loading" :submit="submit">{{ label }}</loading-button>
        <button type="button" :class="moreClass" @click="toggle" :disabled="moreDisabled"><svg fill="#FFF" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"/><path d="M0 0h24v24H0z" fill="none"/></svg></button>
        <div ref="group" :class="{ 'button-group__group': true, [`button-group__group--top`]: alignTop }" v-show="showGroup" tabindex="-1" @focusout="close" @click="close">
            <slot/>
        </div>
    </div>
</template>

<script>
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { LoadingButton },

        emits: ['click'],

        props: {
            label: {
                type: String,
                required: true,
            },
            type: String,
            href: String,
            to: [String, Object],
            icon: String,
            color: String,
            disabled: Boolean,
            loading: Boolean,
            submit: Boolean,
            moreDisabled: Boolean,
            alignTop: Boolean,
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

                if (this.color) {
                    className += ` widget-button--${this.color}`;
                }

                return className;
            },

            moreClass() {
                let className = 'widget-button button-group__more';

                if (this.type) {
                    className += ` widget-button--${this.type}`;
                }

                if (this.color) {
                    className += ` widget-button--${this.color}`;
                }

                return className;
            },
        },

        methods: {
            open() {
                this.showGroup = true;
                setTimeout(() => this.$refs.group?.focus(), 0);
            },

            close(event) {
                if (event && this.$refs.group?.contains(event.relatedTarget)) {
                    return;
                }

                this.$refs.group?.blur();
                setTimeout(() => {
                    this.showGroup = false;
                }, 100);
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
.button-group {
    display: flex;
    gap: 1px;
    position: relative;

    &__primary.widget-button {
        flex-grow: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    &__more.widget-button {
        flex-shrink: 0;
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
        padding-top: 3px;
        z-index: 100;

        &:focus {
            outline: none;
        }

        .widget-button {
            margin-top: 1px;
        }

        .link-menu {
            margin-top: 3px;
        }

        &--top {
            top: auto;
            bottom: 39px;

            .link-menu {
                margin-top: 0;
                margin-bottom: 3px;
            }
        }
    }
}
</style>
