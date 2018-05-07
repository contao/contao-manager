<template>
    <div class="link-more">
        <button @click="open">{{ 'ui.package.more' | translate }}</button>
        <link-menu :items="linkItems" color="contao" v-show="visible"/>
    </div>
</template>

<script>
    import LinkMenu from '../fragments/LinkMenu';

    export default {
        components: { LinkMenu },
        props: {
            name: String,
            homepage: String,
            support: Object,
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

                if (this.name) {
                    items.push({ label: this.$t('ui.package.packagist'), href: `https://packagist.org/packages/${this.name}`, target: '_blank' });
                }

                if (this.support) {
                    Object.keys(this.support).forEach((key) => {
                        items.push({ label: this.$t(`ui.package.support_${key}`), href: this.support[key], target: '_blank' });
                    });
                }

                return items;
            },
        },

        methods: {
            open(e) {
                e.stopPropagation();
                this.visible = !this.visible;
            },

            close() {
                this.visible = false;
            },

            ignore(e) {
                e.stopPropagation();
            },
        },

        mounted() {
            window.addEventListener('click', this.close);
            window.addEventListener('touchstart', this.close);
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .link-more {
        position: relative;
        display: inline-block;
        margin-left: 5px;

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

        ul {
            transform: translateX(-50%);
        }
    }
</style>
