import React        from 'react';
import Promise      from 'bluebird';
import Trappings    from '../trappings/boxed';
import Loader       from '../fragments/loader';
import TensideState from '../../helpers/tenside-state';
import isEqual      from 'lodash/isEqual';
import forIn        from 'lodash/forIn';

class SelfTestComponent extends React.Component {

    constructor(props) {
        super(props);

        this.statePromise = Promise.resolve();

        this.state = {
            data: []
        };
    }

    componentDidMount() {
        var self = this;
        this.statePromise = TensideState.getSelfTest()
            .then(function(result) {
                var sortRef = {
                    FAIL: 3,
                    WARNING: 2,
                    SUCCESS: 1
                };
                result.sort(function(a, b) { return sortRef[b.state] - sortRef[a.state]; });

                self.setState({data: result});

                return null;
            });
    }

    componentWillUnmount() {
        this.statePromise.cancel();
    }

    render() {
        var tests = [];

        forIn(this.state.data, function(data, key) {
            tests.push(<TestComponent key={key} data={data} />);
        });

        return (
            <Trappings wide={true} mainClass="selftest">
                <section>
                    <h1>Contao Manager – Self-Test</h1>
                    {tests.length > 0 ? tests : <Loader>Loading test results …</Loader>}
                </section>
            </Trappings>
        );
    }
}


class TestComponent extends React.Component {

    render() {

        var data = this.props.data;

        return (
            <div className={'test clearfix ' + data.state.toLowerCase() + ' ' + data.name}>
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0px" y="0px" viewBox="0 0 79.536 79.536" xmlSpace="preserve"><g><path d="M39.769,0C17.8,0,0,17.8,0,39.768c0,21.965,17.8,39.768,39.769,39.768 c21.965,0,39.768-17.803,39.768-39.768C79.536,17.8,61.733,0,39.769,0z M34.142,58.513L15.397,39.768l7.498-7.498l11.247,11.247 l22.497-22.493l7.498,7.498L34.142,58.513z" /></g></svg>
                <div className="message">{data.message}</div>
                <div className="explain">{data.explain}</div>
            </div>
        );
    }
}

export default SelfTestComponent;
