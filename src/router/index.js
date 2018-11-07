import Vue from 'vue';
import Router from 'vue-router';

import routes from './routes';

import Packages from '../components/packages/Base';
import PackagesList from '../components/packages/List';
import PackagesSearch from '../components/packages/Search';
import OAuth from '../components/routes/OAuth';
import Maintenance from '../components/routes/Maintenance';

Vue.use(Router);

const router = new Router({
    routes: [
        {
            path: '/packages',
            component: Packages,
            children: [
                {
                    name: routes.packages.name,
                    path: '',
                    component: PackagesList,
                },
                {
                    name: routes.packagesSearch.name,
                    path: 'search',
                    component: PackagesSearch,
                    props: true,
                },
            ],
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
