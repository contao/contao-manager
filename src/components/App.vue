<template>
    <div id="app">
        <router-view :class="taskRunning ? 'blur-in' : 'blur-out'"></router-view>
        <keep-alive><task-popup v-if="taskRunning"></task-popup></keep-alive>
        <error v-if="hasError"></error>
    </div>
</template>

<script>
    import apiStatus from '../api/status';

    import TaskPopup from './fragments/TaskPopup';
    import Error from './Error';

    export default {
        components: { TaskPopup, Error },

        computed: {
            taskRunning() {
                return this.$store.state.tasks.currentId !== null;
            },

            hasError() {
                return this.$store.state.error !== null;
            },

            status() {
                return this.$store.state.status;
            },
        },

        watch: {
            taskRunning(running) {
                document.body.style.overflow = running ? 'hidden' : 'scroll';
            },

            status(status) {
                let route = null;
                delete this.$router.allowed;

                if ((status === apiStatus.NEW || status === apiStatus.EMPTY)) {
                    route = 'install';
                } else if (status === apiStatus.CONFLICT) {
                    route = 'selftest';
                } else if (status === apiStatus.AUTHENTICATE) {
                    route = 'login';
                }

                if (route !== null) {
                    this.$router.allowed = route;
                    this.$router.replace({ name: route });
                }
            },
        },

        created() {
            document.title = 'Contao Manager @package_version@';

            this.$store.dispatch('fetchStatus');
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../assets/styles/bundle.scss";
</style>
