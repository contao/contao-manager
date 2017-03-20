<template>
    <div id="app">
        <router-view :class="taskRunning ? 'blur-in' : 'blur-out'"></router-view>
        <keep-alive><task-popup v-if="taskRunning"></task-popup></keep-alive>
        <error v-if="hasError"></error>
    </div>
</template>

<script>
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
        },
        watch: {
            taskRunning(running) {
                document.body.style.overflow = running ? 'hidden' : 'scroll';
            },
        },
        created() {
            document.title = 'Contao Manager @package_version@';
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../assets/styles/bundle.scss";
</style>
