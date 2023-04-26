import Vue from 'vue';

import datimFormat from 'contao-package-list/src/filters/datimFormat';
import numberFormat from 'contao-package-list/src/filters/numberFormat';
import filesize from './filesize';

Vue.filter('datimFormat', datimFormat);
Vue.filter('numberFormat', numberFormat);
Vue.filter('filesize', filesize);
