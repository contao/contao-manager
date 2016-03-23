'use strict';

const React = require('react');
const Navigation = require('./navigation.js');

var TrappingsComponent = React.createClass({
    getInitialState: function() {
        return {};
    },

    render: function() {
        return (
            <div id="content" className="manager">

                <header>
                    <a id="logo" href="#"><img src="/web-assets/images/logo.svg" width="40" height="40" alt="Contao Logo" /> Contao Manager</a>
                    <a id="nav-toggle"><span /><span /><span /></a>
                    <Navigation />
                </header>

                <main>
                    {this.props.children}
                </main>

                <footer>
                    Contao Manager v1.0 - © <a href="http://association.contao.org/" target="_blank">Contao Association</a>
                    <a href="" className="support">Support</a>
                </footer>

            </div>
        );
    }
});

module.exports = TrappingsComponent;
