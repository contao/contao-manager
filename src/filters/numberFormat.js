import Vue from 'vue';

Vue.filter('numberFormat', (value) => {
    if (!value) {
        return '';
    }

    if (!Intl || !Intl.NumberFormat) {
        return value;
    }

    return new Intl.NumberFormat().format(value);
});
