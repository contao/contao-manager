import React        from 'react';
import Promise      from 'bluebird';
import Trappings    from '../trappings/boxed';
import Loader       from '../fragments/loader';
import Translation  from '../translation';
import TextWidget   from '../widgets/text';
import * as taskmanager from '../../helpers/taskmanager';
import { getState, getLoggedIn } from '../../helpers/tenside-state';
import { createRequest, setRequestToken } from '../../helpers/request';
import eventhandler from '../../helpers/eventhandler';

class InstallComponent extends React.Component {

    constructor(props) {
        super(props);

        this.tensideStatePromise = null;

        this.state = {
            constraintErrorMessage: '',
            passwordsErrorMessage: '',
            installing: false,
            isLoggedIn: null,
            username: '',
            password: '',
            passwordConfirm: '',
            version: ''
        };

        this.handleVersionChange = this.handleVersionChange.bind(this);
        this.handleInstall = this.handleInstall.bind(this);
        this.handleUsernameChange = this.handleUsernameChange.bind(this);
        this.handlePasswordChange = this.handlePasswordChange.bind(this);
    }

    componentDidMount() {
        var self = this;
        this.tensideStatePromise = getLoggedIn()
            .then(function(result) {
                if (true === result.user_loggedIn) {
                    self.setState({
                        isLoggedIn: true,
                        username: result.username
                    });
                } else {
                    self.setState({isLoggedIn: false});
                }
            });

        // Try to redirect to packages when the popup is closed as the
        // last running task might have been the install task.
        eventhandler.on('hideTaskPopup', function() {
            self.context.routing.redirect('packages');
        });
    }

    componentWillUnmount() {
        this.tensideStatePromise.cancel();
    }

    handleVersionChange(version) {
        var self  = this,
            state = {version: version};

        if ('' === value) {
            state['constraintErrorMessage'] = '';
            this.setState(state);
            return;
        }

        createRequest('/api/v1/constraint', {
            method: 'POST',
            json: {constraint: version}
        }).then(function(response) {
             if ('OK' !== response.body.status) {
                 state['constraintErrorMessage'] = <Translation>You have to enter a valid Composer version constraint!</Translation>;
            } else {
                 state['constraintErrorMessage'] = '';
            }

            self.setState(state);

            return null;

        }).catch(function() {
            // @todo: what if request failed?
        });
    }

    handleInstall(e) {
        e.preventDefault();
        let self = this;

        this.setState({installing: true});

        getState()
            .then(function(state) {
                // Configure tenside if not already configured
                if (false === state.tenside_configured) {
                    return self.configure(self.state.username, self.state.password)
                        .then(function() {
                            state.tenside_configured = true;
                            return state;
                        });
                }

                return state;
            })
            .then(function(state) {

                // Create project if not already created
                if (false === state.project_created) {
                    return self.createProject(self.state.version)
                    /*.then(function () {
                        getState()
                        .then(function (state) {
                            if (false === state.project_created || false === state.project_installed) {
                                throw new Error('Project could not be installed');
                            }

                            return state;
                        });
                    })*/;
                }
            })
    }

    configure(username, password) {
        let configurePayload = {
            credentials: {
                username: username,
                password: password
            }
        };

        return createRequest('/api/v1/install/autoconfig', {
            method: 'GET'
        })
        .then(function (response) {
            return response.body;
        })
        .then(function(autoconfig) {

            let config = { configuration: autoconfig};
            configurePayload = Object.assign(configurePayload, config);

            return createRequest('/api/v1/install/configure', {
                method: 'POST',
                json: configurePayload
            })
            .then(function (response) {
                if ('OK' === response.body.status) {
                    // Store the JWT
                    setRequestToken(response.body.token);
                    return;
                }

                throw new Error(response);
            });
        });
    }

    createProject(version) {

        let createProjectPayload = {
            project: {
                name: 'contao/managed-edition'
            }
        };

        if ('' !== version) {
            createProjectPayload.version = version;
        }

        return createRequest('/api/v1/install/create-project', {
            method: 'POST',
            json: createProjectPayload
        })
        .then(function (response) {
            if ('OK' !== response.body.status) {
                throw new Error(response);
            }

            return taskmanager.runNextTask();
        });
    }

    handleUsernameChange(username) {
        this.setState({username: username});
    }

    handlePasswordChange(value, props) {
        var minPasswordLenth = 8,
            state = {
                password: this.state.password,
                passwordConfirm: this.state.passwordConfirm,
                passwordsErrorMessage: ''
            };

        if (props.name == 'password') {
            state.password = String(value);
        } else {
            state.passwordConfirm = String(value);
        }

        if (state.password.length < minPasswordLenth
            || (
                ('' !== state.password || '' !== state.passwordConfirm)
                && state.password !== state.passwordConfirm
            )
        ) {
            state.passwordsErrorMessage = <Translation domain="install" placeholders={{min: minPasswordLenth}}>
                    Passwords do not match or are shorter than %min% characters!
                </Translation>;
        }

        this.setState(state);
    }

    getUsernamePart() {
        if (null === this.state.isLoggedIn) {
            return '';
        }

        if (this.state.isLoggedIn) {
            return <fieldset>
                <legend><Translation domain="install">User Account</Translation></legend>
                        <p><Translation placeholders={{ username: this.state.username }}>You are logged in as %username%.</Translation></p>
                   </fieldset>;

        } else {
            return <fieldset>
                        <legend><Translation domain="install">User Account</Translation></legend>
                        <p><Translation domain="install">Create a user account to manage your installation.</Translation></p>
                        <TextWidget type="text" name="username" label="Username" onChange={this.handleUsernameChange} />
                        <TextWidget type="password" name="password" label="Password" onChange={this.handlePasswordChange} error={this.state.passwordsErrorMessage} />
                        <TextWidget type="password" name="password_confirm" label="Retype Password" onChange={this.handlePasswordChange} error={this.state.passwordsErrorMessage} />
                   </fieldset>;
        }
    }

    render() {

        var disableButton = this.state.passwordsErrorMessage || this.state.installing;

        return (
            <Trappings wide={true} mainClass="install">
                <header>
                    <img src="web-assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
                    <p className="welcome"><strong>Welcome</strong> to Contao Manager v1.0</p>
                    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem.</p>
                </header>

                <section>
                    <form action="#" method="post">

                        {this.getUsernamePart()}

                        <fieldset>
                            <legend>Contao Installation</legend>
                            <p>Enter a version to install or leave blank for the latest version.</p>

                            <TextWidget type="text" name="version" label="Version" placeholder="latest" onChange={this.handleVersionChange} error={this.state.constraintErrorMessage} />

                        </fieldset>

                        <button disabled={disableButton} type="submit" onClick={this.handleInstall}>
                            <Translation domain="login">Install</Translation>
                            <Loader/>
                        </button>
                    </form>
                </section>
            </Trappings>
        );
    }
}

InstallComponent.contextTypes = {
    routing: React.PropTypes.object
};

export default InstallComponent;
