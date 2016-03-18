'use strict';

const React         = require('react');
const jQuery        = require('jquery');
const Translation   = require('./translation.js');
const TextWidget    = require('./widgets/text.js');
const eventhandler  = require('./eventhandler.js');


var InstallComponent = React.createClass({
    password: '',
    passwordConfirm: '',
    getInitialState: function() {
        return {
            passwordsErrorMessage: '',
            constraintErrorMessage: '',
            installing: false
        };
    },

    validateConstraint: function(props, e) {

        var self  = this,
            value = e.target.value;

        if ('' === value) {
            self.setState({constraintErrorMessage: ''});
            return;
        }

        jQuery.ajax('/api/v1/constraint', {
            method: 'POST',
            data: JSON.stringify({constraint: value}),
            dataType: 'json'
        }).success(function(response) {
             if ('OK' !== response.status) {
                self.setState({constraintErrorMessage: <Translation>You have to enter a valid Composer version constraint!</Translation>});
            } else {
                self.setState({constraintErrorMessage: ''});
            }

        }).fail(function() {
            // @todo: what if request failed?
        });
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
                name:    'contao/standard-edition'
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
        }).success(function(response) {
            if ('ok' !== response.status) {
                // @todo: what if configure failed?
            }

            jQuery.ajax('/api/v1/install/create-project', {
                method: 'POST',
                data: JSON.stringify(createProjectPayload),
                dataType: 'json'
            }).success(function(response) {
                if ('ok' !== response.status) {
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

        var hasErrors = this.state.passwordsErrorMessage !== '';
        var disableButton = hasErrors || this.state.installing;

        return (
            <form id="install-form" action="#" method="post">
                <fieldset>
                    <legend><Translation domain="install">User Account</Translation></legend>
                    <p>Create a user account to manage your installation.</p>

                    <TextWidget type="text" name="username" label="Username"></TextWidget>
                    <TextWidget type="password" name="password" label="Password" onChange={this.handlePasswordCompare} error={this.state.passwordsErrorMessage}></TextWidget>
                    <TextWidget type="password" name="password_confirm" label="Retype Password" onChange={this.handlePasswordCompare} error={this.state.passwordsErrorMessage}></TextWidget>

                </fieldset>

                <fieldset>
                    <legend>Contao Installation</legend>
                    <p>Enter a version to install or leave blank for the latest version.</p>

                    <TextWidget type="text" name="version" label="Version" placeholder="latest" onChange={this.validateConstraint} error={this.state.constraintErrorMessage}></TextWidget>

                </fieldset>

                <button disabled={disableButton} type="submit" onClick={this.handleInstall}>Install</button>
            </form>
        );
    }
});

module.exports = InstallComponent;


