const Promise       = require('bluebird');
const jQuery        = require('jquery');


module.exports = {
    getState: function() {
        return new Promise(function (resolve, reject) {
            jQuery.ajax('/api/v1/install/get_state', {
                dataType: 'json'
            }).success(function (response) {
                if ('OK' === response.status) {
                    resolve(response.state)
                } else {
                    reject(response);
                }

            }).fail(function (err) {
                reject(err);
            });
        });
    }
};
