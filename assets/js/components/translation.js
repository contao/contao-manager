'use strict';

const React         = require('react');
const translate     = require('./helpers/translate.js');
const isEqual       = require('lodash/isEqual');


var Translation = React.createClass({

    fetchDataPromise: null,

    getInitialState: function() {
        return {
            data: {}
        };
    },

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    componentDidMount: function() {

        var self = this;

        this.fetchDataPromise = translate.fetchData(this.props.domain, this.props.locale)
            .then(function(data) {
                if (!self.fetchDataPromise.isCancelled()) {
                    self.setState({data: data});
                }

                return data;
            });
    },

    componentWillUnmount: function() {
        this.fetchDataPromise.cancel();
    },

    render: function() {

        var label = translate.getTranslationForKey(this.props.children, this.state.data, this.props.placeholders);

        return (
            <span>{label}</span>
        )
    }
});

module.exports = Translation;
