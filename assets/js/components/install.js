'use strict';

const React         = require('react');
const jQuery        = require('jquery');
const Translation   = require('./translation.js');
const Widget        = require('./widget.js');
const eventhandler  = require('./eventhandler.js');


var InstallComponent = React.createClass({
    password: '',
    passwordConfirm: '',
    getInitialState: function() {
        return {
            passwordsErrorMessage: '',
            installing: false
        };
    },

    handlePasswordCompare: function(props, e) {
        if (props.name == 'password') {
            this.password = e.target.value;
        } else {
            this.passwordConfirm = e.target.value;
        }

        if (this.password === this.passwordConfirm) {
            this.setState({passwordsErrorMessage: ''});
        } else {
            this.setState({passwordsErrorMessage: <Translation domain="install">Passwords do not match!</Translation>});
        }
    },

    handleInstall: function(e) {
        e.preventDefault();
        var form = jQuery('#install-form');
        var configurePayload = {
            credentials: {
                username: form.find('input[name="username"]').first().val(),
                password: form.find('input[name="password"]').first().val()
            }
        };
        var createProjectPayload = {
            project: {
                name:    'contao/contao'
            }
        };

        var versionField = form.find('input[name="version"]').first();

        if (versionField.val() != versionField.attr('placeholder')) {
            createProjectPayload.version = versionField.val();
        }

        eventhandler.emit('displayTaskPopup', {
            'h1': 'hi'
        });

        this.setState({installing: true});

        jQuery.ajax('/api/v1/install/configure', {
            method: 'POST',
            data: JSON.stringify(configurePayload),
            dataType: 'json'
        }).complete(function(response) {
            if ('OK' !== response.status) {
                // @todo: what if configure failed?
            }

            jQuery.ajax('/api/v1/install/create-project', {
                method: 'POST',
                data: JSON.stringify(createProjectPayload),
                dataType: 'json'
            }).complete(function(response) {
                if ('OK' !== response.status) {
                    // @todo: what if create-project failed?
                }
            }).fail(function() {
                // @todo: what if create-project failed?
            });

        }).fail(function() {
            // @todo: what if configure failed?
        });
    },

    render: function() {

        return (
            <form id="install-form" action="#" method="post">
                <fieldset>
                    <legend><Translation domain="install">User Account</Translation></legend>
                    <p>Create a user account to manage your installation.</p>

                    <Widget type="text" name="username" label="Username"></Widget>
                    <Widget type="password" name="password" label="Password" onChange={this.handlePasswordCompare} error={this.state.passwordsErrorMessage}></Widget>
                    <Widget type="password" name="password_confirm" label="Retype Password" onChange={this.handlePasswordCompare} error={this.state.passwordsErrorMessage}></Widget>

                </fieldset>

                <fieldset>
                    <legend>Contao Installation</legend>
                    <p>Enter a version to install or leave blank for the latest version.</p>

                    <Widget type="text" name="version" label="Version" placeholder="latest"></Widget>

                </fieldset>

                <button disabled={this.state.installing} type="submit" onClick={this.handleInstall}>Install</button>
            </form>
        );
    }
});

module.exports = InstallComponent;


