import React       from 'react';
import Trappings   from '../trappings/boxed';
import Hint        from '../fragments/hint';
import Loader      from '../fragments/loader';
import Translation from '../translation';
import TextWidget  from '../widgets/text';
import translate   from '../../helpers/translate';
import request     from '../../helpers/request';
import isEqual     from 'lodash/isEqual';

class LoginComponent extends React.Component {

    constructor(props) {
        super(props);

        this.componentIsMounted = false;

        this.state = {
            isLoggingIn: false,
            credentialsIncorrect: false,
            username: '',
            password: '',
            translationData: {}
        };

        this.handleUsernameChange = this.handleUsernameChange.bind(this);
        this.handlePasswordChange = this.handlePasswordChange.bind(this);
        this.handleLogin = this.handleLogin.bind(this);
    }

    componentDidMount() {
        var self = this;

        this.componentIsMounted = true;

        translate.fetchData('login', this.context.routing.getLanguage())
            .then(function(response) {
                if (self.componentIsMounted) {
                    self.setState({translationData: response.body});
                }
            });
    }

    componentWillUnmount() {
        this.componentIsMounted = false;
    }

    handleUsernameChange(value) {
        this.setState({username: value, isLoggingIn: false, credentialsIncorrect: false});
    }

    handlePasswordChange(value) {
        this.setState({password: value, isLoggingIn: false, credentialsIncorrect: false});
    }

    handleLogin(e) {
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
    }

    login(username, password) {

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
    }

    render() {

        var errorMsg = this.state.credentialsIncorrect ? <Hint type="warning"><Translation domain="login">Your credentials are incorrect!</Translation></Hint> : '';

        return (
            <Trappings mainClass="login">
                <header>
                    <img src="web-assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
                    <p className="welcome"><strong>Welcome</strong> to Contao Manager v1.0</p>
                </header>

                <section>
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
}

LoginComponent.contextTypes = {
    routing: React.PropTypes.object
};

export default LoginComponent;
