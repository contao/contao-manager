'use strict';

var makeCancellable = function(promise) {
    var isCancelled = false;

    var wrappedPromise = new Promise(function(resolve, reject) {
        promise.then(function (val) {
            isCancelled ? reject({isCancelled: true}) : resolve(val)
        });
        promise.catch(function (error) {
            isCancelled ? reject({isCancelled: true}) : reject(error)
        });
    });

    return {
        promise: wrappedPromise,
        cancel: function() {
            isCancelled = true;
        }
    };
};

module.exports = {
    makeCancellable: makeCancellable
};
