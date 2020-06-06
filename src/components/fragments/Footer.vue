<template>
    <footer :class="'fragment-footer' + (display ? (' fragment-footer--'+display) : '')">
        <strong class="fragment-footer__product">Contao Manager @package_version@</strong>
        <ul class="fragment-footer__links">
            <li><a :href="$t('ui.footer.helpHref')" target="_blank">{{ $t('ui.footer.help') }}</a></li>
            <li><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ $t('ui.footer.reportProblem') }}</a></li>
        </ul>
        <div class="fragment-footer__language">
            <button @click="toggle">{{ languageOptions[currentLanguage] }}</button>
            <ul class="link-more__menu" ref="menu" v-show="visible" tabindex="-1" @blur="close" @click="close">
                <li v-for="(label, code) in languageOptions" :key="code">
                    <a :class="{ active: code === currentLanguage }" @click="updateLanguage(code)" @touchstart.stop="">{{ label }}</a>
                </li>
            </ul>
        </div>
    </footer>
</template>

<script>
    import i18n from '../../i18n';
    import locales from '../../i18n/locales';

    export default {
        props: {
            display: String,
        },

        data: () => ({
            visible: false,
        }),

        computed: {
            currentLanguage() {
                return this.$i18n.locale;
            },

            languageOptions() {
                return locales;
            },
        },

        methods: {
            updateLanguage(value) {
                i18n.switch(value);
            },

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
    @import "~contao-package-list/src/assets/styles/defaults";

    .fragment-footer {
        clear: both;
        width: 250px;
        margin: 10px auto 0;
        padding: 15px 0;
        font-size: 12px;
        text-align: center;
        border-top: 1px solid #eee;

        &--main {
            width: auto;
            margin-top: 52px !important;
            padding: 20px 0;
            border-top: 1px solid #bbbbbb;
        }

        &:before {
            content: "";
            display: table;
            clear: both
        }

        &__product {
            font-weight: $font-weight-normal;
        }

        &__links {
            margin: 5px 0 0;
            padding: 0;
            list-style-type: none;

            li {
                display: inline-block;

                &:not(:first-child):before {
                    content: "|";
                    padding: 0 10px 0 8px;
                }
            }

            a {
                display: inline !important;
                color: $footer-link;
            }
        }

        &__language {
            position: relative;
            display: inline-block;
            margin-left: 5px;

            button {
                width: auto;
                height: auto;
                padding: 0 0 0 25px;
                margin-top: 10px !important;
                background: transparent;
                color: $text-color;
                font-size: 12px;
                font-weight: $font-weight-normal;
                line-height: 20px;
                background: url(../../assets/images/language.svg) left center no-repeat;
                background-size: 20px 20px;
                border: none;

                &:hover {
                    color: #000;
                }
            }

            ul {
                position: absolute;
                display: block;
                width: 350px;
                left: 50%;
                bottom: 30px;
                margin: 0;
                padding: 0;
                text-align: left;
                list-style-type: none;
                white-space: nowrap;
                background: #fff;
                border-bottom: 3px solid $contao-color;
                transform: translateX(-50%);
                z-index: 100;
                box-shadow: $shadow-color 0 -1px 2px;

                &:after {
                    position: absolute;
                    left: 50%;
                    bottom: -7px;
                    width: 0;
                    height: 0;
                    margin-left: -4px;
                    border-style: solid;
                    border-width: 4px 3.5px 0 3.5px;
                    border-color: $contao-color transparent transparent transparent;
                    content: "";
                }
            }

            li {
                float: left;
                width: 50%;
                margin: 0;
                padding: 0;
                border-top: 1px solid #e5dfd0;

                a {
                    display: block;
                    margin: 0;
                    padding: 5px 10px;
                    color: $text-color;
                    cursor: pointer;

                    &.active {
                        font-weight: $font-weight-bold;
                    }

                    &:hover {
                        color: #000;
                        text-decoration: none;
                    }
                }

                &:first-child,
                &:nth-child(2) {
                    border-top: none;
                }
            }
        }

        @include screen(960) {
            &--boxed,
            &--main {
                .fragment-footer {

                    &__product {
                        float: left;
                    }

                    &__links {
                        float: right;
                        margin: 0;
                    }

                    &__language button {
                        margin-top: 0 !important;
                    }
                }
            }

            &--boxed {
                width: 840px;
            }
        }
    }

</style>
