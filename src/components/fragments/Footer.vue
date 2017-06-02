<template>
    <footer v-once>
        <strong>Contao Manager @package_version@</strong>
        <ul class="links">
            <li><a :href="$t('ui.footer.helpHref')" target="_blank">{{ 'ui.footer.help' | translate }}</a></li>
            <li><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ 'ui.footer.reportProblem' | translate }}</a></li>
        </ul>
        <select-menu name="language" label="Language" :value="currentLanguage" :options="languageOptions" @input="updateLanguage"></select-menu>
    </footer>
</template>

<script>
    import i18n from '../../i18n';
    import locales from '../../i18n/locales';

    import SelectMenu from '../widgets/SelectMenu';

    export default {
        components: { SelectMenu },

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
