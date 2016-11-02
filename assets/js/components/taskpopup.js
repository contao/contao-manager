import React        from 'react';
import Translation  from './translation';
import eventhandler from './../helpers/eventhandler';
import * as taskmanager from './../helpers/taskmanager';
import merge        from 'lodash/merge';
import includes     from 'lodash/includes';

class TaskPopupComponent extends React.Component {

    constructor(props) {
        super(props);

        this.popup = null;
        this.lastTaskId = null;
        this.currentInterval = null;
        this.taskTitleCache = {};
        this.scrolled = false;

        this.state = TaskPopupComponent.initialState;

        this.startTaskUpdate = this.startTaskUpdate.bind(this);
        this.taskUpdate = this.taskUpdate.bind(this);
        this.handleButton = this.handleButton.bind(this);
        this.hideConsole = this.hideConsole.bind(this);
        this.showConsole = this.showConsole.bind(this);
        this.toggleFixedPosition = this.toggleFixedPosition.bind(this);
        this.onConsoleOutputScroll = this.onConsoleOutputScroll.bind(this);
        this.show = this.show.bind(this);
        this.hide = this.hide.bind(this);
    }

    componentDidMount() {
        this.popup = document.getElementById('task-popup');

        // Escape key
        window.addEventListener('keyup', function(e) {
            if (27 === e.keyCode && includes(['error', 'success'], this.state.status)) {
                eventhandler.emit('hideTaskPopup');
            }
        }.bind(this));

        window.addEventListener('resize', this.toggleFixedPosition);
        this.toggleFixedPosition();

        eventhandler.on('displayTaskPopup', this.show);
        eventhandler.on('hideTaskPopup', this.hide);
    }

    componentWillUnmount() {
        eventhandler.off('displayTaskPopup', this.show);
        eventhandler.off('hideTaskPopup', this.hide);
    }

    componentDidUpdate(prevProps, prevState) {

        // If task has changed, start update
        if (this.lastTaskId !== this.state.taskId) {
            this.startTaskUpdate();
        }

        this.lastTaskId = this.state.taskId;

        // Scroll to bottom of console if not scrolled manually
        if (!this.scrolled) {
            var consoleOutput = this.refs.consoleOutput;
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }
    }

    show(state) {
        state = state || {};
        var newState = merge({}, this.state, state, {show: true});

        this.setState(newState);
    }

    handleButton() {
        eventhandler.emit('hideTaskPopup', this.state);
    }

    hide() {
        this.scrolled = false;
        taskmanager.deleteTask(this.state.taskId);
        this.setState(TaskPopupComponent.initialState);
        window.clearInterval(this.currentInterval);
    }

    hideConsole() {
        this.setState({showConsole: false});
    }

    showConsole() {
        this.setState({showConsole: true});
    }

    toggleFixedPosition() {
        this.setState({positionFixed: this.popup && this.popup.clientHeight < window.innerHeight});
    }

    startTaskUpdate() {
        this.currentInterval = window.setInterval(this.taskUpdate, this.state.updateFrequency);
    }

    taskUpdate() {

        var taskId = this.state.taskId;
        var self = this;

        if (null === taskId) {
            window.clearInterval(this.currentInterval);
            return;
        }

        taskmanager.getTask(taskId)
            .then(function (response) {
                var newState = {
                    content: {
                        consoleOutput:response.body.task.output
                    }
                };
                switch (response.body.task.status) {
                    case 'PENDING':
                    case 'RUNNING':
                        break;
                    case 'FINISHED':
                        newState['status'] = 'success';
                        window.clearInterval(self.currentInterval);
                        break;
                    case 'ERROR':
                    default:
                        newState['status'] = 'error';
                        window.clearInterval(self.currentInterval);
                }

                newState['content']['taskTitle'] = self.getTaskTitle(response.body.task.type);
                self.setState(merge({}, self.state, newState));

                return null;
            }).catch(function (err) {
                self.setState(merge({}, self.state, {status: 'error'}));
                window.clearInterval(self.currentInterval);
            });
    }

    onConsoleOutputScroll () {
        var consoleOutput = this.refs.consoleOutput;
        this.scrolled = consoleOutput.scrollTop !== consoleOutput.scrollHeight;
    }

    extractConsolePreview() {
        // get the last line (with content from a string
        var chunks = this.state.content.consoleOutput.split("\n").reverse();
        for (var i=0;i<chunks.length;i++) {
            var line = chunks[i].trim();

            if ('' !== line) {

                return line;
            }
        }

        return '';
    }

    getTaskTitle(type) {

        if (undefined !== this.taskTitleCache[type]) {

            return this.taskTitleCache[type];
        }

        var label = '';
        var lookup = {
            'install':          'Setting up a Contao Application',
            'remove-package':   'Removing packages',
            'require-package':  'Installing packages',
            'upgrade':          'Checking for updates of installed packages'
        };

        if (undefined !== lookup[type]) {
            label = lookup[type];
        }

        this.taskTitleCache[type] = <Translation domain="taskpopup">{label}</Translation>;
        return this.taskTitleCache[type];
    }

    render() {

        var cssClasses = [];
        var consolePreview = this.extractConsolePreview();

        if (!this.state.show) {
            cssClasses.push('hidden');
        }

        if (this.state.showConsole) {
            cssClasses.push('console');
        }

        if (this.state.positionFixed) {
            cssClasses.push('fixed');
        }

        cssClasses.push('status-' + this.state.status);
        cssClasses = cssClasses.join(' ');

        return (
            <div id="task-popup" className={cssClasses ? cssClasses : ''}>
                <h1>{this.state.content.taskTitle}</h1>

                <div className="status success"><i className="icono-checkCircle" /></div>
                <div className="status error"><i className="icono-crossCircle" /></div>
                <div className="status loading">
                    <div className="bounce1"></div>
                    <div className="bounce2"></div>
                    <div className="bounce3"></div>
                    <div className="bounce4"></div>
                    <div className="bounce5"></div>
                </div>

                <p>{consolePreview}</p>

                <button onClick={this.handleButton} disabled={!includes(['error', 'success'], this.state.status)}>
                    <Translation domain="taskpopup">Hide Task Popup</Translation>
                </button>

                <a onClick={this.hideConsole} className="hide">
                    <i className="icono-caretRight" />
                    <Translation domain="taskpopup">Hide Console Output</Translation>
                </a>
                <a onClick={this.showConsole} className="show">
                    <i className="icono-caretRight" />
                    <Translation domain="taskpopup">Show Console Output</Translation>
                </a>
                <code ref="consoleOutput" onScroll={this.onConsoleOutputScroll}>{this.state.content.consoleOutput}</code>
            </div>
        );
    }
}

TaskPopupComponent.initialState = {
    show: false,
    showConsole: false,
    positionFixed: false,
    status: 'loading',
    taskId: null,
    updateFrequency: 2000, // every 2 seconds
    content: {
        taskTitle: 'Starting task …',
        consoleOutput: ''
    }
};

export default TaskPopupComponent;
