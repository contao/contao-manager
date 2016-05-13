'use strict';

module.exports = {
    ifTensideNotOkRedirectToInstall: function (results, routing) {
        if (false === results['tenside_configured']
            || false === results['project_created']
            || false === results['project_installed']
        ) {
            routing.redirect('install');
        }
    },

    ifUserNotLoggedInRedirectToLogin: function (results, routing) {
        if (false === results['user_loggedIn']) {
            routing.redirect('login');
        }
    }
};
