'use strict';

const React     = require('react');
const jQuery    = require('jquery');
const Promise   = require('promise');
const lscache   = require('lscache');

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

    var cacheKey = 'cpm:translations:' + locale + ':' + domain;

    return new Promise(function (resolve, reject) {

        var cache = lscache.get(cacheKey);

        if (cache) {
            resolve(cache);
            return;
        }

        jQuery.ajax({
            url: '/translation/' + locale + '/' + domain
        }).success(function(result) {
            lscache.set(cacheKey, result, 60); // expire after 1 hour
            resolve(result);
        });
    });
};

var Translation = React.createClass({
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

        translate(this.props.children, this.props.domain, this.props.locale)
            .then(function(result) {
                self.setState({label: result[label]});
            });
    },
    render: function() {

        return (
            <span>{this.state.label}</span>
        )
    }
});

module.exports = Translation;
