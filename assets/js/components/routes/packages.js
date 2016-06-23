'use strict';

const React         = require('react');
const Trappings     = require('../trappings/main.js');
const Package       = require('../fragments/package.js');
const Loader        = require('../fragments/loader.js');
const request       = require('../../helpers/request.js');
const Translation   = require('../translation.js');
const forEach       = require('lodash/forEach');
const merge         = require('lodash/merge');
const reverse       = require('lodash/reverse');

var PackagesComponent = React.createClass({

    requests: [],

    getInitialState: function() {
        return {
            mode: 'packages',
            loading: false,
            searchRequest: {
                keywords: '',
                threshold: 20
            },
            packages: [],
            changes: {}
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
            type:       'contao',
            threshold:  searchRequest.threshold
        };

        var req = request.createRequest('/api/v1/search', {
                method: 'POST',
                data: JSON.stringify(searchPayload)
            })
            .then(function(response) {
                // @todo should this not return a status too?
                self.setState({packages: response, loading: false, changes: {}});
            })
            .catch(function() {
                // @todo: what if request failed?
                self.setState({loading: false, changes: {}});
            });

        this.requests.push(req);
    },

    loadPackagesPackages: function() {
        var self = this;
        this.setState({loading: true});

        var req = request.createRequest('/api/v1/packages')
            .then(function(response) {
                // @todo should this not return a status too?
                self.setState({packages: response, loading: false, changes: {}});
            })
            .catch(function() {
                // @todo: what if request failed?
                self.setState({loading: false, changes: {}});
            });

        this.requests.push(req);
    },

    closeSearch: function() {
        var searchRequest = merge({}, this.state.searchRequest, {keywords: ''});

        this.setState({
            mode: 'packages',
            searchRequest: searchRequest
        });

        this.updatePackageList('packages', searchRequest);
    },

    updateKeywordsOnType: function(e) {
        e.preventDefault();
        var searchRequest = merge({}, this.state.searchRequest, {keywords: e.target.value});
        var mode = '' === e.target.value ? 'packages' : 'search';

        this.setState({
            mode: mode,
            searchRequest: searchRequest
        });

        this.updatePackageList(mode, searchRequest);
    },

    handleApplyButton: function(e) {
        e.preventDefault();

        // @todo run the taskrunner
    },

    handleResetButton: function(e) {
        e.preventDefault();

        this.updatePackageList('packages');
    },

    handlePackageModified: function(name, data) {
        var changes = this.state.changes;

        if (data.modified) {
            changes[name] = data;
        } else if (changes.hasOwnProperty(name)) {
            delete changes[name];
        }

        this.setState({changes: changes});

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
            closeSearch={this.closeSearch}
            keywords={this.state.searchRequest.keywords}
            onKeywordsChange={this.updateKeywordsOnType}
         />;

        if (this.state.loading) {
            packages = <Loader>{'search' === this.state.mode ? 'Searching packages' : 'Loading'} …</Loader>;
        } else {
            packages = [];

            forEach(this.state.packages, function(packageData) {
                packages.push(<Package
                    key={packageData.name}
                    mode={self.state.mode}
                    name={packageData.name}
                    description={packageData.description}
                    licenses={packageData.license}
                    constraint={packageData.constraint}
                    installed={packageData.installed}
                    enabled={true}
                    time={packageData.time}
                    keywords={self.state.searchRequest.keywords}
                    canBeRemoved={'packages' === self.state.mode}
                    canBeEnabledOrDisabled={'packages' === self.state.mode}
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

                <div id="package-actions" className={Object.keys(this.state.changes).length > 0 ? 'active' : ''}>
                    <div className="inner">
                        <p>You have unconfirmed changes.</p>
                        <button className="apply" onClick={this.handleApplyButton}>Apply changes</button>
                        <button className="reset" onClick={this.handleResetButton}>Reset changes</button>
                    </div>
                </div>

            </Trappings>
        );
    }
});

var SearchTypeComponent = React.createClass({

    getInitialState: function() {
        return {
            searchActive: false
        };
    },

    componentDidUpdate: function(prevProps, prevState) {
        if (!prevState.searchActive && this.state.searchActive) {
            this.refs.searchInput.focus();
        }
    },

    toggleSearch: function() {
        this.setState({searchActive: true});
    },

    handleSearchBlur: function() {
        if ('' == this.refs.searchInput.value) {
            this.setState({searchActive: false});
        }
    },

    handleSearchKey: function(e) {
        if ('Escape' == e.key) {
            this.props.closeSearch();
            this.setState({searchActive: false});
        }
    },

    handleCancel: function(e) {
        e.preventDefault();
        this.props.closeSearch();
        this.setState({searchActive: false});
    },

    render: function() {
        var sectionClass = 'package-actions';

        if (this.state.searchActive) {
            sectionClass = sectionClass + ' search-active';
        }

        return  (
            <section className={sectionClass}>

                <button className="update">Check for Updates</button>
                <button className="search" onClick={this.toggleSearch}>Search packages </button>
                <input id="search" ref="searchInput" type="text" placeholder="Search Packages…" onChange={this.props.onKeywordsChange} onBlur={this.handleSearchBlur} onKeyUp={this.handleSearchKey} value={this.props.keywords} />
                <button className="cancel" onClick={this.handleCancel}>X</button>

            </section>
        );
    }
});

module.exports = PackagesComponent;
