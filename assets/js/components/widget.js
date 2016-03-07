'use strict';

const React = require('react');

var Widget = React.createClass({
    render: function() {

        var onChange = null;
        if (undefined !== this.props.onChange){
            onChange = this.props.onChange.bind(null, this.props);
        }

        return (
            <div className={"widget" + (this.props.error ? " error" : "")}>
                <label htmlFor={this.props.name}>{this.props.label}</label>
                <input type={this.props.type} id={"ctrl_" + this.props.name} name={this.props.name} placeholder={this.props.placeholder} onChange={onChange} />
            </div>
        );
    }
});

module.exports = Widget;
