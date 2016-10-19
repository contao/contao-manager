import { setRequestToken } from './../helpers/request';

export default {
    name: 'logout',
    path: '/{locale}/logout',
    controller: function(request, routing) {
        setRequestToken('');
        routing.redirect('login');
    }
}
