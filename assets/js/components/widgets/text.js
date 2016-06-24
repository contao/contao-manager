'use strict';

const React         = require('react');
const isEqual       = require('lodash/isEqual');

var ErrorMessage = React.createClass({

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

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

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    handleChange: function(e) {
        this.setState({
            empty: '' === e.target.value
        });

        if (undefined !== this.props.onChange){
            this.props.onChange.call(this, e, this.props);
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
                <input type={this.props.type} id={'ctrl_' + this.props.name} name={this.props.name} placeholder={this.props.placeholder} disabled={this.props.disabled} onChange={this.handleChange} />
            </div>
        );
    }
});

module.exports = Widget;
