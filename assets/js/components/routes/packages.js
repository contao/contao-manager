import React        from 'react';
import Trappings    from '../trappings/main';
import Package      from '../fragments/package';
import Loader       from '../fragments/loader';
import request      from '../../helpers/request';
import taskmanager  from '../../helpers/taskmanager';
import eventhandler from '../../helpers/eventhandler';
import Translation  from '../translation';
import forEach      from 'lodash/forEach';
import merge        from 'lodash/merge';
import reverse      from 'lodash/reverse';

class PackagesComponent extends React.Component {

    constructor(props) {
        super(props);

        this.requests = [];

        this.state = {
            mode: 'packages',
            loading: false,
            searchRequest: {
                keywords: '',
                threshold: 20
            },
            packages: [],
            changes: {}
        };

        this.handleApplyButton = this.handleApplyButton.bind(this);
        this.handlePackageModified = this.handlePackageModified.bind(this);
        this.handleResetButton = this.handleResetButton.bind(this);
        this.updateKeywordsOnType = this.updateKeywordsOnType.bind(this);
        this.searchUpdates = this.searchUpdates.bind(this);
    }

    componentDidMount() {
        this.updatePackageList('packages');

        // Reset state when search is closed
        eventhandler.on('closeSearch', function() {
            var searchRequest = merge({}, this.state.searchRequest, {keywords: ''});

            this.setState({
                mode: 'packages',
                searchRequest: searchRequest
            });

            this.updatePackageList('packages', searchRequest);
        }.bind(this));

        // Reload packages list when install or update tasks were running
        eventhandler.on('hideTaskPopup', function() {
            // Reset the search keywords after closing the popup
            // which will also go to the packages list.
            eventhandler.emit('closeSearch');
        });
    }

    componentWillUnmount() {
        this.stopRunningRequests();
    }

    updatePackageList(mode, searchRequest) {

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
    }

    loadSearchPackages(searchRequest) {
        var self = this;
        this.setState({loading: true});

        var searchPayload = {
            keywords:   searchRequest.keywords,
            type:       'contao',
            threshold:  searchRequest.threshold
        };

        var req = request.createRequest('/api/v1/search', {
                method: 'POST',
                json: searchPayload
            })
            .then(function(response) {
                // @todo should this not return a status too?
                self.setState({packages: response.body, loading: false, changes: {}});

                return null;
            })
            .catch(function() {
                // @todo: what if request failed?
                self.setState({loading: false, changes: {}});
            });

        this.requests.push(req);
    }

    loadPackagesPackages() {
        var self = this;
        this.setState({loading: true});

        var req = request.createRequest('/api/v1/packages')
            .then(function(response) {
                // @todo should this not return a status too?
                self.setState({packages: response.body, loading: false, changes: {}});

                return null;
            })
            .catch(function() {
                // @todo: what if request failed?
                self.setState({loading: false, changes: {}});
            });

        this.requests.push(req);
    }

    updateKeywordsOnType(e) {
        e.preventDefault();
        var searchRequest = merge({}, this.state.searchRequest, {keywords: e.target.value});
        var mode = '' === e.target.value ? 'packages' : 'search';

        this.setState({
            mode: mode,
            searchRequest: searchRequest
        });

        this.updatePackageList(mode, searchRequest);
    }

    searchUpdates(e) {
        e.preventDefault();
        var task = {
            'type': 'upgrade',
            'dry-run': true
        };
        taskmanager.addTask(task).then(taskmanager.runNextTask);
    }

    handleApplyButton(e) {
        e.preventDefault();

        var task = null;
        var removePackages = [];
        var installPackages = [];

        forEach(this.state.changes, function(change, name) {
            if (change.removed) {
                removePackages.push(name);
            }

            if (change.installed || change.constraintChanged) {
                installPackages.push(name + ((change.constraint) ? ' ' + change.constraint : ''));
            }
        });

        if (removePackages.length) {
            task = {
                'type': 'remove-package',
                'package': removePackages
            }
        }

        // If we have an install task, we don't remove anything at all
        // by overriding the task variable here
        if (installPackages.length) {
            task = {
                'type': 'require-package',
                'package': installPackages
            }
        }

        taskmanager.addTask(task)
            .then(taskmanager.runNextTask);
    }

    handleResetButton(e) {
        e.preventDefault();

        this.updatePackageList('packages');
    }

    handlePackageModified(name, data) {
        var changes = this.state.changes;

        if (data.modified) {
            changes[name] = data;
        } else if (changes.hasOwnProperty(name)) {
            delete changes[name];
        }

        this.setState({changes: changes});
    }

    stopRunningRequests() {
        var self = this;
        forEach(reverse(this.requests), function(req, i) {
            req.cancel();
            self.requests.splice(i, 1);
        });
    }

    render() {
        var packages = null;
        var self     = this;
        var search = <SearchTypeComponent
            disableButtons={Object.keys(this.state.changes).length > 0 || 'packages' !== this.state.mode}
            keywords={this.state.searchRequest.keywords}
            onKeywordsChange={this.updateKeywordsOnType}
            onSearchUpdates={this.searchUpdates}
         />;

        if (this.state.loading) {
            if ('search' === this.state.mode) {
                packages = <Loader>Searching for Contao packages matching <i>{this.state.searchRequest.keywords}</i> …</Loader>;
            } else {
                packages = <Loader>Loading …</Loader>
            }
        } else {
            packages = [];

            forEach(this.state.packages, function(packageData) {
                packages.push(<Package
                    key={packageData.name}
                    mode={self.state.mode}
                    name={packageData.name}
                    description={packageData.description}
                    licenses={packageData.license}
                    constraint={packageData.constraint ? packageData.constraint : ''}
                    icon={packageData.extra && packageData.extra.icon}
                    version={packageData.version}
                    upgrade_version={packageData.upgrade_version}
                    upgrade_time={packageData.upgrade_time}
                    time={packageData.time}
                    abandoned={packageData.abandoned}
                    enabled={true}
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
}

class SearchTypeComponent extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            searchActive: false
        };

        this.openSearch = this.openSearch.bind(this);
        this.closeSearch = this.closeSearch.bind(this);
        this.handleSearchBlur = this.handleSearchBlur.bind(this);
    }

    componentDidMount() {
        eventhandler.on('closeSearch', this.closeSearch);
    }

    componentDidUpdate(prevProps, prevState) {
        if (!prevState.searchActive && this.state.searchActive) {
            this.refs.searchInput.focus();
        }
    }

    openSearch() {
        this.setState({searchActive: true});
    }

    closeSearch() {
        this.setState({searchActive: false});
    }

    handleSearchBlur() {
        if ('' == this.refs.searchInput.value) {
            eventhandler.emit('closeSearch');
        }
    }

    handleSearchKey(e) {
        if ('Escape' == e.key) {
            eventhandler.emit('closeSearch');
        }
    }

    handleCancel(e) {
        e.preventDefault();
        eventhandler.emit('closeSearch');
    }

    render() {
        var sectionClass = 'package-actions';

        if (this.state.searchActive) {
            sectionClass = sectionClass + ' search-active';
        }

        return  (
            <section className={sectionClass}>

                <button className="update" disabled={this.props.disableButtons} onClick={this.props.onSearchUpdates}>Check for Updates</button>
                <button className="search" disabled={this.props.disableButtons} onClick={this.openSearch}>Search packages </button>
                <input id="search" ref="searchInput" type="text" placeholder="Search Packages…" onChange={this.props.onKeywordsChange} onBlur={this.handleSearchBlur} onKeyUp={this.handleSearchKey} value={this.props.keywords} />
                <button className="cancel" onClick={this.handleCancel}>X</button>

            </section>
        );
    }
}

export default PackagesComponent;
