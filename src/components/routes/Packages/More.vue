<template>
    <div class="link-more" v-if="linkItems.length">
        <button @click="toggle">{{ 'ui.package.more' | translate }}</button>
        <div class="link-more__menu" ref="menu" v-show="visible" tabindex="-1" @blur="close" @click="close">
            <link-menu :items="linkItems" color="contao"/>
        </div>
    </div>
</template>

<script>
    import LinkMenu from '../../fragments/LinkMenu';

    export default {
        components: { LinkMenu },
        props: {
            name: String,
            homepage: String,
            support: Object,
            hidePackagist: Boolean,
        },

        data: () => ({
            visible: false,
        }),

        computed: {
            linkItems() {
                const items = [];

                if (this.homepage) {
                    items.push({ label: this.$t('ui.package.homepage'), href: this.homepage, target: '_blank' });
                }

                if (this.name && !this.hidePackagist) {
                    items.push({ label: this.$t('ui.package.packagist'), href: `https://packagist.org/packages/${this.name}`, target: '_blank' });
                }

                if (this.support) {
                    Object.keys(this.support).forEach((key) => {
                        if (key === 'email') {
                            items.push({ label: this.$t(`ui.package.support_${key}`), href: `mailto:${this.support[key]}` });
                        } else {
                            items.push({ label: this.$t(`ui.package.support_${key}`), href: this.support[key], target: '_blank' });
                        }
                    });
                }

                return items;
            },
        },

        methods: {
            open() {
                this.visible = true;
                this.$nextTick(() => this.$refs.menu.focus());
            },

            close() {
                this.$refs.menu.blur();
                setTimeout(() => {
                    this.visible = false;
                }, 300);
            },

            toggle() {
                if (this.visible) {
                    this.close();
                } else {
                    this.open();
                }
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../../assets/styles/defaults";

    .link-more {
        position: relative;
        display: inline-block;
        margin-left: 5px;

        p:empty + & {
            margin-left: 0;
        }

        button {
            width: auto;
            height: auto;
            padding: 0 0 5px;
            background: transparent;
            color: $link-color;
            font-weight: $font-weight-normal;
            line-height: inherit;
            border: none;
            cursor: pointer;

            &:hover {
                text-decoration: underline;
            }
        }

        &__menu {
            outline: none;
        }

        ul {
            transform: translateX(-50%);
        }
    }
</style>
