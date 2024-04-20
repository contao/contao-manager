<template>
    <footer :class="'fragment-footer' + (display ? (' fragment-footer--'+display) : '')">
        <strong class="fragment-footer__product" v-if="!isLogin">Contao Manager @manager_version@</strong>
        <ul class="fragment-footer__links">
            <li><a href="https://docs.contao.org" target="_blank">{{ $t('ui.footer.help') }}</a></li>
            <li><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ $t('ui.footer.reportProblem') }}</a></li>
        </ul>
        <div class="fragment-footer__settings">
            <div class="fragment-footer__language">
                <button :title="$t('ui.app.language')" @click="toggle">{{ languageOptions[currentLanguage] }}</button>
                <ul class="link-more__menu" ref="menu" v-show="visible" tabindex="-1" @blur="close" @click="close">
                    <li v-for="(label, code) in languageOptions" :key="code">
                        <a :class="{ active: code === currentLanguage }" @click="updateLanguage(code)" @touchstart.stop="">{{ label }}</a>
                    </li>
                </ul>
            </div>
            <theme-toggle></theme-toggle>
        </div>
    </footer>
</template>

<script>
    import i18n from '../../i18n';
    import locales from 'contao-package-list/src/i18n/locales';
    import views from '../../router/views';
    import ThemeToggle from 'contao-package-list/src/components/fragments/ThemeToggle.vue';

    export default {
        components: {ThemeToggle},
        props: {
            display: String,
        },

        data: () => ({
            visible: false,
        }),

        computed: {
            isLogin: vm => vm.$store.state.view === views.LOGIN,

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
    @import "../../assets/styles/defaults";

    .fragment-footer {
        width: 280px;
        margin: 10px auto 0;
        padding: 15px 0 25px;
        font-size: 12px;
        text-align: center;
        border-top: 1px solid var(--footer-bdr);

        &--main {
            width: auto;
            margin-top: 52px !important;
            padding: 20px 0;
        }

        &--boxed {
            border-color: var(--footer-fragment-bdr);
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
                color: var(--link-footer);
            }
        }

        &__settings {
            margin-top: 10px;
            display: flex;
            flex-flow: column;
            justify-content: center;
            gap: 10px;
        }

        &__language {
            position: relative;
            display: inline-block;

            button {
                width: auto;
                height: auto;
                padding: 0 0 0 25px;
                background: transparent;
                color: var(--text);
                font-size: 12px;
                font-weight: $font-weight-normal;
                line-height: 20px;
                background: var(--svg--language) left center no-repeat;
                background-size: 20px 20px;
                border: none;
                cursor: pointer;

                &:hover {
                    color: var(--black);
                }
            }

            ul {
                position: absolute;
                display: block;
                width: 350px;
                left: 50%;
                bottom: 25px;
                margin: 0;
                padding: 3px 3px 0;
                text-align: left;
                list-style-type: none;
                white-space: nowrap;
                background: var(--form-bg);
                border: 1px solid var(--tiles-bdr);
                border-bottom: 2px solid var(--contao);
                transform: translateX(-50%);
                z-index: 100;

                &:after {
                    position: absolute;
                    left: 50%;
                    bottom: -6px;
                    width: 0;
                    height: 0;
                    margin-left: -4px;
                    border-style: solid;
                    border-width: 4px 3.5px 0 3.5px;
                    border-color: var(--contao) transparent transparent transparent;
                    content: "";
                }
            }

            li {
                float: left;
                width: 50%;
                margin: 0 0 3px;
                padding: 0;

                a {
                    display: block;
                    margin: 0;
                    padding: 5px;
                    color: var(--text);
                    cursor: pointer;

                    &.active {
                        font-weight: $font-weight-bold;
                    }

                    &.active,
                    &:hover {
                        color: var(--text);
                        background: var(--focus);
                        text-decoration: none;
                    }
                }
            }
        }

        @include screen(960) {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(0, 1fr);
            gap: 16px;
            align-content: center;

            &--boxed,
            &--main {
                .fragment-footer {
                    &__product {
                        margin-right: auto;
                    }

                    &__links {
                        order: 15;
                        margin: 0 0 0 auto;
                    }

                    &__settings {
                        flex-flow: row;
                        margin-top: 0;
                    }
                }
            }

            &--boxed {
                width: 840px;
            }
        }
    }

</style>
