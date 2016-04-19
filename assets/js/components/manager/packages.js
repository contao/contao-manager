'use strict';

const React         = require('react');
const Trappings     = require('./trappings.js');
const Package       = require('./package.js');
const request       = require('./../helpers/request.js');
const RadioWidget   = require('./../widgets/radio.js');
const Translation   = require('./../translation.js');
const _             = require('lodash');


var PackagesComponent = React.createClass({

    requests: [],

    getInitialState: function() {
        return {
            mode: 'packages',
            keywords : '',
            type: 'installed',
            threshold: 20,
            packages: []
        };
    },

    componentDidMount: function() {

        this.loadPackagesPackages();

        // Focus search input
        document.getElementById('search').focus();
    },

    shouldComponentUpdate: function(nextProps, nextState) {
        // Only update if the state is different.
        return !_.isEqual(this.state, nextState);
    },

    componentWillUpdate: function(nextProps, nextState) {
        this.stopRunningRequests();

        if ('packages' === nextState.mode) {
            this.loadPackagesPackages();
        } else{
            this.loadSearchPackages(nextState);
        }
    },

    componentWillUnmount: function() {
        this.stopRunningRequests();
    },

    loadSearchPackages: function(state) {
        var self = this;

        var searchPayload = {
            keywords: state.keywords,
            type: state.type,
            threshold: state.threshold
        };

        var req = request.createRequest('/api/v1/search', {
                method: 'POST',
                data: JSON.stringify(searchPayload)
            })
            .then(function(response) {
                // @todo should this not return a status too?
                self.setState({packages: response});

            }).catch(function() {
                // @todo: what if request failed?
            });

        this.requests.push(req);
    },

    loadPackagesPackages: function() {
        var self = this;
        var req = request.createRequest('/api/v1/packages')
            .then(function(response) {
                // @todo should this not return a status too?
                self.setState({packages: response});
            })
            .catch(function() {
                // @todo: what if request failed?
            });

        this.requests.push(req);
    },

    updateKeywordsOnType: function(e) {
        e.preventDefault();
        var keywords = e.target.value;

        this.setState({
            mode: '' !== keywords ? 'search' : 'packages',
            keywords: keywords
        });
    },

    handleTypeChange: function(e) {
        this.setState({
            type: e.target.value
        });
    },

    handleCloseButton: function(e) {
        e.preventDefault();

        this.setState({
            mode: 'packages',
            keywords: ''
        });
    },

    stopRunningRequests: function() {
        var self = this;
        _.forEach(_.reverse(this.requests), function(req, i) {
            req.cancel();
            self.requests.splice(i, 1);
        });
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

        if ('search' === this.state.mode) {
            search = <SearchTypeComponent onChange={this.handleTypeChange} onClose={this.handleCloseButton} selected={this.state.type} />
        }

        return (
            <Trappings>

                <section className="search">
                    <input id="search" type="text" placeholder="Search Packages…" onChange={this.updateKeywordsOnType} value={this.state.keywords} />
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
        return (
            <fieldset className="type">
                <legend><Translation domain="packages">Search in</Translation></legend>
                <a href="#close" className="close" onClick={this.props.onClose}>
                    <i className="icono-cross" />
                    <Translation domain="packages">Close search</Translation>
                </a>
                <RadioWidget options={this.options} onChange={this.props.onChange} name="searchType" selected={this.props.selected}/>
            </fieldset>
        )
    }
});

module.exports = PackagesComponent;
