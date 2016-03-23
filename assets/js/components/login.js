'use strict';

const React         = require('react');
const TextWidget    = require('./widgets/text.js');
const eventhandler  = require('./eventhandler.js');
const taskmanager   = require('./taskmanager.js');
const request       = require('./request.js');


var InstallComponent = React.createClass({
    getInitialState: function() {
        return {};
    },

    render: function() {
        return (
            <div id="content" className="table">
                <div className="cell">
                    <main>
                        <header>
                            <img src="/web-assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
                            <p className="welcome"><strong>Welcome</strong> to Contao Manager v1.0</p>
                        </header>

                        <section className="login">
                            <h1>Sign In</h1>
                            <p>Login to manage your installation.</p>

                            <form action="#" method="post">
                                <TextWidget type="text" name="username" label="Username" placeholder="Username" />
                                <TextWidget type="password" name="password" label="Password" placeholder="Password" />

                                <a href="">Forgot your password?</a>

                                <button type="submit">Sign In</button>
                            </form>
                        </section>

                        <footer>
                            © 2016 <a href="http://association.contao.org/" target="_blank">Contao Association</a>
                            <a href="" className="support">Support</a>
                        </footer>
                    </main>
                </div>
            </div>
        );
    }
});

module.exports = InstallComponent;
