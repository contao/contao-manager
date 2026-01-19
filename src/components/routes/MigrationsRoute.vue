<template>
    <migration-view @close="close" />
</template>

<script>
import routes from '../../router/routes';
import MigrationView from '../views/MigrationView.vue';

export default {
    components: { MigrationView },

    methods: {
        close() {
            this.$router.push(routes.discover);
        },
    },
};
</script>
