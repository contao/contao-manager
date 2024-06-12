<template>
    <nav role="navigation" class="navigation">
        <a class="navigation__toggle" @click.prevent="toggleNavigation"><span></span></a>
        <ul class="navigation__group navigation__group--main">
            <router-link tag="li" class="navigation__item navigation__item--main" :to="routes.discover"><a>{{ $t('ui.navigation.discover') }}</a></router-link>
            <router-link tag="li" class="navigation__item navigation__item--main" :to="routes.packages">
                <a>{{ $t('ui.navigation.packages') }}<span class="navigation__item-badge" v-if="packageChanges > 0">{{ packageChanges }}</span></a>
            </router-link>
            <router-link tag="li" class="navigation__item navigation__item--main" :to="routes.maintenance">
                <a>{{ $t('ui.navigation.maintenance') }}<span class="navigation__item-badge" v-if="hasDatabaseChanges || hasDatabaseWarning || hasDatabaseError">1</span></a>
            </router-link>
            <li class="navigation__item navigation__item--main">
                <a tabindex="0" aria-haspopup="true" onclick="">{{ $t('ui.navigation.tools') }}</a>
                <ul class="navigation__group navigation__group--sub">
                    <li class="navigation__item navigation__item--sub" v-if="!safeMode"><a :href="backendUrl">{{ $t('ui.navigation.backend') }}</a></li>
                    <li class="navigation__item navigation__item--sub" v-if="!safeMode && showAppDev"><a href="/app_dev.php/" target="_blank">{{ $t('ui.navigation.debug') }}</a></li>
                    <li class="navigation__item navigation__item--sub" v-if="!safeMode && showPreview"><a :href="previewUrl" target="_blank">{{ $t('ui.navigation.debug') }}</a></li>
                    <li class="navigation__item navigation__item--sub" v-if="!safeMode && showInstallTool"><a :href="installToolUrl" target="_blank">{{ $t('ui.navigation.installTool') }}</a></li>
                    <router-link tag="li" :to="routes.logViewer" class="navigation__item navigation__item--sub"><a>{{ $t('ui.navigation.logViewer') }}</a></router-link>
                    <li class="navigation__item navigation__item--sub"><a href="#" @click.prevent="phpinfo">{{ $t('ui.navigation.phpinfo') }}</a></li>
                </ul>
            </li>
            <li class="navigation__item navigation__item--main navigation__item--icon">
                <a tabindex="0" aria-haspopup="true" onclick="">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 204.993 204.993"><path d="M113.711 202.935H92.163c-3.242 0-4.373.007-15.421-27.364l-8.532-3.468c-23.248 10.547-26 10.547-26.92 10.547h-1.779l-1.517-1.303-15.275-14.945c-2.323-2.319-3.128-3.124 8.825-30.137l-3.479-8.231C0 117.977 0 116.81 0 113.496V92.37c0-3.31 0-4.355 27.972-15.171l3.479-8.249c-12.644-26.602-11.774-27.428-9.28-29.776l16.427-16.105 2.04-.064c2.48 0 11.681 3.357 27.371 9.981l8.507-3.454C86.758 2.054 88.015 2.058 91.246 2.058h21.548c3.228 0 4.363.004 15.411 27.382l8.546 3.443c23.212-10.533 26-10.533 26.927-10.533h1.768l1.517 1.281 15.275 14.92c2.323 2.344 3.117 3.146-8.836 30.17l3.489 8.278c28.101 10.014 28.101 11.177 28.101 14.498v21.101c0 3.232 0 4.37-28.008 15.192l-3.457 8.256c12.58 26.487 11.749 27.317 9.394 29.69l-16.552 16.205-2.051.057c-2.469 0-11.649-3.361-27.317-9.992l-8.557 3.457c-10.27 27.472-11.437 27.472-14.733 27.472zm-19.308-8.722h16.996c1.95-3.976 6.166-14.516 9.541-23.595l.68-1.807 15.475-6.249 1.664.705c9.223 3.933 20.124 8.292 24.372 9.631l11.943-11.681c-1.517-4.205-6.116-14.494-10.264-23.173l-.837-1.764 6.403-15.285 1.743-.673c9.316-3.586 20.11-8.013 24.143-10.032V93.88c-4.08-1.918-14.831-6.009-24.096-9.294l-1.814-.648-6.445-15.3.769-1.725c3.965-8.947 8.375-19.501 9.788-23.753l-11.975-11.706c-3.865 1.349-14.688 5.987-23.817 10.153l-1.7.78-15.475-6.238-.691-1.721c-3.658-9.13-8.203-19.716-10.253-23.635H93.569c-1.961 3.965-6.163 14.509-9.53 23.585l-.669 1.797-15.432 6.27-1.664-.712c-9.244-3.926-20.167-8.278-24.429-9.616L29.923 43.805c1.496 4.198 6.109 14.48 10.243 23.159l.848 1.768-6.435 15.278-1.732.669c-9.301 3.582-20.077 8.006-24.111 10.017v16.431c4.08 1.925 14.82 6.027 24.079 9.326l1.8.655 6.446 15.249-.769 1.721c-3.965 8.94-8.371 19.48-9.788 23.724l12 11.742c3.854-1.36 14.663-5.998 23.803-10.168l1.711-.784 15.443 6.277.691 1.721c3.669 9.133 8.2 19.701 10.251 23.623zm8.092-56.56c-19.759 0-35.849-15.772-35.849-35.159 0-19.372 16.087-35.134 35.849-35.134 19.748 0 35.799 15.765 35.799 35.134 0 19.387-16.051 35.159-35.799 35.159zm0-61.563c-14.956 0-27.113 11.846-27.113 26.405 0 14.569 12.154 26.426 27.113 26.426 14.931 0 27.078-11.857 27.078-26.426-.004-14.559-12.147-26.405-27.078-26.405z"/></svg>
                    <span>{{ $t('ui.navigation.advanced') }}</span>
                </a>
                <ul class="navigation__group navigation__group--sub navigation__group--right">
                    <li class="navigation__item navigation__item--sub"><a href="#" @click.prevent="systemCheck">{{ $t('ui.navigation.systemCheck') }}</a></li>
                    <li class="navigation__item navigation__item--sub"><a href="#" @click.prevent="logout">{{ $t('ui.navigation.logout') }}</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</template>

<script>
    import { mapState, mapGetters, mapActions } from 'vuex';

    import views from '../../router/views';
    import routes from '../../router/routes';

    export default {
        data: () => ({
            routes,
            databaseChanges: 0,
        }),

        computed: {
            ...mapState(['safeMode']),
            ...mapState('contao/install-tool', { showInstallTool: 'isSupported' }),
            ...mapState('contao/access-key', { showAppDev: 'isEnabled' }),
            ...mapState('contao/jwt-cookie', { showPreview: 'isDebugEnabled' }),
            ...mapState('server/contao', ['contaoConfig']),
            ...mapGetters('packages', ['totalChanges']),
            ...mapGetters('packages/uploads', ['totalUploads']),
            ...mapGetters('server/database', { hasDatabaseChanges: 'hasChanges', hasDatabaseWarning: 'hasWarning', hasDatabaseError: 'hasError' }),

            packageChanges: vm => vm.totalChanges + vm.totalUploads,

            backendUrl: vm => vm.contaoConfig?.backend?.route_prefix || '/contao',
            previewUrl: vm => `${vm.contaoConfig?.backend?.preview_script || '/preview.php'}/`,
            installToolUrl: vm => `${vm.contaoConfig?.backend?.route_prefix || '/contao'}/install`,
        },

        methods: {
            ...mapActions('auth', ['logout']),

            toggleNavigation() {
                document.body.classList.toggle('nav-active');
            },

            phpinfo() {
                const popup = window.open();

                if (popup) {
                    popup.document.open();
                    popup.document.write('<p class="phpinfo__loading" style="display: flex; justify-content: center; align-items: center; min-height: 100vh; font: 4vmin -apple-system, system-ui, &quot;Segoe UI&quot;, Roboto, Oxygen-Sans, Ubuntu, Cantarell, &quot;Helvetica Neue&quot;, sans-serif;">');
                    popup.document.write(this.$t('ui.navigation.phpinfoLoading'));
                    popup.document.write('</p>');

                    this.$store.dispatch('server/phpinfo/get').then((content) => {
                        popup.document.write(content);
                        popup.document.close();
                        popup.document.body.removeChild(popup.document.querySelector('.phpinfo__loading'));
                    });
                }
            },

            systemCheck() {
                window.localStorage.removeItem('contao_manager_booted');
                this.$store.commit('setView', views.BOOT);
            },
        },

        mounted() {
            this.$store.dispatch('contao/install-tool/fetch');
            this.$store.dispatch('contao/jwt-cookie/get').catch(() => {});
            this.$store.dispatch('contao/access-key/get').catch(() => {});
            this.$store.dispatch('server/database/get');
        },
    };
</script>


<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    $nav-offset: 280px;

    body.nav-active {
        overflow: hidden !important;
    }

    #app {
        transition: transform 0.4s cubic-bezier(0.55, 0, 0.1, 1);

        .nav-active & {
            overflow-y: visible;
            transform: translateX(-$nav-offset);

            @include screen(1024) {
                transform: none;
            }
        }
    }

    .navigation {
        float: right;

        &__toggle {
            display: block;
            float: right;
            position: relative;
            margin: 5px 15px;
            padding: 0;
            width: 30px;
            height: 30px;
            cursor: pointer;
            z-index: 20;

            span,
            span:before,
            span:after {
                content: '';
                display: block;
                width: 100%;
                height: 4px;
                background: var(--text);
                border-radius: 4px;
                position: absolute;
            }

            span {
                transition-duration: 0.075s;
                transition-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
                top: 50%;
                margin-top: -2px;

                &:before {
                    top: -10px;
                    transition: top 0.075s 0.12s ease, opacity 0.075s ease;
                }

                &:after {
                    bottom: -10px;
                    transition: bottom 0.075s 0.12s ease, transform 0.075s cubic-bezier(0.55, 0.055, 0.675, 0.19);
                }
            }

            .nav-active & {

                span {
                    transform: rotate(45deg);
                    transition-delay: 0.12s;
                    transition-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);

                    &:before {
                        top: 0;
                        opacity: 0;
                        transition: top 0.075s ease, opacity 0.075s 0.12s ease;
                    }

                    &:after {
                        transition: bottom 0.075s ease, transform 0.075s 0.12s cubic-bezier(0.215, 0.61, 0.355, 1);
                        bottom: 0;
                        transform: rotate(-90deg);
                    }
                }
            }
        }

        &__group,
        &__item {
            list-style-type: none;
            margin: 0;
            padding: 0;
        }

        &__group--main {
            position: fixed;
            top: 0;
            bottom: 0;
            right: -$nav-offset;
            width: $nav-offset;
            padding: 20px;
            overflow-y: auto;
            overflow-scrolling: touch;
            background: var(--header-main-bg);
            border-left: 1px solid var(--header-bdr);
            z-index: 10;
        }

        &__item {
            a {
                display: block;
                padding: 12px 10px;
                font-size: 16px;
                color: var(--text);
                white-space: pre;

                &:hover {
                    text-decoration: none;
                }

                &[href]:hover {
                    color: var(--link);
                }
            }

            &--main {
                > a {
                    text-transform: uppercase;
                }
            }

            &--sub {
                > a {
                    margin-left: 15px;
                }
            }

            &--icon {
                svg {
                    display: none;
                }
            }
        }

        &__item-badge {
            position: relative;
            top: -2px;
            margin-left: 8px;
            padding: 2px 5px;
            font-size: 10px;
            color: var(--clr-btn);
            font-weight: $font-weight-bold;
            background: var(--contao);
            border-radius: 40%;
        }

        @include screen(1024) {
            &__toggle {
                display: none;
            }

            &__group {
                &--main {
                    position: inherit;
                    top: auto;
                    bottom: auto;
                    right: auto;
                    width: auto;
                    padding: 0;
                    overflow: visible;
                    background: none;
                    border: none;
                    box-shadow: none;
                    transform: none;
                    transition: none;
                }

                &--sub {
                    display: none;
                    position: absolute;
                    left: 50%;
                    min-width: 180px;
                    margin-top: -3px;
                    text-align: center;
                    background: var(--form-bg);
                    border-top: 3px solid var(--link);
                    border-radius: 5px;
                    transform: translateX(-50%);
                    z-index: 100;
                    box-shadow: 0 0 2px var(--shadow);

                    &:before {
                        position: absolute;
                        left: 50%;
                        top: -7px;
                        width: 0;
                        height: 0;
                        margin-left: -4px;
                        border-style: solid;
                        border-width: 0 3.5px 4px 3.5px;
                        border-color: transparent transparent var(--link) transparent;
                        content: "";
                    }
                }

                &--right {
                    left: auto;
                    right: 7px;
                    transform: translateX(0);

                    &:before {
                        left: auto;
                        right: 18px;
                    }
                }
            }

            &__item {
                position: relative;
                display: inline-block;
                padding: 0 8px;

                &.router-link-active > a,
                &:hover > a {
                    color: var(--link) !important;
                    border-bottom: 3px solid var(--link);
                }

                &:hover > .navigation__group--sub {
                    display: block;
                }

                &--sub {
                    display: block;
                    margin: calc(var(--border-radius) / 2);
                    border-radius: var(--border-radius);

                    a {
                        margin: 0;
                        border: none !important;
                    }

                    &.router-link-active,
                    &:hover {
                        background: var(--focus);

                        a {
                            color: var(--text) !important;
                        }
                    }
                }

                &--icon > a {
                    padding-top: 7px;

                    svg {
                        display: inline;
                        position: relative;
                        top: 4px;
                        width: 22px;
                        height: 22px;
                        fill: var(--text);
                    }

                    &:hover svg {
                        fill: var(--link);
                    }

                    span {
                        display: none;
                    }
                }
            }

            &:hover {
                li > a {
                    border: none;
                }

                li:hover > a {
                    border-bottom: 3px solid var(--link);

                    svg {
                        fill: var(--link);
                    }
                }
            }
        }
    }
</style>
