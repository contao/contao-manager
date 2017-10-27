import Vue from 'vue';

export default {
    read(filename) {
        return Vue.http.get(`api/files/${filename}`);
    },

    write(filename, content) {
        return Vue.http.put(`api/files/${filename}`, content);
    },
};
