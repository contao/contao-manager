<template>
    <div id="app">
        <Notivue v-slot="item"><Notification :item="item" /></Notivue>

        <div class="app-hint app-hint--alert" v-if="safeMode && view === 'ready'">
            <strong class="app-hint__headline">{{ $t('ui.app.safeModeHeadline') }}</strong>&nbsp;
            <span class="app-hint__description">{{ $t('ui.app.safeModeDescription') }}</span>&nbsp;
            <button class="app-hint__link" @click="() => window.location.reload()">{{ $t('ui.app.safeModeExit') }}</button>
        </div>

        <div class="app-hint" v-if="limited">
            <strong class="app-hint__headline">{{ $t('ui.app.limitedHeadline') }}</strong>&nbsp;
            <span class="app-hint__description">{{ $t('ui.app.limitedDescription') }}</span>&nbsp;
            <button class="app-hint__link" @click="logout">{{ $t('ui.app.limitedLogout') }}</button>
        </div>

        <div class="app-hint app-hint--warning" v-else-if="isInsecure">
            <strong class="app-hint__headline">{{ $t('ui.app.httpsHeadline') }}</strong>&nbsp;
            <span class="app-hint__description">{{ $t('ui.app.httpsDescription') }}</span>&nbsp;
            <a :href="$t('ui.app.httpsHref')" target="_blank" class="app-hint__link">{{ $t('ui.app.httpsLink') }}</a>
        </div>

        <error-view v-if="error"/>

        <transition name="animate-fade" mode="out-in" style="height:100%">

            <div v-if="isInitializing || isReady" class="view-init">
                <div class="view-init__cell animate-initializing">
                    <img src="../assets/images/logo.svg" width="100" height="100" alt="Contao Logo">
                    <p class="view-init__message">{{ $t('ui.app.loading') }}</p>
                </div>
            </div>

            <task-view :class="hasModal ? 'animate-blur-in' : 'animate-blur-out'" v-else-if="username && taskStatus"/>
            <component :is="currentView" :class="hasModal ? 'animate-blur-in' : 'animate-blur-out'" v-else-if="currentView"/>

            <div v-else>
                <router-view :class="hasModal ? 'animate-blur-in' : 'animate-blur-out'"/>
            </div>

        </transition>

        <component :is="currentModal" v-if="hasModal"/>
    </div>
</template>

<script>
    import { defineAsyncComponent, markRaw } from "vue";
    import { mapState, mapGetters, mapActions } from 'vuex';
    import axios from 'axios';
    import { Notivue, Notification } from 'notivue';
    import views from '../router/views';

    import ErrorView from './views/ErrorView';
    import TaskView from './views/TaskView';

    export default {
        components: { ErrorView, TaskView, Notivue, Notification },

        data: () => ({
            views: {
                [views.ACCOUNT]: markRaw(defineAsyncComponent(() => import('././views/AccountView'))),
                [views.LOGIN]: markRaw(defineAsyncComponent(() => import('././views/LoginView'))),
                [views.BOOT]: markRaw(defineAsyncComponent(() => import('././views/BootView'))),
                [views.CONFIG]: markRaw(defineAsyncComponent(() => import('././views/ConfigView'))),
                [views.SETUP]: markRaw(defineAsyncComponent(() => import('././views/SetupView'))),
                [views.RECOVERY]: markRaw(defineAsyncComponent(() => import('././views/RecoveryView'))),
                [views.MIGRATION]: markRaw(defineAsyncComponent(() => import('././views/MigrationView'))),
            },
            loaded: false,
        }),

        computed: {
            ...mapState(['safeMode']),
            ...mapState(['view', 'error']),
            ...mapState('auth', ['username', 'limited']),
            ...mapState('tasks', { taskStatus: 'status' }),
            ...mapGetters('modals', ['hasModal', 'currentModal']),

            isInitializing: vm => vm.view === views.INIT,
            isReady: vm => !vm.isInitializing && !vm.currentView && !vm.loaded,
            isInsecure: () => location.protocol !== 'https:' && location.hostname !== 'localhost',

            currentView: vm => vm.views[vm.view] || null,
        },

        methods: {
            ...mapActions('auth', ['logout']),

            initColorMode() {
                let prefersDark = localStorage.getItem('contao--prefers-dark');

                if (null === prefersDark) {
                    prefersDark = String(window.matchMedia('(prefers-color-scheme: dark)').matches);
                }

                document.documentElement.dataset.colorScheme = prefersDark === 'true' ? 'dark' : 'light';
            },

            async checkPublicConfig () {
                const chunks = location.pathname.split('/').filter(v => v !== '');
                chunks.unshift('');

                while (chunks.pop() !== undefined && chunks.length) {
                    let config;

                    try {
                        config = (await axios.get(`${chunks.join('/')}/contao-manager/users.json`)).data;
                    } catch (err) {
                        // user.json could not be loaded, seems like a valid config
                        continue;
                    }

                    if (!config.users && !config.version) {
                        continue;
                    }

                    this.$store.commit('setError', {
                        title: this.$t('ui.app.configSecurity1'),
                        type: 'about:blank',
                        status: '500',
                        detail: this.$t('ui.app.configSecurity2'),
                    });

                    throw new Error(this.$t('ui.app.configSecurity1'));
                }
            }
        },

        watch: {
            async isReady(ready) {
                if (ready) {
                    try {
                        await this.$store.dispatch('packages/uploads/load');
                        await this.$store.dispatch('packages/load');
                        await this.$store.dispatch('algolia/discover');
                    } catch (err) {
                        // do nothing
                    }

                    this.loaded = true;
                    this.$store.dispatch('packages/details/init', {
                        vue: this,
                        component: defineAsyncComponent(() => import('./fragments/PackageDetails'))
                    });
                }
            },

            username(username) {
                if (username === null) {
                    this.$store.commit('tasks/setCurrent', null);
                    this.$store.commit('tasks/setInitialized', false);
                }
            },
        },

        async mounted() {
            this.initColorMode();
            await this.checkPublicConfig()

            await this.$router.isReady();

            if (this.$route.query.invitation) {
                this.$store.commit('setView', views.ACCOUNT);
                return;
            }

            if (this.$route.query.token) {
                try {
                    await axios.post('api/session', { token: this.$route.query.token });
                } catch (err) {
                    // ignore authentication errors
                }

                this.$router.replace({ name: this.$route.name, query: null });
            }

            const accountStatus = await this.$store.dispatch('auth/status');

            if (accountStatus === 200) {
                this.$store.commit('setView', views.BOOT);
            } else if (accountStatus === 204) {
                this.$store.commit('setView', views.ACCOUNT);
            } else if (accountStatus === 401 || accountStatus === 403) {
                this.$store.commit('setView', views.LOGIN);
            } else {
                this.$store.commit('setError', { type: 'about:blank', status: accountStatus });
            }
        },

        created() {
            document.title = `Contao Manager | ${location.hostname}`;
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
$icons: (
    'add',
    'check',
    'clipboard',
    'cloud',
    'cloud-off',
    'console',
    'database',
    'download',
    'edit',
    'gear',
    'hide',
    'details',
    'link',
    'lock',
    'maintenance',
    'more',
    'power',
    'run',
    'save',
    'search',
    'show',
    'trash',
    'unlock',
    'update',
    'upload',
);

@use "~contao-package-list/src/assets/styles/layout";
@use "~contao-package-list/src/assets/styles/forms" with ($icons: $icons);
@use "~contao-package-list/src/assets/styles/animations";
@use "~contao-package-list/src/assets/styles/defaults";
@use "../assets/styles/defaults" as AppDefaults;

@import '~notivue/notifications.css';
@import '~notivue/animations.css';

.app-hint {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 27px;
    padding: 4px 8px;
    background: var(--btn-info);
    color: #fff;
    text-align: center;
    z-index: 100;

    &--warning {
        background: var(--btn-warning);
    }

    &--alert {
        background: var(--btn-alert);
    }

    &__description {
        display: none;

        @include defaults.screen(600) {
            display: inline;
        }
    }

    &__link {
        margin: 0;
        padding: 0;
        background: none;
        border: none;
        color: #fff;
        text-decoration: underline;
        cursor: pointer;
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
    }
}
</style>
