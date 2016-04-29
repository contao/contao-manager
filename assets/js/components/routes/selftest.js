'use strict';

const React         = require('react');
const Promise       = require('bluebird');
const TensideState  = require('../helpers/tenside-state.js');
const isEqual       = require('lodash/isEqual');
const forIn         = require('lodash/forIn');

var SelfTestComponent = React.createClass({

    statePromise: Promise.resolve(),

    getInitialState: function() {
        return {
            data: []
        };
    },

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    componentDidMount: function() {
        var self = this;
        this.statePromise = TensideState.getSelfTest()
            .then(function(result) {
                self.setState({data: result});
            });
    },

    componentWillUnmount: function() {
        this.statePromise.cancel();
    },


    render: function() {

        var tests = [];
        var sortRef = {
            ERROR: 3,
            WARNING: 2,
            SUCCESS: 1
        };

        this.state.data.sort(function(a, b) {
            if (sortRef[a.state] < sortRef[b.state]) {
                return 1;
            }
            if (sortRef[a.state] > sortRef[b.state]) {
                return -1;
            }

            return 0;
        });

        forIn(this.state.data, function(data, key) {
            tests.push(<TestComponent key={key} data={data} />);
        });

        return (
            <div className="tests">
                {tests}
            </div>
        );
    }
});


var TestComponent = React.createClass({

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    render: function() {

        var data = this.props.data;

        return (
            <div className={'test ' + data.state.toLowerCase()}>
                <div className="name">{data.name}</div>
                <div className="message">{data.message}</div>
                <div className="explain">{data.explain}</div>
            </div>
        );
    }
});

module.exports = SelfTestComponent;
