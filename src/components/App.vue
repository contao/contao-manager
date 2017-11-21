<template>
    <div id="app">
        <div class="https-warning" v-if="isInsecure">
            <strong class="https-warning__headline">{{ 'ui.app.httpsHeadline' | translate }}</strong>
            <span class="https-warning__description">{{ 'ui.app.httpsDescription' | translate }}</span>
            <a :href="$t('ui.app.httpsHref')" target="_blank" class="https-warning__link">{{ 'ui.app.httpsLink' | translate }}</a>
        </div>

        <error v-if="hasError"></error>

        <div v-if="viewInit" class="view-init">
            <div class="view-init__cell">
                <img src="../assets/images/logo.svg" width="100" height="100" alt="Contao Logo">
                <p class="view-init__message">{{ 'ui.app.loading' | translate }}</p>
            </div>
        </div>

        <account v-else-if="viewAccount"></account>
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

    import Error from './views/Error';
    import Account from './views/Account';
    import Login from './views/Login';
    import Boot from './views/Boot';

    export default {
        components: { Loader, TaskPopup, Error, Account, Login, Boot },

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
    @import url('https://fonts.googleapis.com/css?family=Lato:100&text=Contao%20Manager');
    @import url('https://fonts.googleapis.com/css?family=Lato:300,300italic,400,400italic,700&subset=latin-ext');

    @import "../assets/styles/defaults";
    @import "../assets/styles/layout";

    .https-warning {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 27px;
        padding: 4px 8px;
        background: $orange-button;
        color: #fff;
        text-align: center;
        z-index: 100;

        &__description {
            display: none;

            @include screen(600) {
                display: inline;
            }
        }

        &__link {
            color: #fff;
            text-decoration: underline;
        }

        + div {
            padding-top: 25px;
        }
    }

    .view-init {
        display: table;
        width: 100%;
        height: 100%;

        &__cell {
            display: table-cell;
            font-size: 1.5em;
            text-align: center;
            vertical-align: middle;
            animation: initializing 1s linear infinite;
        }
    }

    .blur-in {
        position: absolute;
        height: 100%;
        width: 100%;
        z-index: -1;
        opacity: 0.5;
        filter: blur(4px);
        transition: opacity .5s, filter .5s;
    }

    .blur-out {
        opacity: 1;
        transition: opacity .5s;
    }

    @keyframes initializing {
        0% {
            opacity: 0.5;
        }
        50% {
            opacity: 1;
        }
        100% {
            opacity: 0.5;
        }
    }
</style>
