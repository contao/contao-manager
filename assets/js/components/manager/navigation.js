'use strict';

const React         = require('react');
const Translation   = require('./../translation.js');
const routing       = require('./../helpers/routing.js');

var Link = React.createClass({

    handleClick: function(e) {
        e.preventDefault();

        routing.redirect(this.props.routeName);
    },

    generateLink: function(routeName) {
        return routing.generateUrl(routeName);
    },

    isRouteActive: function(routeName) {
        return routing.isCurrentRoute(routeName);
    },

    getLabel: function(routeName) {
        var label = '';
        var lookup = {
            'packages':         'Packages',
            'app-kernel':       'AppKernel.php',
            'composer-json':    'composer.json'
        };

        if (undefined !== lookup[routeName]) {
            label = lookup[routeName];
        }

        return <Translation domain="navigation">{label}</Translation>;
    },

    render: function() {
        return (
            <a onClick={this.handleClick}
               href={this.generateLink(this.props.routeName)}
               className={this.isRouteActive(this.props.routeName) ? 'active' : 'inactive'}
            >{this.getLabel(this.props.routeName)}</a>
        )
    }
});

var NavigationComponent = React.createClass({
    getInitialState: function() {
        return {};
    },

    render: function() {
        return (
            <nav role="navigation">
                <ul>
                    <li><Link routeName="packages"/></li>
                    <li>
                        <a><Translation domain="navigation">Files</Translation></a>
                        <ul>
                            <li><Link routeName="app-kernel"/></li>
                            <li><Link routeName="composer-json"/></li>
                        </ul>
                    </li>
                    <li><a href="#"><Translation domain="navigation">Configuration</Translation></a></li>
                </ul>
            </nav>
        );
    }
});

module.exports = NavigationComponent;
