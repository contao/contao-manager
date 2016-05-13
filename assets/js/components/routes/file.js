'use strict';

const React         = require('react');
const Trappings     = require('../trappings/main.js');
const Codemirror    = require('react-codemirror');
const request       = require('../../helpers/request.js');
const Translation   = require('../translation.js');
const assign        = require('lodash/assign');
const forEach       = require('lodash/forEach');
const isEqual       = require('lodash/isEqual');

var MessageComponent = React.createClass({

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    render: function() {

        var line = '';
        var msg = this.props.msg;

        if (this.props.line > 0) {
            line = <Translation domain="file" placeholders={{ line: this.props.line }}>Line %line%</Translation>;
            msg = ': ' + msg;
        }

        return (
            <p className={this.props.type}>{line}{msg}</p>
        )
    }
});

var FileComponent = React.createClass({

    getInitialState: function() {
        return {
            code: '',
            status: 'OK',
            errors: [],
            warnings: []
        };
    },

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    componentDidMount: function() {
        this.loadInitial(this.props.apiEndpoint);
    },

    componentWillReceiveProps: function(nextProps) {
        this.loadInitial(nextProps.apiEndpoint);
    },

    loadInitial: function(endPoint) {
        var self = this;
        request.createRequest(endPoint)
            .then(function (response) {

                self.setState({code: response});

            }).catch(function (err) {
                // @todo
            });
    },

    updateContent: function(content) {
        var self = this;
        self.setState({code: content});

        request.createRequest(this.props.apiEndpoint, {
                method: 'PUT',
                data: content
            })
            .then(function (response) {

                var newState = {};
                newState['status'] = response.status;

                if (undefined !== response.warnings) {
                    newState['warnings'] = response.warnings;
                } else {
                    newState['warnings'] = [];
                }

                if (undefined !== response.errors) {
                    newState['errors'] = response.errors;
                } else {
                    newState['errors'] = [];
                }

                self.setState(newState);

            })
            .catch(function (err) {
                // @todo
            });
    },

    render: function() {

        var messages = [];
        var options = {
            lineNumbers: true,
            autofocus: true,
            dragDrop: false
        };
        options = assign(options, this.props.options);

        if ('OK' === this.state.status) {
            var msg = <Translation domain="file">The file is OK!</Translation>;
            messages.push(<MessageComponent key="ok" type="ok" msg={msg}/>);
        }

        if (this.state.warnings.length > 0) {
            forEach(this.state.warnings, function(value, key) {
                messages.push(<MessageComponent key={'warning' + key} type="warning" msg={value.msg} line={value.line} />);
            });
        }

        if (this.state.errors.length > 0) {
            forEach(this.state.errors, function(value, key) {
                messages.push(<MessageComponent key={'error' + key} type="error" msg={value.msg} line={value.line} />);
            });
        }

        return (
            <Trappings>

            <div className="messages">{messages}</div>

            <Codemirror value={this.state.code} onChange={this.updateContent} options={options} />

            </Trappings>
        );
    }
});

module.exports = FileComponent;
