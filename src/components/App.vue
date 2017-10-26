<template>
    <div id="app">
        <div class="https-warning" v-if="isInsecure">
            <strong>{{ 'ui.app.httpsHeadline' | translate }}</strong>
            <span>{{ 'ui.app.httpsDescription' | translate }}</span>
            <a :href="$t('ui.app.moreHref')" target="_blank">{{ 'ui.app.moreLink' | translate }}</a>
        </div>

        <error v-if="hasError"></error>

        <loader v-if="viewInit" class="init">
            <p>Initializing …</p>
        </loader>

        <create-account v-else-if="viewAccount"></create-account>
        <login v-else-if="viewLogin"></login>

        <boot v-else-if="viewBoot" :class="taskRunning ? 'blur-in' : 'blur-out'"></boot>
        <router-view v-else :class="taskRunning ? 'blur-in' : 'blur-out'"></router-view>

        <keep-alive><task-popup v-if="taskRunning"></task-popup></keep-alive>
    </div>
</template>

<script>
    import views from '../router/views';

    import TaskPopup from './fragments/TaskPopup';
    import Loader from './fragments/Loader';
    import Error from './Error';
    import CreateAccount from './CreateAccount';
    import Login from './Login';
    import Boot from './Boot';

    export default {
        components: { Loader, TaskPopup, Error, CreateAccount, Login, Boot },

        computed: {
            taskRunning() {
                return this.$store.state.tasks.status !== null;
            },

            hasError() {
                return this.$store.state.error !== null;
            },

            isInsecure() {
                return window.location.protocol !== 'https:' && window.location.hostname !== 'localhost';
            },

            viewInit() {
                return this.$store.state.view === views.INIT;
            },

            viewAccount() {
                return this.$store.state.view === views.ACCOUNT;
            },

            viewLogin() {
                return this.$store.state.view === views.LOGIN;
            },

            viewBoot() {
                return this.$store.state.view === views.BOOT;
            },
        },

        watch: {
            taskRunning(running) {
                document.body.style.overflow = running ? 'hidden' : 'scroll';
            },
        },

        mounted() {
            document.title = 'Contao Manager @package_version@';

            this.$store.dispatch('auth/status').then((statusCode) => {
                if (statusCode === 200) {
                    this.$store.commit('setView', views.BOOT);
                } else if (statusCode === 204) {
                    this.$store.commit('setView', views.ACCOUNT);
                } else if (statusCode === 401) {
                    this.$store.commit('setView', views.LOGIN);
                } else {
                    this.$store.dispatch('apiError', statusCode);
                }
            });
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../assets/styles/bundle.scss";
</style>
