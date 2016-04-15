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

    getInitialState: function() {
        return {
            empty: true
        };
    },

    handleChange: function(event) {
        this.setState({
            empty: '' === event.target.value
        });

        if (undefined !== this.props.onChange){
            this.props.onChange.call(this, this.props, event);
        }
    },

    render: function() {

        var errorMessage = '',
            cssClasses = ['widget'];

        if (undefined !== this.props.error && '' !== this.props.error) {
            errorMessage = <ErrorMessage message={this.props.error} />;
            cssClasses.push('invalid');
        } else if (!this.state.empty) {
            cssClasses.push('valid');
        }

        cssClasses = cssClasses.join(' ');
        
        return (
            <div className={cssClasses}>
                {errorMessage}
                <label htmlFor={'ctrl_' + this.props.name}>{this.props.label}</label>
                <input type={this.props.type} id={'ctrl_' + this.props.name} name={this.props.name} placeholder={this.props.placeholder} onChange={this.handleChange} />
            </div>
        );
    }
});

module.exports = Widget;
