<template>
    <footer :class="'fragment-footer' + (display ? ' fragment-footer--' + display : '')">
        <strong class="fragment-footer__product" v-if="!isLogin">Contao Manager @manager_version@</strong>
        <ul class="fragment-footer__links">
            <li>
                <a href="https://docs.contao.org" target="_blank">{{ $t('ui.footer.help') }}</a>
            </li>
            <li>
                <a href="https://to.contao.org/support" target="_blank">{{ $t('ui.footer.reportProblem') }}</a>
            </li>
        </ul>
        <div class="fragment-footer__settings">
            <footer-languages :locales="languageOptions" :current="currentLanguage" @change="updateLanguage" />
            <theme-toggle />
        </div>
    </footer>
</template>

<script>
import i18n from '../../i18n';
import locales from 'contao-package-list/src/i18n/locales';
import views from '../../router/views';
import FooterLanguages from 'contao-package-list/src/components/fragments/FooterLanguages.vue';
import ThemeToggle from 'contao-package-list/src/components/fragments/ThemeToggle.vue';

export default {
    components: { FooterLanguages, ThemeToggle },

    props: {
        display: String,
    },

    computed: {
        isLogin: (vm) => vm.$store.state.view === views.LOGIN,

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
    },
};
</script>

<style rel="stylesheet/scss" lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

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
        font-weight: defaults.$font-weight-normal;
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

    @include defaults.screen(960) {
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
