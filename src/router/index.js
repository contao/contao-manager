import Vue from 'vue';
import Router from 'vue-router';

import routes from './routes';

import PackagesList from '../components/routes/PackageList';
import PackagesSearch from '../components/routes/PackageSearch';
import OAuth from '../components/routes/OAuth';
import Maintenance from '../components/routes/Maintenance';

Vue.use(Router);

const router = new Router({
    routes: [
        {
            name: routes.packages.name,
            path: '/packages',
            component: PackagesList,
        },
        {
            name: routes.packagesSearch.name,
            path: '/packages/search',
            component: PackagesSearch,
            props: true,
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
        { path: '*', redirect: '/packages' },
    ],
});

export default router;
