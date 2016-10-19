import React    from 'react';
import ReactDOM from 'react-dom';
import App      from './../components/app';
import Login    from './../components/routes/login';

module.exports = {
    path: '/{locale}/login',
    preController: [function(results, routing) {
        // Not configured yet
        if (false === results['tenside_configured']) {
            routing.redirect('install');
            return false;
        }

        // Already logged in
        if (true === results['user_loggedIn']) {
            routing.redirect('packages');
            return false;
        }

        return true;
    }],
    controller: function(request, routing) {
        ReactDOM.render(<App routing={routing}><Login /></App>, document.getElementById('react-container'));
    }
};
