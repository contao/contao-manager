'use strict';

const React      = require('react');
const Trappings  = require('./trappings.js');
const Codemirror = require('react-codemirror');
const request    = require('./../helpers/request.js');
const _          = require('lodash');

var FileComponent = React.createClass({
    getInitialState: function() {
        return {
            code: '',
            status: 'OK',
            error: {
                line: 0,
                msg: ''
            }
        };
    },

    componentDidMount: function() {
        this.loadInitial(this.props.apiEndpoint);
    },

    componentWillReceiveProps: function(nextProps) {
        this.loadInitial(nextProps.apiEndpoint);
    },

    loadInitial: function(endPoint) {
        var self = this;
        request.createRequest(endPoint, {
            method: 'GET'
        }).success(function (response) {

            self.setState({code: response});

        }).fail(function (err) {
            // @todo
        });
    },

    updateContent: function(content) {
        var self = this;
        self.setState({code: content});

        request.createRequest(this.props.apiEndpoint, {
            method: 'PUT',
            data: content
        }).success(function (response) {

            var newState = {
                status: response.status
            };

            if (undefined !== response.error) {
                newState['error'] = response.error
            } else {
                newState['error'] = {
                    msg: '',
                    line: 0
                }
            }
            self.setState(newState);

        }).fail(function (err) {
            // @todo
        });
    },

    render: function() {

        var options = {
            lineNumbers: true,
            autofocus: true,
            dragDrop: false
        };
        options = _.assign(options, this.props.options);
        return (
            <Trappings>

            <p>Your file status: {this.state.status}</p>
            <p>Error message: {this.state.error.msg} on line {this.state.error.line}</p>

            <Codemirror value={this.state.code} onChange={this.updateContent} options={options} />

            </Trappings>
        );
    }
});

module.exports = FileComponent;
