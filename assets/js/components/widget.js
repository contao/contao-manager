'use strict';

const React = require('react');

var ErrorMessage = React.createClass({
    render: function() {
        return (
            <p className="error">{this.props.message}</p>
        );
    }
});

var Widget = React.createClass({
    render: function() {

        var onChange = null,
            errorMessage = '';

        if (undefined !== this.props.onChange){
            onChange = this.props.onChange.bind(null, this.props);
        }

        if (undefined !== this.props.error && '' !== this.props.error) {
            errorMessage = <ErrorMessage message={this.props.error} />;
        }
        
        return (
            <div className={'widget' + (this.props.error ? ' error' : '')}>
                {errorMessage}
                <label htmlFor={this.props.name}>{this.props.label}</label>
                <input type={this.props.type} id={'ctrl_' + this.props.name} name={this.props.name} placeholder={this.props.placeholder} onChange={onChange} />
            </div>
        );
    }
});

module.exports = Widget;
