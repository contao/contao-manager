<template>
    <div id="app">
        <div class="https-warning" v-if="isInsecure">
            <strong class="https-warning__headline">{{ 'ui.app.httpsHeadline' | translate }}</strong>
            <span class="https-warning__description">{{ 'ui.app.httpsDescription' | translate }}</span>
            <a :href="$t('ui.app.httpsHref')" target="_blank" class="https-warning__link">{{ 'ui.app.httpsLink' | translate }}</a>
        </div>

        <error v-if="hasError"></error>

        <div v-if="isInitializing" class="view-init">
            <div class="view-init__cell">
                <img src="../assets/images/logo.svg" width="100" height="100" alt="Contao Logo">
                <p class="view-init__message">{{ 'ui.app.loading' | translate }}</p>
            </div>
        </div>

        <component :is="currentView" :class="blurView ? 'blur-in' : 'blur-out'" v-else-if="currentView"/>

        <router-view v-else :class="blurView ? 'blur-in' : 'blur-out'"></router-view>

        <keep-alive>
            <logout-warning v-if="warnForLogout"></logout-warning>
            <task-popup v-else-if="taskRunning"></task-popup>
        </keep-alive>
    </div>
</template>

<script>
    import { mapState, mapGetters } from 'vuex';
    import views from '../router/views';

    import TaskPopup from './fragments/TaskPopup';
    import LogoutWarning from './fragments/LogoutWarning';
    import Loader from './fragments/Loader';

    import Error from './views/Error';
    import Account from './views/Account';
    import Login from './views/Login';
    import Boot from './views/Boot';
    import Recovery from './views/Recovery';

    export default {
        components: { Loader, TaskPopup, LogoutWarning, Error },

        data: () => ({
            views: {
                [views.ACCOUNT]: Account,
                [views.LOGIN]: Login,
                [views.BOOT]: Boot,
                [views.RECOVERY]: Recovery,
            }
        }),

        computed: {
            ...mapState(['view', 'error']),
            ...mapState('tasks', { taskStatus: 'status' }),
            ...mapGetters('auth', ['warnForLogout']),

            isInitializing: vm => vm.view === views.INIT,
            isInsecure: () => location.protocol !== 'https:' && location.hostname !== 'localhost',
            taskRunning: vm => vm.taskStatus !== null,
            blurView: vm => vm.warnForLogout || vm.taskRunning,
            hasError: vm => vm.error !== null,

            currentView: vm => vm.views[vm.view] || null,
        },

        watch: {
            taskRunning(running) {
                document.body.style.overflow = running ? 'hidden' : 'scroll';
            },
        },

        async mounted() {
            const accountStatus = await this.$store.dispatch('auth/status');

            const chunks = location.pathname.split('/').filter(v => v !== '');
            chunks.unshift('');

            while (chunks.pop() !== undefined && chunks.length) {
                try {
                    const config = (await this.$http.get(`${chunks.join('/')}/contao-manager/users.json`, { responseType: 'json' })).body;

                    if (!config.hasOwnProperty('users') && !config.hasOwnProperty('version')) {
                        continue;
                    }

                    this.$store.commit('setError', {
                        title: this.$t('ui.app.configSecurity1'),
                        type: 'about:blank',
                        status: '500',
                        detail: this.$t('ui.app.configSecurity2'),
                    });

                    return;
                } catch (err) {
                    // user.json could not be loaded, seems like a valid config
                }
            }

            if (accountStatus === 200) {
                this.$store.commit('setView', views.BOOT);
            } else if (accountStatus === 204) {
                this.$store.commit('setView', views.ACCOUNT);
            } else if (accountStatus === 401) {
                this.$store.commit('setView', views.LOGIN);
            } else {
                this.$store.commit('apiError', accountStatus);
            }
        },

        created() {
            document.title = `Contao Manager @package_version@ | ${location.hostname}`;
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
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
