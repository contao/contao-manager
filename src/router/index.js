import { createRouter, createWebHashHistory } from 'vue-router';

import routes from './routes';

import DiscoverRoute from '../components/routes/DiscoverRoute';
import PackagesListRoute from '../components/routes/PackageListRoute';
import OAuthRoute from '../components/routes/OAuthRoute';
import MaintenanceRoute from '../components/routes/MaintenanceRoute';
import LogViewerRoute from '../components/routes/LogViewerRoute';
import UserManagerRoute from '../components/routes/UserManagerRoute';

const router = createRouter({
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
            name: routes.oauth.name,
            path: '/oauth',
            component: OAuthRoute,
            props: true,
        },
        {
            name: routes.maintenance.name,
            path: '/maintenance',
            component: MaintenanceRoute,
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
        },
        { path: '/:pathMatch(.*)*', redirect: '/discover' },
    ],
});

export default router;
