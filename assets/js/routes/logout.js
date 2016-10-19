import { setRequestToken } from './../helpers/request';

module.exports = {
    path: '/{locale}/logout',
    controller: function(request, routing) {
        setRequestToken('');
        routing.redirect('login');
    }
};
