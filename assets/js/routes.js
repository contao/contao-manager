import composerjson from './routes/composer-json';
import install from './routes/install';
import login from './routes/login';
import logout from './routes/logout';
import maintenance from './routes/maintenance';
import packages from './routes/packages';
import selftest from './routes/self-test';

export default [
    install,
    login,
    packages,
    maintenance,
    composerjson,
    selftest,
    logout,
]
