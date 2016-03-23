'use strict';

const React = require('react');

var NavigationComponent = React.createClass({
    getInitialState: function() {
        return {};
    },

    render: function() {
        return (
            <nav role="navigation">
                <ul>
                    <li><a href="#" className="active">Packages</a></li>
                    <li>
                        <a href="#">Files</a>
                        <ul>
                            <li><a href="#">AppKernel</a></li>
                            <li><a href="#">composer.json</a></li>
                        </ul>
                    </li>
                    <li><a href="#">Configuration</a></li>
                </ul>
            </nav>
        );
    }
});

module.exports = NavigationComponent;
