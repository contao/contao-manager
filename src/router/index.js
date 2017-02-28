import Vue from 'vue';
import Router from 'vue-router';

import Login from '../components/Login';
import Install from '../components/Install';
import Error from '../components/Error';
import Packages from '../components/packages/Base';
import PackagesList from '../components/packages/List';
import PackagesSearch from '../components/packages/Search';

import store from '../store';
import apiStatus from '../api/status';

Vue.use(Router);

const router = new Router({
    routes: [
        {
            path: '/',
            redirect: '/login',
        },
        {
            name: 'login',
            path: '/login',
            component: Login,
        },
        {
            name: 'install',
            path: '/install',
            component: Install,
        },
        {
            name: 'error',
            path: '/error',
            component: Error,
        },
        {
            path: '/packages',
            component: Packages,
            children: [
                {
                    name: 'packages',
                    path: '',
                    component: PackagesList,
                },
                {
                    name: 'packages-search',
                    path: 'search',
                    component: PackagesSearch,
                    props: true,
                },
            ],
        },
    ],
});

router.beforeEach((to, from, next) => {
    store.dispatch('fetchStatus').then(
        (status) => {
            if (status === apiStatus.AUTHENTICATE && to.name !== 'login') {
                next('login');
            }

            if ((status === apiStatus.NEW || status === apiStatus.EMPTY) && to.name !== 'install') {
                next('install');
            }

            if ((status === apiStatus.CONFLICT || status === apiStatus.ERROR) && to.name !== 'error') {
                next('error');
            }

            next();
        },
    );
});

export default router;
