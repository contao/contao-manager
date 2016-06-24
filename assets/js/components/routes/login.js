'use strict';

const React         = require('react');
const Trappings     = require('../trappings/boxed.js');
const Hint          = require('../fragments/hint.js');
const Loader        = require('../fragments/loader');
const Translation   = require('../translation.js');
const TextWidget    = require('../widgets/text.js');
const translate     = require('../../helpers/translate.js');
const request       = require('../../helpers/request.js');
const isEqual       = require('lodash/isEqual');

var LoginComponent = React.createClass({

    componentIsMounted: false,
    contextTypes: {
        routing: React.PropTypes.object
    },

    getInitialState: function() {
        return {
            isLoggingIn: false,
            credentialsIncorrect: false,
            username: '',
            password: '',
            translationData: {}
        }
    },

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    componentDidMount: function() {
        var self = this;

        this.componentIsMounted = true;

        translate.fetchData('login', this.context.routing.getLanguage())
            .then(function(response) {
                if (self.componentIsMounted) {
                    self.setState({translationData: response.body});
                }
            });
    },

    componentWillUnmount: function() {
        this.componentIsMounted = false;
    },

    handleUsernameChange: function(e) {
        this.setState({username: e.target.value, isLoggingIn: false, credentialsIncorrect: false});
    },

    handlePasswordChange: function(e) {
        this.setState({password: e.target.value, isLoggingIn: false, credentialsIncorrect: false});
    },

    handleLogin: function(e) {
        e.preventDefault();
        this.setState({isLoggingIn: true, credentialsIncorrect: false});

        var self = this;
        var form = document.getElementById('login-form');

        return request.createRequest('/api/v1/auth', {
                method: 'POST',
                json: {
                    username: this.state.username,
                    password: this.state.password
                }
            })
            .then(function (response) {
                if ('OK' === response.body.status) {
                    // Store the JWT
                    request.setToken(response.body.token);
                    self.context.routing.redirect('packages');
                } else {
                    self.setState({isLoggingIn: false, credentialsIncorrect: true});
                }
            })
            .catch(function() {
                // @todo handle failed request
            });
    },

    login: function(username, password) {

        var authPayload = {
            username: username,
            password: password
        };

        return request.createRequest('/api/v1/auth', {
                method: 'POST',
                json: authPayload
            })
            .then(function (response) {
                if ('OK' === response.body.status) {
                    // Store the JWT
                    request.setToken(response.body.token);
                }
            });
    },

    render: function() {

        var errorMsg = this.state.credentialsIncorrect ? <Hint type="warning"><Translation domain="login">Your credentials are incorrect!</Translation></Hint> : '';

        return (
            <Trappings sectionClass="login">
                <header>
                    <img src="/web-assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
                    <p className="welcome"><strong>Welcome</strong> to Contao Manager v1.0</p>
                </header>

                <section className="login">
                    <h1><Translation domain="login">Sign In</Translation></h1>
                    <p><Translation domain="login">Login to manage your installation.</Translation></p>

                    {errorMsg}

                    <form id="login-form" action="#" method="post">
                        <TextWidget type="text" name="username"
                                    label={translate.getTranslationForKey('Username', this.state.translationData)}
                                    placeholder={translate.getTranslationForKey('Username', this.state.translationData)}
                                    onChange={this.handleUsernameChange}
                                    disabled={this.state.isLoggingIn}
                        />
                        <TextWidget type="password" name="password"
                                    label={translate.getTranslationForKey('Password', this.state.translationData)}
                                    placeholder={translate.getTranslationForKey('Password', this.state.translationData)}
                                    disabled={this.state.isLoggingIn}
                                    onChange={this.handlePasswordChange}
                        />


                        <a href="https://www.google.com" target="_blank">Forgot your password?</a>

                        <button disabled={this.state.isLoggingIn} type="submit" onClick={this.handleLogin}>
                            <Translation domain="login">Sign In</Translation>
                            <Loader/>
                        </button>
                    </form>
                </section>
            </Trappings>
        );
    }
});

module.exports = LoginComponent;
