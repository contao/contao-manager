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
                            © 2016 <a href="http://association.contao.org/" target="_blank">Contao Association</a>
                            <a href="#" className="support">Support</a>
                        </footer>
                    </main>
                </div>
            </div>
        );
    }
});

module.exports = BoxedTrappingsComponent;
