var React = require('react');
var ReactDOM = require('react-dom');

var InstallComponent = React.createClass({
    render: function() {
        return (
            <form action="#" method="post">
                <fieldset>
                    <legend>User Account</legend>
                    <p>Create a user account to manage your installation.</p>

                    <div className="widget">
                        <label htmlFor="ctrl_username">Username</label>
                        <input type="text" id="ctrl_username" />
                    </div>

                    <div className="widget">
                        <label htmlFor="ctrl_password">Password</label>
                        <input type="password" id="ctrl_password" />
                    </div>

                    <div className="widget">
                        <label htmlFor="ctrl_password_confirm">Retype Password</label>
                        <input type="password" id="ctrl_password_confirm" />
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Contao Installation</legend>
                    <p>Enter a version to install or leave blank for the latest version.</p>

                    <div className="widget">
                        <label htmlFor="ctrl_version">Version</label>
                        <input type="password" id="ctrl_version" placeholder="latest" />
                    </div>
                </fieldset>

                <button type="submit">Install</button>
            </form>
        );
    }
});

ReactDOM.render(
<InstallComponent />,
    document.getElementById('install_component')
);
