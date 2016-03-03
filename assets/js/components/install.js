var React = require('react');
var ReactDOM = require('react-dom');

var Widget = React.createClass({
    render: function() {
        return (
            <div className="widget">
                <label htmlFor="ctrl_{this.props.name}">{this.props.label}</label>
                <input type="{this.props.type}" id="ctrl_{this.props.name}" placeholder="{this.props.placeholder}" />
            </div>
        );
    }
});

var InstallComponent = React.createClass({
    render: function() {
        return (
            <form action="#" method="post">
                <fieldset>
                    <legend>User Account</legend>
                    <p>Create a user account to manage your installation.</p>

                    <Widget type="text" name="username" label="Username"></Widget>
                    <Widget type="password" name="password" label="Password"></Widget>
                    <Widget type="password" name="password_confirm" label="Retype Password"></Widget>

                </fieldset>

                <fieldset>
                    <legend>Contao Installation</legend>
                    <p>Enter a version to install or leave blank for the latest version.</p>

                    <Widget type="text" name="version" label="Retype Password" placeholder="latest"></Widget>

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
