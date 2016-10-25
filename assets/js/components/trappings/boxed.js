import React from 'react';
import eventhandler from '../../helpers/eventhandler';

class BoxedTrappingsComponent extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            blurClass: ''
        };

        this.blurIn = this.blurIn.bind(this);
        this.blurOut = this.blurOut.bind(this);
    }

    componentDidMount() {
        eventhandler.on('displayTaskPopup', this.blurIn);
        eventhandler.on('hideTaskPopup', this.blurOut);
    }

    componentWillUnmount() {
        eventhandler.removeListener('displayTaskPopup', this.blurIn);
        eventhandler.removeListener('hideTaskPopup', this.blurOut);
    }

    blurIn() {
        this.setState({blurClass: ' blur-in'});
    }

    blurOut() {
        this.setState({blurClass: ' blur-out'});
    }

    render() {
        return (
            <div id="content" className={"table" + (this.props.wide ? ' wide' : '') + this.state.blurClass}>
                <div className="cell">
                    <main className={this.props.mainClass ? ' ' + this.props.mainClass : ''}>
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
