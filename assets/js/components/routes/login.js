'use strict';

const React         = require('react');
const Promise       = require('bluebird');
const Trappings     = require('../trappings/boxed.js');
const Translation   = require('../translation.js');
const TextWidget    = require('../widgets/text.js');
const translate     = require('../../helpers/translate.js');
const request       = require('../../helpers/request.js');
const routing       = require('../../helpers/routing.js');
const isEqual       = require('lodash/isEqual');

var LoginComponent = React.createClass({

    loginPromise: Promise.resolve(),
    componentIsMounted: false,

    getInitialState: function() {
        return {
            isLoggingIn: false,
            credentialsIncorrect: false,
            translationData: {}
        }
    },

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    componentDidMount: function() {
        var self = this;

        this.componentIsMounted = true;

        translate.fetchData('login', routing.getLanguage())
            .then(function(data) {
                if (self.componentIsMounted) {
                    self.setState({translationData: data});
                }

                return data;
            });
    },

    componentWillUnmount: function() {
        this.componentIsMounted = false;
        this.loginPromise.cancel();
    },

    handleLogin: function(e) {
        e.preventDefault();
        this.setState({isInstalling: true});

        var self = this;
        var form = document.getElementById('login-form');
        var username = form.querySelectorAll('input[name="username"]')[0].value;
        var password = form.querySelectorAll('input[name="password"]')[0].value;

        this.loginPromise = this.login(username, password)
            .then(function() {
                routing.redirect('packages');
            })
            .catch(function() {
                if (!self.loginPromise.isCancelled()) {
                    self.setState({credentialsIncorrect: true});
                }
            });
    },

    login: function(username, password) {

        var authPayload = {
            username: username,
            password: password
        };

        return request.createRequest('/api/v1/auth', {
                method: 'POST',
                data: JSON.stringify(authPayload)
            })
            .then(function (response) {
                if ('OK' === response.status) {
                    // Store the JWT
                    request.setToken(response.token);
                }
            });
    },

    render: function() {

        var errorMsg = this.state.credentialsIncorrect ? <Translation domain="login">Your credentials are incorrect!</Translation> : '';

    return (
            <Trappings sectionClass="login">
                <header>
                    <img src="/web-assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
                    <p className="welcome"><strong>Welcome</strong> to Contao Manager v1.0</p>
                </header>

                <section className="login">
                    <h1><Translation domain="login">Sign In</Translation></h1>
                    <p><Translation domain="login">Login to manage your installation.</Translation></p>

                    <form id="login-form" action="#" method="post">
                        <TextWidget type="text" name="username" label={translate.getTranslationForKey('Username', this.state.translationData)}
                                    placeholder={translate.getTranslationForKey('Username', this.state.translationData)} error={errorMsg}/>
                        <TextWidget type="password" name="password"
                                    label={translate.getTranslationForKey('Password', this.state.translationData)} placeholder={translate.getTranslationForKey('Password', this.state.translationData)} error={errorMsg}/>


                        {/* @todo Implement a forgot password functionality? */}
                        {/* <a href="">Forgot your password?</a> */}

                        <button disabled={this.state.isLoggingIn} type="submit"
                                onClick={this.handleLogin}><Translation domain="login">Sign In</Translation>
                        </button>
                    </form>
                </section>
            </Trappings>
        );
    }
});

module.exports = LoginComponent;
