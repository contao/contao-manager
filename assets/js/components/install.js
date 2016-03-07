'use strict';

const React         = require('react');
const Translation   = require('./translation.js');
const Widget        = require('./widget.js');

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

    handleInstall: function(e) {
        e.preventDefault();
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

                <button type="submit" onClick={this.handleInstall}>Install</button>
            </form>
        );
    }
});

module.exports = InstallComponent;


