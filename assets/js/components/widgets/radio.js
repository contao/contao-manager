import React    from 'react';
import isEqual  from 'lodash/isEqual';

function Option(props) {
    return (
        <span>
            <input
                type="radio"
                name={props.name}
                id={props.id}
                value={props.value}
                checked={props.selected}
                onChange={props.onChange}
            />
            <label htmlFor={props.id}>{props.label}</label>
        </span>
    )
}

class Widget extends React.Component {

    constructor(props) {
        super(props);

        this.state ={
            selected: this.props.selected
        };

        this.handleChange = this.handleChange.bind(this);
    }

    handleChange(e) {
        this.setState({
            selected: e.target.value
        });

        if (undefined !== this.props.onChange){
            this.props.onChange.call(this, e, this.props);
        }
    }

    render() {

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
}

export default Widget;
