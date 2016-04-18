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
                    <a id="logout" href="#">
                        <svg width="22" height="22" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M256 512c-64 0-124.2-24.9-169.4-70.2S16.4 336.4 16.4 272.4 41.3 148.2 86.6 103c4.1-4.1 10.8-4.1 14.9 0s4.1 10.8 0 14.9c-41.3 41.3-64 96.2-64 154.5s22.7 113.3 64 154.5c41.3 41.3 96.2 64 154.5 64 58.4 0 113.3-22.7 154.5-64 41.3-41.3 64-96.2 64-154.5s-22.7-113.3-64-154.5c-4.1-4.1-4.1-10.8 0-14.9s10.8-4.1 14.9 0c45.3 45.3 70.2 105.4 70.2 169.4s-24.9 124.2-70.2 169.4S320 512 256 512z"/><path d="M256 233c-5.8 0-10.5-4.7-10.5-10.5v-212C245.5 4.7 250.2 0 256 0s10.5 4.7 10.5 10.5v211.9c0 5.9-4.7 10.6-10.5 10.6z"/></svg>
                    </a>
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
