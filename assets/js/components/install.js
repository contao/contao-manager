var React = require('react');
var ReactDOM = require('react-dom');

var Widget = React.createClass({
    render: function() {

        var onChange = null;
        if (undefined !== this.props.onChange){
            onChange = this.props.onChange.bind(null, this.props);
        }

        return (
            <div className={"widget" + (this.props.error ? " error" : "")}>
                <label htmlFor={this.props.name}>{this.props.label}</label>
                <input type={this.props.type} id={"ctrl_" + this.props.name} placeholder={this.props.placeholder} onChange={onChange} />
            </div>
        );
    }
});

var InstallComponent = React.createClass({
    password: '',
    passwordConfirm: '',
    getInitialState: function() {
        return {
            passwordsMatch: true
        };
    },

    handlePasswordCompare: function(props, e) {
        if (props.name == 'password') {
            this.password = e.target.value;
        } else {
            this.passwordConfirm = e.target.value;
        }

        if (this.password === this.passwordConfirm) {
            this.setState({passwordsMatch: true});
        } else {
            this.setState({passwordsMatch: false});
        }
    },

    render: function() {
        return (
            <form action="#" method="post">
                <fieldset>
                    <legend>User Account</legend>
                    <p>Create a user account to manage your installation.</p>

                    <Widget type="text" name="username" label="Username"></Widget>
                    <Widget type="password" name="password" label="Password" onChange={this.handlePasswordCompare} error={!this.state.passwordsMatch}></Widget>
                    <Widget type="password" name="password_confirm" label="Retype Password" onChange={this.handlePasswordCompare} error={!this.state.passwordsMatch}></Widget>

                </fieldset>

                <fieldset>
                    <legend>Contao Installation</legend>
                    <p>Enter a version to install or leave blank for the latest version.</p>

                    <Widget type="text" name="version" label="Version" placeholder="latest"></Widget>

                </fieldset>

                <button type="submit">Install</button>
            </form>
        );
    }
});

ReactDOM.render(
<InstallComponent />,
    document.getElementById('install_component')
);
