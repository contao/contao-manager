'use strict';

const React = require('react');

var TrappingsComponent = React.createClass({
    getInitialState: function() {
        return {};
    },

    render: function() {
        var intro = this.props.install ? (<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem.</p>) : '';

        return (
            <div id="content" className={"table" + (this.props.install ? ' wide' : '')}>
                <div className="cell">
                    <main>
                        <header>
                            <img src="/web-assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
                            <p className="welcome"><strong>Welcome</strong> to Contao Manager v1.0</p>
                            {intro}
                        </header>

                        <section className={this.props.sectionClass}>
                            {this.props.children}
                        </section>

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

module.exports = TrappingsComponent;
