'use strict';

const React     = require('react');
const Trappings = require('./trappings.js');
const Package   = require('./package.js');
const Warning   = require('./package-warning.js');

var PackagesComponent = React.createClass({
    getInitialState: function() {
        return {};
    },

    render: function() {
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

                <Package
                    name="contao/contao"
                    description="Contao Open Source CMS."
                    website="#"
                    license="LGPL-3.0+"
                    installs="2 091"
                    constraint="4.2.0@dev"
                    canBeEnabled={true}
                    isEnabled={true}
                />

                <Package
                    name="isotope/isotope-core"
                    description="Core repository of Isotope eCommerce, an eCommerce extension for Contao Open Source CMS."
                    website="#"
                    license="LGPL-3.0+"
                    installs="5000"
                    constraint=""
                    canBeEnabled={true}
                    isEnabled={false}
                    before={(() => {
                        var links = [
                            {
                                label: "Edit composer.json",
                                href:  "#"
                            },
                            {
                                label: "Help",
                                href:  "#"
                            }
                        ];

                        return (
                            <Warning message="Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor." links={links} />
                        )
                    })()}
                />

                <Package
                    name="metamodels/core"
                    description="MetaModels core."
                    website="#"
                    license="LGPL-3.0+"
                    installs="14859"
                    constraint="^2.3.0"
                    canBeEnabled={true}
                    isEnabled={true}
                />

            </Trappings>
        );
    }
});

module.exports = PackagesComponent;
