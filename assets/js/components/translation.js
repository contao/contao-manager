'use strict';

const React     = require('react');
const jQuery    = require('jquery');
const Promise   = require('bluebird');
const routing   = require('./helpers/routing.js');

var translate = function(key, placeholders, domain, locale) {
    placeholders = typeof placeholders !== 'undefined' ? placeholders : {};
    domain = typeof domain !== 'undefined' ? domain : 'messages';
    locale = typeof locale !== 'undefined' ? locale : 'fallback';

    if ('fallback' === locale) {
        locale = routing.getLanguage();
    }

    return new Promise(function (resolve, reject) {
        jQuery.ajax({
            url: '/translation/' + locale + '/' + domain
        }).success(function(result) {
            var translation = key;
            if (undefined !== result[key]) {
                translation = result[key];
            }

            // Replace placeholders
            if (placeholders) {
                for (var placeholder in placeholders) {
                    if (placeholders.hasOwnProperty(placeholder)) {
                        translation = translation.replace('%' + placeholder + '%', placeholders[placeholder]);
                    }
                }
            }

            resolve(translation);
        });
    });
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

        this.translatePromise = translate(label, this.props.placeholders, this.props.domain, this.props.locale);
        this.translatePromise
            .then(function(translation) {
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
