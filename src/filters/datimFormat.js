import Vue from 'vue';

Vue.filter('datimFormat', (value) => {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString();
});
