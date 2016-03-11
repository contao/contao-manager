'use strict';

const React = require('react');
const jQuery = require('jquery');

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

    return jQuery.ajax({
        url: '/translation/' + locale + '/' + domain
    });
};

var Translation = React.createClass({
    getInitialState: function() {
        return {
            label:  this.props.children,
            domain: this.props.domain,
            locale: this.props.locale
        };
    },
    componentDidMount: function() {

        var label = this.state.label;
        var self = this;

        translate(this.props.children, this.props.domain, this.props.locale)
            .complete(function(result) {
                if (undefined !== result.responseJSON
                    && undefined !== result.responseJSON[label]
                ) {
                    self.setState({label: result.responseJSON[label]});
                }
            });
    },
    render: function() {

        return (
            <span>{this.state.label}</span>
        )
    }
});

module.exports = Translation;
