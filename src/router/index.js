import Vue from 'vue';
import Router from 'vue-router';

import scopes from './scopes';
import routes from './routes';

import Login from '../components/Login';
import Install from '../components/Install';
import SelfTest from '../components/SelfTest';
import Packages from '../components/packages/Base';
import PackagesList from '../components/packages/List';
import PackagesSearch from '../components/packages/Search';

Vue.use(Router);

const router = new Router({
    routes: [
        {
            path: '/',
            redirect: routes.login,
        },
        {
            name: routes.fail.name,
            path: '/fail',
            meta: { scope: scopes.FAIL },
            component: SelfTest,
        },
        {
            name: routes.login.name,
            path: '/login',
            meta: { scope: scopes.LOGIN },
            component: Login,
        },
        {
            name: routes.install.name,
            path: '/install',
            meta: { scope: scopes.INSTALL },
            component: Install,
        },
        {
            name: routes.installCheck.name,
            path: '/install/check',
            meta: { scope: scopes.INSTALL },
            component: SelfTest,
        },
        {
            path: '/packages',
            component: Packages,
            children: [
                {
                    name: routes.packages.name,
                    path: '',
                    meta: { scope: scopes.MANAGER },
                    component: PackagesList,
                },
                {
                    name: routes.packagesSearch.name,
                    path: 'search',
                    meta: { scope: scopes.MANAGER },
                    component: PackagesSearch,
                    props: true,
                },
            ],
        },
    ],
});

router.beforeEach((to, from, next) => {
    if (to.meta.scope === undefined
        || (router.scope !== undefined && router.scope !== to.meta.scope)
    ) {
        console.log(`routing "${to.name}" is denied in scope "${router.scope}"`);
        next(false);
    } else {
        next();
    }
});

export default router;
