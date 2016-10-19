import React       from 'react';
import Trappings   from '../trappings/main';
import Codemirror  from 'react-codemirror';
import request     from '../../helpers/request';
import Translation from '../translation';
import assign      from 'lodash/assign';
import forEach     from 'lodash/forEach';
import isEqual     from 'lodash/isEqual';

function Message(props) {

    var line = '';
    var msg = props.msg;

    if (props.line > 0) {
        line = <Translation domain="file" placeholders={{ line: props.line }}>Line %line%</Translation>;
        msg = ': ' + msg;
    }

    return (
        <p className={props.type}>{line}{msg}</p>
    )
}

class FileComponent extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            code: '',
            status: 'OK',
            errors: [],
            warnings: []
        };
    }

    componentDidMount() {
        this.loadInitial(this.props.apiEndpoint);
    }

    componentWillReceiveProps(nextProps) {
        this.loadInitial(nextProps.apiEndpoint);
    }

    loadInitial(endPoint) {
        var self = this;
        request.createRequest(endPoint)
            .then(function (response) {
                self.setState({code: response.rawRequest.responseText});

            }).catch(function (err) {
                // @todo
            });
    }

    updateContent(content) {
        var self = this;
        self.setState({code: content});

        request.createRequest(this.props.apiEndpoint, {
                method: 'PUT',
                body: content
            })
            .then(function (response) {

                var newState = {};
                newState['status'] = response.body.status;

                if (undefined !== response.body.warnings) {
                    newState['warnings'] = response.body.warnings;
                } else {
                    newState['warnings'] = [];
                }

                if (undefined !== response.body.errors) {
                    newState['errors'] = response.body.errors;
                } else {
                    newState['errors'] = [];
                }

                self.setState(newState);

            })
            .catch(function (err) {
                // @todo
            });
    }

    render() {

        var messages = [];
        var options = {
            lineNumbers: true,
            autofocus: true,
            dragDrop: false
        };
        options = assign(options, this.props.options);

        if ('OK' === this.state.status) {
            var msg = <Translation domain="file">The file is OK!</Translation>;
            messages.push(<Message key="ok" type="ok" msg={msg}/>);
        }

        if (this.state.warnings.length > 0) {
            forEach(this.state.warnings, function(value, key) {
                messages.push(<Message key={'warning' + key} type="warning" msg={value.msg} line={value.line} />);
            });
        }

        if (this.state.errors.length > 0) {
            forEach(this.state.errors, function(value, key) {
                messages.push(<Message key={'error' + key} type="error" msg={value.msg} line={value.line} />);
            });
        }

        return (
            <Trappings>
                <div className="messages">{messages}</div>
                <Codemirror value={this.state.code} onChange={this.updateContent} options={options} />
            </Trappings>
        );
    }
}

export default FileComponent;
