<template>
    <footer>
        <strong>Contao Manager @package_version@</strong>
        <ul class="links">
            <li><a :href="$t('ui.footer.helpHref')" target="_blank">{{ 'ui.footer.help' | translate }}</a></li>
            <li><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ 'ui.footer.reportProblem' | translate }}</a></li>
        </ul>
        <div class="language">
            <button>{{ languageOptions[currentLanguage] }}</button>
            <ul>
                <li v-for="(label, code) in languageOptions"><a @click="updateLanguage(code)">{{ label }}</a></li>
            </ul>
        </div>
    </footer>
</template>

<script>
    import i18n from '../../i18n';
    import locales from '../../i18n/locales';

    export default {
        data: () => ({
            language: 'en',
        }),

        computed: {
            currentLanguage() {
                return this.$i18n.locale();
            },

            languageOptions() {
                return locales;
            },
        },

        methods: {
            updateLanguage(value) {
                i18n.load(value);
            },
        },
    };
</script>
