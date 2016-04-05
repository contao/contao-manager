'use strict';

const React                 = require('react');
const jQuery                = require('jquery');
const Promise               = require('promise');
const cancellablePromise    = require('./helpers/cancellable-promise.js');

var translate = function(key, domain, locale) {
    domain = typeof domain !== 'undefined' ? domain : 'messages';
    locale = typeof locale !== 'undefined' ? locale : 'fallback';

    if ('fallback' === locale) {
        if (undefined !== document
            && undefined !== document.getElementsByTagName('html')[0]
            && undefined !== document.getElementsByTagName('html')[0].lang) {
            locale = document.getElementsByTagName('html')[0].lang;
        } else {
            locale = 'en';
        }
    }

    var promise = new Promise(function (resolve, reject) {
        jQuery.ajax({
            url: '/translation/' + locale + '/' + domain
        }).success(function(result) {
            resolve(result);
        });
    });

    return cancellablePromise.makeCancellable(promise);
};

var Translation = React.createClass({

    translatePromise: null,

    getInitialState: function() {
        return {
            label:  '',
            domain: this.props.domain,
            locale: this.props.locale
        };
    },
    componentDidMount: function() {

        var label = this.props.children;
        var self = this;

        this.translatePromise = translate(label, this.props.domain, this.props.locale);
        this.translatePromise
            .promise
            .then(function(result) {
                var translation = result[label];

                // Replace placeholders
                if (self.props.placeholders) {
                    for (var placeholder in self.props.placeholders) {
                        if (self.props.placeholders.hasOwnProperty(placeholder)) {
                            translation = translation.replace('%' + placeholder + '%', self.props.placeholders[placeholder]);
                        }
                    }
                }

                self.setState({label: translation});
            })
            .catch(function(err) {
                // catch isCancelled
            });
    },

    componentWillUnmount: function() {
        this.translatePromise.cancel();
    },

    render: function() {

        return (
            <span>{this.state.label}</span>
        )
    }
});

module.exports = Translation;
