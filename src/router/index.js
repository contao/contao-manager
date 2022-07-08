import Vue from 'vue';
import Router from 'vue-router';

import store from '../store';
import routes from './routes';

import Discover from '../components/routes/Discover';
import PackagesList from '../components/routes/PackageList';
import OAuth from '../components/routes/OAuth';
import Maintenance from '../components/routes/Maintenance';
import DatabaseMigration from '../components/routes/DatabaseMigration';

Vue.use(Router);

const router = new Router({
    routes: [
        {
            name: routes.discover.name,
            path: '/discover',
            component: Discover,
        },
        {
            name: routes.packages.name,
            path: '/packages',
            component: PackagesList,
        },
        {
            name: routes.oauth.name,
            path: '/oauth',
            component: OAuth,
            props: true,
        },
        {
            name: routes.maintenance.name,
            path: '/maintenance',
            component: Maintenance,
        },
        {
            name: routes.databaseMigration.name,
            path: '/database-migration',
            component: DatabaseMigration,
            beforeEnter: async (to, from, next) => {
                if (store.state.server.database.supported) {
                    next();
                } else {
                    next({
                        name: routes.discover.name
                    })
                }
            },
        },
        { path: '*', redirect: '/discover' },
    ],
});

router.afterEach(() => {
    document.body.classList.remove('nav-active');
});

export default router;
