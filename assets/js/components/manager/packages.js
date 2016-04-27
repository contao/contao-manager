'use strict';

const React         = require('react');
const Trappings     = require('./trappings.js');
const Package       = require('./package.js');
const request       = require('./../helpers/request.js');
const RadioWidget   = require('./../widgets/radio.js');
const Translation   = require('./../translation.js');
const forEach       = require('lodash/forEach');
const merge         = require('lodash/merge');
const reverse       = require('lodash/reverse');

var PackagesComponent = React.createClass({

    requests: [],

    getInitialState: function() {
        return {
            mode: 'packages',
            loading: false,
            showApplyButton: false,
            searchRequest: {
                keywords: '',
                type: 'installed',
                threshold: 20
            },
            packages: []
        };
    },

    componentDidMount: function() {

        this.updatePackageList('packages');

        // Focus search input
        document.getElementById('search').focus();
    },

    componentWillUnmount: function() {
        this.stopRunningRequests();
    },

    updatePackageList: function(mode, searchRequest) {

        searchRequest = searchRequest || {
            keywords: '',
            type: 'installed',
            threshold: 20
        };

        this.stopRunningRequests();

        if ('packages' === mode) {
            this.loadPackagesPackages();
        } else {
            this.loadSearchPackages(searchRequest);
        }
    },


    loadSearchPackages: function(searchRequest) {
        var self = this;
        this.setState({loading: true});

        var searchPayload = {
            keywords:   searchRequest.keywords,
            type:       searchRequest.type,
            threshold:  searchRequest.threshold
        };

        var req = request.createRequest('/api/v1/search', {
                method: 'POST',
                data: JSON.stringify(searchPayload)
            })
            .then(function(response) {
                // @todo should this not return a status too?
                self.setState({packages: response, loading: false});
            })
            .catch(function() {
                // @todo: what if request failed?
                self.setState({loading: false});
            });

        this.requests.push(req);
    },

    loadPackagesPackages: function() {
        var self = this;
        this.setState({loading: true});

        var req = request.createRequest('/api/v1/packages')
            .then(function(response) {
                // @todo should this not return a status too?
                self.setState({packages: response, loading: false});
            })
            .catch(function() {
                // @todo: what if request failed?
                self.setState({loading: false});
            });

        this.requests.push(req);
    },

    updateKeywordsOnType: function(e) {
        e.preventDefault();
        var searchRequest = merge(this.state.searchRequest, {keywords: e.target.value});
        var mode = '' === e.target.value ? 'packages' : 'search';

        this.setState({
            mode: mode,
            searchRequest: searchRequest
        });

        this.updatePackageList(mode, searchRequest);
    },

    handleTypeChange: function(e) {
        var searchRequest = merge(this.state.searchRequest, {type: e.target.value});

        this.setState({
            searchRequest: searchRequest
        });

        this.updatePackageList('search', searchRequest);
    },

    handleCloseButton: function(e) {
        e.preventDefault();
        var searchRequest = merge(this.state.searchRequest, {keywords: ''});

        this.setState({
            mode: 'packages',
            searchRequest: searchRequest
        });

        this.updatePackageList('packages', searchRequest);
    },

    handlePackageModified: function(data) {
        if (data.modified) {
            this.setState({showApplyButton: true});
        } else {
            this.setState({showApplyButton: false});
        }
        // @todo request the api to modify the package
    },

    stopRunningRequests: function() {
        var self = this;
        forEach(reverse(this.requests), function(req, i) {
            req.cancel();
            self.requests.splice(i, 1);
        });
    },

    render: function() {
        var packages = null;
        var self     = this;
        var search = <SearchTypeComponent
            mode={this.state.mode}
            onTypeChange={this.handleTypeChange}
            onClose={this.handleCloseButton}
            selected={this.state.searchRequest.type}
            keywords={this.state.searchRequest.keywords}
            onKeywordsChange={this.updateKeywordsOnType}
         />;

        if (this.state.loading) {
            // @todo
            packages = 'Hello, I can be a beautiful ajax spinner.';
        } else {
            packages = [];

            forEach(this.state.packages, function(packageData) {
                packages.push(<Package
                    key={packageData.name}
                    name={packageData.name}
                    description={packageData.description}
                    licenses={packageData.license}
                    constraint={packageData.constraint}
                    installed={packageData.installed}
                    time={packageData.time}
                    keywords={self.state.searchRequest.keywords}
                    canBeRemoved={'packages' === self.state.mode}
                    canBeInstalled={'search' === self.state.mode}
                    onModified={self.handlePackageModified}
                />);
            });

            if (0 === packages.length) {
                packages = <Translation domain="packages">No packages found.</Translation>
            }
        }

        return (
            <Trappings>

                {search}

                {packages}

            </Trappings>
        );
    }
});

var SearchTypeComponent = React.createClass({

    options: [{
            value: 'installed',
            label: <Translation domain="packages">My installed packages</Translation>
        }, {
            value: 'contao',
            label: <Translation domain="packages">Available packages for Contao</Translation>
        }, {
            value: 'all',
            label: <Translation domain="packages">All available packages</Translation>
        }
    ],

    render: function() {

        var typeFilter = '';

        if ('search' === this.props.mode) {
            typeFilter = (
                <fieldset className="type">
                    <legend><Translation domain="packages">Search in</Translation></legend>
                    <a href="#close" className="close" onClick={this.props.onClose}>
                        <i className="icono-cross" />
                        <Translation domain="packages">Close search</Translation>
                    </a>
                    <RadioWidget options={this.options} onChange={this.props.onTypeChange} name="searchType" selected={this.props.selected}/>
                </fieldset>
            )
        }

        return (
            <section className="search">
                <input id="search" type="text" placeholder="Search Packages…" onChange={this.props.onKeywordsChange} value={this.props.keywords} />
                <button>Check for Updates</button>

                {typeFilter}

            </section>

        )
    }
});

module.exports = PackagesComponent;
