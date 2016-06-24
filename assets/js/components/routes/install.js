'use strict';

const React         = require('react');
const Promise       = require('bluebird');
const Trappings     = require('../trappings/boxed.js');
const Translation   = require('../translation.js');
const TextWidget    = require('../widgets/text.js');
const TensideState  = require('../../helpers/tenside-state.js');
const taskmanager   = require('../../helpers/taskmanager.js');
const request       = require('../../helpers/request.js');
const isEqual       = require('lodash/isEqual');

// @todo Handle the componentWillUnmount stuff here (cancelling, unsettled promises)

var InstallComponent = React.createClass({

    tensideStatePromise: null,

    getInitialState: function() {
        return {
            constraintErrorMessage: '',
            hasPasswordErrors: false,
            installing: false,
            isLoggedIn: false,
            username: ''
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
                }
            });
    },

    validateConstraint: function(e) {

        var self  = this,
            value = e.target.value;

        if ('' === value) {
            self.setState({constraintErrorMessage: ''});
            return;
        }

        request.createRequest('/api/v1/constraint', {
            method: 'POST',
            json: {constraint: value}
        }).then(function(response) {
             if ('OK' !== response.body.status) {
                self.setState({constraintErrorMessage: <Translation>You have to enter a valid Composer version constraint!</Translation>});
            } else {
                self.setState({constraintErrorMessage: ''});
            }

        }).catch(function() {
            // @todo: what if request failed?
        });
    },

    handleInstall: function(e) {
        e.preventDefault();

        var self = this;
        var form = document.getElementById('install-form');

        TensideState.getState()
            .then(function(state) {
                // Configure tenside if not already configured
                if (true !== state.tenside_configured) {
                    var username = form.querySelectorAll('input[name="username"]')[0].value;
                    var password = form.querySelectorAll('input[name="password"]')[0].value;

                    return self.configure(username, password);
                }

                return state;
            })
            .then(function(state) {
                // Create project if not already created
                if (true !== state.project_created) {
                    var createProjectPayload = {
                        project: {
                            name: 'contao/standard-edition'
                        }
                    };

                    var versionField = form.querySelectorAll('input[name="version"]')[0];

                    if ('' !== versionField.value) {
                        createProjectPayload.version = versionField.value;
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
                console.log(err);
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

    onPasswordError: function() {
        this.setState({hasPasswordErrors: true});
    },

    onPasswordNoError: function() {
        this.setState({hasPasswordErrors: false});
    },

    componentWillUnmount: function() {
        this.tensideStatePromise.cancel();
    },

    render: function() {

        var disableButton = this.state.hasPasswordErrors || this.state.installing;
        var usernamePart = '';
        if (this.state.isLoggedIn) {
            usernamePart = <LoggedInComponent username={this.state.username} />;
        } else {
            usernamePart = <UsernameComponent onPasswordError={this.onPasswordError} onPasswordNoError={this.onPasswordNoError} />;
        }

        return (
            <Trappings wide={true}>
                <header>
                    <img src="/web-assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
                    <p className="welcome"><strong>Welcome</strong> to Contao Manager v1.0</p>
                    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem.</p>
                </header>

                <section>
                    <form id="install-form" action="#" method="post">
                        {usernamePart}

                        <fieldset>
                            <legend>Contao Installation</legend>
                            <p>Enter a version to install or leave blank for the latest version.</p>

                            <TextWidget type="text" name="version" label="Version" placeholder="latest" onChange={this.validateConstraint} error={this.state.constraintErrorMessage} />

                        </fieldset>

                        <button disabled={disableButton} type="submit" onClick={this.handleInstall}>Install</button>
                    </form>
                </section>
            </Trappings>
        );
    }
});


var UsernameComponent = React.createClass({

    password: '',
    passwordConfirm: '',

    getInitialState: function() {
        return {
            passwordsErrorMessage: ''
        };
    },

    handlePasswordCompare: function(e, props) {
        if (props.name == 'password') {
            this.password = e.target.value;
        } else {
            this.passwordConfirm = e.target.value;
        }

        if ('' === this.password || '' === this.passwordConfirm || this.password === this.passwordConfirm) {
            this.setState({passwordsErrorMessage: ''});

            if (undefined !== this.props.onPasswordNoError){
                this.props.onPasswordNoError.call(this);
            }

        } else {
            this.setState({passwordsErrorMessage: <Translation domain="install">Passwords do not match!</Translation>});

            if (undefined !== this.props.onPasswordError){
                this.props.onPasswordError.call(this);
            }
        }
    },

    render: function() {
        return (
            <fieldset>
                <legend><Translation domain="install">User Account</Translation></legend>
                <p>Create a user account to manage your installation.</p>

                <TextWidget type="text" name="username" label="Username" />
                <TextWidget type="password" name="password" label="Password" onChange={this.handlePasswordCompare} error={this.state.passwordsErrorMessage} />
                <TextWidget type="password" name="password_confirm" label="Retype Password" onChange={this.handlePasswordCompare} error={this.state.passwordsErrorMessage} />

            </fieldset>
        )
    }
});

var LoggedInComponent = React.createClass({

    render: function() {
        return (
            <fieldset>
                <legend><Translation placeholders={{ username: this.props.username }}>You are logged in as %username%.</Translation></legend>
            </fieldset>
        )
    }
});

module.exports = InstallComponent;
