import React from 'react';
import TaskPopup from './taskpopup';

class AppComponent extends React.Component {

    getChildContext() {
        return {routing: this.props.routing};
    }

    render() {
        return (
            <div id="app">
                {this.props.children}
                <TaskPopup />
            </div>
        )
    }
}

AppComponent.childContextTypes = {
    routing: React.PropTypes.object
};

export default AppComponent;
