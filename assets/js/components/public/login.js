'use strict';

const React         = require('react');
const Trappings     = require('./trappings.js');
const Translation   = require('../translation.js');
const TextWidget    = require('../widgets/text.js');
const request       = require('../helpers/request.js');
const routing       = require('../helpers/routing.js');


var InstallComponent = React.createClass({
    getInitialState: function() {
        return {
            isLoggingIn: false,
            credentialsIncorrect: false
        }
    },

    handleLogin: function(e) {
        e.preventDefault();
        this.setState({isInstalling: true});

        var self = this;
        var form = document.getElementById('login-form');
        var username = form.querySelectorAll('input[name="username"]')[0].value
        var password = form.querySelectorAll('input[name="password"]')[0].value

        this.login(username, password)
            .then(function() {
                routing.redirect('packages');
            })
            .catch(function() {
                self.setState({credentialsIncorrect: true});
            });
    },

    login: function(username, password) {
        return new Promise(function (resolve, reject) {

            var authPayload = {
                username: username,
                password: password
            };

            request.createRequest('/api/v1/auth', {
                    method: 'POST',
                    data: JSON.stringify(authPayload)
                })
                .then(function (response) {
                    if ('OK' === response.status) {
                        // Store the JWT
                        request.setToken(response.token);

                        resolve(response);
                    } else {
                        reject(new Error(response));
                    }
                })
                .catch(function (err) {
                    reject(new Error(err));
                });
        });
    },

    render: function() {

        var errorMsg = this.state.credentialsIncorrect ? <Translation domain="login">Your credentials are incorrect!</Translation> : '';

    return (
            <Trappings sectionClass="login">
                <h1><Translation domain="login">Sign In</Translation></h1>
                <p><Translation domain="login">Login to manage your installation.</Translation></p>

                <form id="login-form" action="#" method="post">
                    <TextWidget type="text" name="username" label="Username"
                                placeholder="Username" error={errorMsg}/>
                    <TextWidget type="password" name="password"
                                label="Password" placeholder="Password" error={errorMsg}/>


                    {/* @todo Implement a forgot password functionality? */}
                    {/* <a href="">Forgot your password?</a> */}

                    <button disabled={this.state.isLoggingIn} type="submit"
                            onClick={this.handleLogin}><Translation domain="login">Sign In</Translation>
                    </button>
                </form>
            </Trappings>
        );
    }
});

module.exports = InstallComponent;
