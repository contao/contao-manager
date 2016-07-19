'use strict';

const React         = require('react');
const Promise       = require('bluebird');
const Trappings     = require('../trappings/main.js');
const TensideState  = require('../../helpers/tenside-state.js');
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
                var sortRef = {
                    FAIL: 3,
                    WARNING: 2,
                    SUCCESS: 1
                };
                result.sort(function(a, b) { return sortRef[b.state] - sortRef[a.state]; });

                self.setState({data: result});

                return null;
            });
    },

    componentWillUnmount: function() {
        this.statePromise.cancel();
    },


    render: function() {
        var tests = [];

        forIn(this.state.data, function(data, key) {
            tests.push(<TestComponent key={key} data={data} />);
        });

        return (
            <Trappings>{tests}</Trappings>
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
            <div className={'test clearfix ' + data.state.toLowerCase() + ' ' + data.name}>
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0px" y="0px" viewBox="0 0 79.536 79.536" xmlSpace="preserve"><g><path d="M39.769,0C17.8,0,0,17.8,0,39.768c0,21.965,17.8,39.768,39.769,39.768 c21.965,0,39.768-17.803,39.768-39.768C79.536,17.8,61.733,0,39.769,0z M34.142,58.513L15.397,39.768l7.498-7.498l11.247,11.247 l22.497-22.493l7.498,7.498L34.142,58.513z" /></g></svg>
                <div className="message">{data.message}</div>
                <div className="explain">{data.explain}</div>
            </div>
        );
    }
});

module.exports = SelfTestComponent;
