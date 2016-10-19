import React    from 'react';
import isEqual  from 'lodash/isEqual';

class ErrorMessage extends React.Component {

    render() {
        return (
            <p className="error">{this.props.message}</p>
        );
    }
}

class Widget extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            value: this.props.value ? this.props.value : ''
        };

        this.handleChange = this.handleChange.bind(this);
    }

    handleChange(e) {
        this.setState({
            value: e.target.value
        });

        if (undefined !== this.props.onChange){
            this.props.onChange.call(this, e.target.value, this.props);
        }
    }

    render() {

        var errorMessage = '',
            cssClasses = ['widget'];

        if (undefined !== this.props.error && '' !== this.props.error) {
            errorMessage = <ErrorMessage message={this.props.error} />;
            cssClasses.push('invalid');
        } else if ('' !== this.state.value) {
            cssClasses.push('valid');
        }

        cssClasses = cssClasses.join(' ');

        return (
            <div className={cssClasses}>
                {errorMessage}
                <label htmlFor={'ctrl_' + this.props.name}>{this.props.label}</label>
                <input type={this.props.type} id={'ctrl_' + this.props.name} value={this.state.value} name={this.props.name} placeholder={this.props.placeholder} disabled={this.props.disabled} onChange={this.handleChange} />
            </div>
        );
    }
}

export default Widget;
