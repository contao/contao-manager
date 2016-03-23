'use strict';

const React         = require('react');
const Trappings     = require('./trappings.js')
const TextWidget    = require('../widgets/text.js');


var InstallComponent = React.createClass({
    getInitialState: function() {
        return {};
    },

    render: function() {
        return (
            <Trappings sectionClass="login">
                <h1>Sign In</h1>
                <p>Login to manage your installation.</p>

                <form action="#" method="post">
                    <TextWidget type="text" name="username" label="Username" placeholder="Username" />
                    <TextWidget type="password" name="password" label="Password" placeholder="Password" />

                    <a href="">Forgot your password?</a>

                    <button type="submit">Sign In</button>
                </form>
            </Trappings>
        );
    }
});

module.exports = InstallComponent;
