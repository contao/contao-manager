import Vue from 'vue';
import Router from 'vue-router';

import routes from './routes';

import Packages from '../components/packages/Base';
import PackagesList from '../components/packages/List';
import OAuth from '../components/routes/OAuth';
import Maintenance from '../components/routes/Maintenance';

const PackagesSearch = () => new Promise(
    (resolve) => {
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = 'https://cdn.jsdelivr.net/algoliasearch/3.22.1/algoliasearchLite.min.js';
        script.setAttribute('integrity', 'sha256-af2RXe0fkPuUqhxbsRoVPlEumRNuCaJwDVBnAj2uZcI=');
        script.setAttribute('crossorigin', 'anonymous');
        script.addEventListener('load', () => resolve(script), false);
        script.addEventListener('error', () => resolve(script), false);
        document.body.appendChild(script);
    }).then(() => import('../components/packages/Search'));

Vue.use(Router);

const router = new Router({
    mode: 'history',
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
