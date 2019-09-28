import Vue from 'vue';

import datimFormat from 'contao-package-list/src/filters/datimFormat';
import numberFormat from 'contao-package-list/src/filters/numberFormat';

Vue.filter('datimFormat', datimFormat);
Vue.filter('numberFormat', numberFormat);
