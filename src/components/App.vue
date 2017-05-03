<template>
    <div id="app">
        <div class="https-warning" v-if="insecure">
            <strong>{{ 'ui.app.httpsHeadline' | translate }}</strong>
            <span>{{ 'ui.app.httpsDescription' | translate }}</span>
            <a :href="$t('ui.app.moreHref')" target="_blank">{{ 'ui.app.moreLink' | translate }}</a>
        </div>
        <router-view :class="taskRunning ? 'blur-in' : 'blur-out'"></router-view>
        <keep-alive><task-popup v-if="taskRunning"></task-popup></keep-alive>
        <error v-if="hasError"></error>
    </div>
</template>

<script>
    import apiStatus from '../api/status';
    import scopes from '../router/scopes';
    import routes from '../router/routes';

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

            insecure() {
                return window.location.protocol !== 'https:' && window.location.hostname !== 'localhost';
            },
        },

        watch: {
            taskRunning(running) {
                document.body.style.overflow = running ? 'hidden' : 'scroll';
            },

            status(status) {
                delete this.$router.scope;

                if (status === apiStatus.INSTALL) {
                    this.$router.scope = scopes.INSTALL;
                    this.$router.replace(routes.install);
                } else if (status === apiStatus.AUTHENTICATE) {
                    this.$router.scope = scopes.LOGIN;
                    this.$router.replace(routes.login);
                } else if (status === apiStatus.OK) {
                    this.$router.scope = scopes.MANAGER;
                    this.$router.replace(routes.packages);
                } else {
                    this.$store.commit('setError', {
                        title: this.$t('ui.app.apiError'),
                        type: 'about:blank',
                    });
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
