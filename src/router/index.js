import { createRouter, createWebHashHistory } from 'vue-router';

import routes from './routes';

import DiscoverRoute from '../components/routes/DiscoverRoute';
import PackagesListRoute from '../components/routes/PackageListRoute';
import MaintenanceRoute from '../components/routes/MaintenanceRoute';
import MigrationsRoute from '@/components/routes/MigrationsRoute.vue';
import LogViewerRoute from '../components/routes/LogViewerRoute';
import UserManagerRoute from '../components/routes/UserManagerRoute';
import OAuthRoute from '../components/routes/OAuthRoute';

const router = (store) =>
    createRouter({
        history: createWebHashHistory(),
        routes: [
            {
                name: routes.discover.name,
                path: '/discover',
                component: DiscoverRoute,
            },
            {
                name: routes.packages.name,
                path: '/packages',
                component: PackagesListRoute,
            },
            {
                name: routes.maintenance.name,
                path: '/maintenance',
                component: MaintenanceRoute,
            },
            {
                name: routes.migrations.name,
                path: '/migrations/:type(.*)*',
                component: MigrationsRoute,
            },
            {
                name: routes.logViewer.name,
                path: '/logs',
                component: LogViewerRoute,
            },
            {
                name: routes.userManager.name,
                path: '/users',
                component: UserManagerRoute,
                beforeEnter: (to, from, next) => {
                    if (store.state.auth.limited) {
                        next(from);
                    } else {
                        next();
                    }
                },
            },
            {
                name: routes.oauth.name,
                path: '/oauth',
                component: OAuthRoute,
                props: true,
            },
            { path: '/:pathMatch(.*)*', redirect: '/discover' },
        ],
    });

export default router;
