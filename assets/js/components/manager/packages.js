'use strict';

const React     = require('react');
const Trappings = require('./trappings.js');
const Package   = require('./package.js');
const Warning   = require('./package-warning.js');
const request   = require('./../helpers/request.js');
const _         = require('lodash');

var PackagesComponent = React.createClass({

    loadPackagesRequest: null,

    getInitialState: function() {
        return {
            packages: []
        };
    },

    componentDidMount: function() {
        var self = this;
        this.loadPackagesRequest = request.createRequest('/api/v1/packages', {
        }).success(function(response) {
            // @todo should this not return a status too?

            self.setState({packages: response});

        }).fail(function() {
            // @todo: what if request failed?
        });
    },

    componentWillUnmount: function() {
        this.loadPackagesRequest.abort();
    },

    render: function() {

        var packages = [];

        _.forEach(this.state.packages, function(packageData) {
            packages.push(<Package
                key={packageData.name}
                name={packageData.name}
                description={packageData.description}
                licenses={packageData.license}
                constraint={packageData.installed}
            />);
        });

        return (
            <Trappings>

                <section className="search">
                    <input type="text" placeholder="Search Packages…" />
                    <button>Check for Updates</button>
                </section>

                <section className="sorting">
                    <ul>
                        <li><a href="#" className="desc">Name</a></li>
                        <li><a href="#">Last updated</a></li>
                    </ul>
                </section>

                {packages}

            </Trappings>
        );
    }
});

module.exports = PackagesComponent;
