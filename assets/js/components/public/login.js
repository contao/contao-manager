'use strict';

const React         = require('react');
const jQuery        = require('jquery');
const Trappings     = require('./trappings.js');
const Translation   = require('../translation.js');
const TextWidget    = require('../widgets/text.js');
const request       = require('../helpers/request.js');


var InstallComponent = React.createClass({
    getInitialState: function() {
        return {
            isLoggingIn: false,
            isLoggedIn: false
        }
    },

    componentDidMount: function() {

        if ('' !== request.getUsername() && '' !== request.getToken()) {
            this.setState({isLoggedIn: true});
        }
    },

    handleLogin: function(e) {
        e.preventDefault();
        this.setState({isInstalling: true});

        var self = this;
        var form = jQuery('#login-form');
        var username = form.find('input[name="username"]').first().val();
        var password = form.find('input[name="password"]').first().val();

        this.login(username, password)
            .then(function() {
                self.setState({isLoggedIn: true});
            })
    },

    login: function(username, password) {
        return new Promise(function (resolve, reject) {

            var authPayload = {
                username: username,
                password: password
            };

            request.createRequest('/api/v1/auth', {
                method: 'POST',
                data: JSON.stringify(authPayload),
                dataType: 'json'
            }).success(function (response) {
                if ('OK' === response.status) {
                    // Store the JWT
                    request.setToken(response.token);
                    request.setUsername(username);

                    resolve(response);
                } else {
                    reject(response);
                }
            }).fail(function (err) {
                reject(err);
            });
        });
    },

    render: function() {

        var username = request.getUsername();
        var disabled = this.state.isLoggingIn || this.state.isLoggedIn;

        if (this.state.isLoggedIn) {
            var translationPlaceholders = { username: request.getUsername() };

            return (
                <Trappings sectionClass="login">
                    <h1><Translation placeholders={translationPlaceholders}>You are logged in as %username%.</Translation></h1>
                </Trappings>
            );
        } else {
            return (
                <Trappings sectionClass="login">
                    <h1>Sign In</h1>
                    <p>Login to manage your installation.</p>

                    <form id="login-form" action="#" method="post">
                        <TextWidget type="text" name="username" label="Username"
                                    placeholder="Username"/>
                        <TextWidget type="password" name="password"
                                    label="Password" placeholder="Password"/>


                        {/* @todo Implement a forgot password functionality? */}
                        {/* <a href="">Forgot your password?</a> */}

                        <button disabled={disabled} type="submit"
                                onClick={this.handleLogin}>Sign in
                        </button>
                    </form>
                </Trappings>
            );
        }
    }
});

module.exports = InstallComponent;
