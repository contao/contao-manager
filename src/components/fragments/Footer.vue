<template>
    <footer>
        <strong>Contao Manager @package_version@</strong>
        <ul class="links">
            <li><a :href="$t('ui.footer.helpHref')" target="_blank">{{ 'ui.footer.help' | translate }}</a></li>
            <li><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ 'ui.footer.reportProblem' | translate }}</a></li>
        </ul>
        <div class="language">
            <button @click="open">{{ languageOptions[currentLanguage] }}</button>
            <ul v-if="visible">
                <li v-for="(label, code) in languageOptions">
                    <a :class="{ active: code === currentLanguage }" @click="updateLanguage(code)" @touchstart="ignore">{{ label }}</a>
                </li>
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
            visible: false,
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
