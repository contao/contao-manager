import React from 'react';
import isEqual from 'lodash/isEqual';

class BoxedTrappingsComponent extends React.Component {

    shouldComponentUpdate(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    }

    render() {
        return (
            <div id="content" className={"table" + (this.props.wide ? ' wide' : '')}>
                <div className="cell">
                    <main className={this.props.mainClass ? ' '+this.props.mainClass : ''}>
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
}

export default BoxedTrappingsComponent;
