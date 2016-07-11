'use strict';

const React         = require('react');
const Promise       = require('bluebird');
const Trappings     = require('../trappings/boxed.js');
const Translation   = require('../translation.js');
const TextWidget    = require('../widgets/text.js');
const TensideState  = require('../../helpers/tenside-state.js');
const taskmanager   = require('../../helpers/taskmanager.js');
const request       = require('../../helpers/request.js');
const eventhandler  = require('../../helpers/eventhandler.js');
const isEqual       = require('lodash/isEqual');

var InstallComponent = React.createClass({

    tensideStatePromise: null,
    contextTypes: {
        routing: React.PropTypes.object
    },

    getInitialState: function() {
        return {
            constraintErrorMessage: '',
            passwordsErrorMessage: '',
            installing: false,
            isLoggedIn: null,
            username: '',
            password: '',
            passwordConfirm: '',
            version: ''
        };
    },

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    componentDidMount: function() {
        var self = this;
        this.tensideStatePromise = TensideState.getLoggedIn()
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
    },

    componentWillUnmount: function() {
        this.tensideStatePromise.cancel();
    },

    handleVersionChange: function(version) {
        var self  = this,
            state = {version: version};

        if ('' === value) {
            state['constraintErrorMessage'] = '';
            this.setState(state);
            return;
        }

        request.createRequest('/api/v1/constraint', {
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
    },

    handleInstall: function(e) {
        e.preventDefault();
        var self = this;

        this.setState({installing: true});

        TensideState.getState()
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
                    var createProjectPayload = {
                        project: {
                            name: 'contao/standard-edition'
                        }
                    };

                    if ('' !== self.state.version) {
                        createProjectPayload.version = self.state.version;
                    }

                    return self.createProject(createProjectPayload, state);
                }

                return state;
            })
            .then(function(state) {
                // Install project if not already installed
                if (true !== state.project_installed) {

                    // @todo what if the project was created but not installed
                    // and the task manually deleted? running the next task
                    // will fail?

                    return taskmanager.runNextTask();
                }
            })
            .catch(function(err) {
                // @todo: what to do with those general errors
            });
    },

    configure: function(username, password) {
        var configurePayload = {
            credentials: {
                username: username,
                password: password
            }
        };

        // First do an autoconfigure and then merge the data with the user
        return new Promise(function (resolve, reject) {

            return new Promise(function (resolve, reject) {
                request.createRequest('/api/v1/install/autoconfig', {
                    method: 'GET'
                }).then(function (response) {
                    resolve(response.body);
                }).catch(function (err) {
                    reject(new Error(err));
                });
            })
            .then(function(autoconfig) {

                var config = { configuration: autoconfig};
                configurePayload = Object.assign(configurePayload, config);

                request.createRequest('/api/v1/install/configure', {
                    method: 'POST',
                    json: configurePayload
                }).then(function (response) {
                    if ('OK' === response.body.status) {
                        // Store the JWT
                        request.setToken(response.body.token);

                        resolve(response.body);
                    } else {
                        reject(new Error(response));
                    }
                }).catch(function (err) {
                    reject(new Error(err));
                });
            });
        });
    },

    createProject: function(createProjectPayload, state) {

        return new Promise(function (resolve, reject) {

            request.createRequest('/api/v1/install/create-project', {
                method: 'POST',
                json: createProjectPayload
            }).then(function (response) {
                if ('OK' === response.body.status) {
                    // Successfully created, adjust state
                    state.project_created = true;

                    resolve(state);
                } else {
                    reject(new Error(response));
                }
            }).catch(function (err) {
                reject(new Error(err));
            });
        });
    },

    handleUsernameChange: function(username) {
        this.setState({username: username});
    },

    handlePasswordChange: function(value, props) {
        var invalid,
            minPasswordLenth = 8,
            state = {password: this.state.password, passwordConfirm: this.state.passwordConfirm};

        if (props.name == 'password') {
            state['password'] = value;
        } else {
            state['passwordConfirm'] = value;
        }

        invalid = state.password.length < minPasswordLenth
            || (
                ('' !== state.password || '' !== state.passwordConfirm)
                && state.password !== state.passwordConfirm
            );

        if (!invalid) {
            state['passwordsErrorMessage'] = ''
        } else {
            state['passwordsErrorMessage'] = <Translation domain="install" placeholders={{min: minPasswordLenth}}>
                    Passwords do not match or are shorter than %min% characters!
                </Translation>;
        }

        this.setState(state);
    },

    getUsernamePart: function() {
        if (null === this.state.isLoggedIn) {

            return '';
        }

        if (this.state.isLoggedIn) {
            return <fieldset>
                        <legend><Translation placeholders={{ username: this.state.username }}>You are logged in as %username%.</Translation></legend>
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
    },

    render: function() {

        var disableButton = this.state.passwordsErrorMessage || this.state.installing;

        return (
            <Trappings wide={true}>
                <header>
                    <img src="/web-assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
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

                        <button disabled={disableButton} type="submit" onClick={this.handleInstall}>Install</button>
                    </form>
                </section>
            </Trappings>
        );
    }
});

module.exports = InstallComponent;
