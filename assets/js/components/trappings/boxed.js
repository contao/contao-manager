'use strict';

const React         = require('react');
const isEqual       = require('lodash/isEqual');

var BoxedTrappingsComponent = React.createClass({

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    render: function() {
        return (
            <div id="content" className={"table" + (this.props.wide ? ' wide' : '')}>
                <div className="cell">
                    <main>
                        {this.props.children}

                        <footer>
                            Contao Manager v1.0
                            <a href="https://manager.contao.org" target="_blank" className="support">Support</a>
                        </footer>
                    </main>
                </div>
            </div>
        );
    }
});

module.exports = BoxedTrappingsComponent;
