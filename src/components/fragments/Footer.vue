<template>
    <footer v-once>
        Contao Manager @package_version@
        <ul class="links">
            <li><a :href="$t('ui.footer.helpHref')" target="_blank">{{ 'ui.footer.help' | translate }}</a></li>
            <li><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ 'ui.footer.reportProblem' | translate }}</a></li>
        </ul>
    </footer>
</template>
