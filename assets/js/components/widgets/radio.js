'use strict';

const React         = require('react');
const isEqual       = require('lodash/isEqual');

var Option = React.createClass({

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    render: function() {
        return (
            <span>
                <input
                    type="radio"
                    name={this.props.name}
                    id={this.props.id}
                    value={this.props.value}
                    checked={this.props.selected}
                    onChange={this.props.onChange}
                />
                <label htmlFor={this.props.id}>{this.props.label}</label>
            </span>
        )
    }
});

var Widget = React.createClass({

    getInitialState: function() {
        return {
            selected: this.props.selected
        };
    },

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    handleChange: function(e) {
        this.setState({
            selected: e.target.value
        });

        if (undefined !== this.props.onChange){
            this.props.onChange.call(this, e, this.props);
        }
    },

    render: function() {

        var name = this.props.name;
        var self = this;

        var options = [];
        this.props.options.map(function(option, index) {
            options.push(<Option
                name={name}
                key={name + '_' + index}
                id={name + '_' + index}
                value={option.value}
                label={option.label}
                selected={option.value === self.state.selected}
                onChange={self.handleChange}
            />);
        });

        return (
            <fieldset>
                <legend>{this.props.label}</legend>
                {options}
            </fieldset>
        )
    }
});

module.exports = Widget;
