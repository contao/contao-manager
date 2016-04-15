'use strict';

const React         = require('react');
const jQuery        = require('jquery');
const Trappings     = require('./trappings.js');
const Package       = require('./package.js');
const Warning       = require('./package-warning.js');
const request       = require('./../helpers/request.js');
const RadioWidget   = require('./../widgets/radio.js');
const _             = require('lodash');


var PackagesComponent = React.createClass({

    loadPackagesRequest: null,
    searchPackagesRequest: null,

    getInitialState: function() {
        return {
            searching: false,
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

        // Focus search input
        jQuery('#search').focus();
    },

    componentWillUnmount: function() {
        this.stopRunningRequests();
    },

    handleSearchClose: function(e) {
        e.preventDefault();
        this.stopRunningRequests();
        self.setState({searching: false});
        jQuery('#search').val('');
    },

    handleSearch: function(e) {
        e.preventDefault();
        var self = this;
        var keywords = jQuery('#search').val();

        // Stop running requests
        this.stopRunningRequests();

        // Leave searching state when keywords is empty
        if ('' === keywords) {
            self.setState({searching: false});
            return;
        }

        self.setState({searching: true});

        var searchPayload = {
            keywords: keywords,
            type: 'installed',
            threshold: 20
        };

        self.searchPackagesRequest = request.createRequest('/api/v1/search', {
            method: 'POST',
            data: JSON.stringify(searchPayload)
        });

        var delay;
        clearTimeout(delay);
        delay = setTimeout(function() {
            self.searchPackagesRequest .success(function(response) {
                // @todo should this not return a status too?

                self.setState({packages: response});

            }).fail(function() {
                // @todo: what if request failed?
            });

        }, 1000);

    },

    stopRunningRequests: function() {
        if (null !== this.loadPackagesRequest) {
            this.loadPackagesRequest.abort();
        }

        if (null !== this.searchPackagesRequest) {
            this.searchPackagesRequest.abort();
        }
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

        var search = '';

        if (this.state.searching) {
            search = <SearchTypeComponent />
        }

        return (
            <Trappings>

                <section className="search">
                    <input id="search" type="text" placeholder="Search Packages…" onKeyUp={this.handleSearch} />
                    <button>Check for Updates</button>
                </section>

                <section className="sorting">
                    <ul>
                        <li><a href="#" className="desc">Name</a></li>
                        <li><a href="#">Last updated</a></li>
                    </ul>
                </section>

                {search}

                {packages}

            </Trappings>
        );
    }
});

var SearchTypeComponent = React.createClass({

    options: [{
        value: 'mine',
        label: 'Mine'
    }, {
        value: 'contao',
        label: 'Contao'
    }, {
        value: 'all',
        label: 'All'
    }
    ],

    render: function() {
        return (
            <fieldset className="type">
                <legend>Search in</legend>
                {/* @todo add onClick here */}
                <a href="#" className="close">
                    <i className="icono-cross" />
                    Close
                </a>
                <RadioWidget options={this.options} name="searchType" selected="mine"/>
            </fieldset>
        )
    }
});

module.exports = PackagesComponent;
