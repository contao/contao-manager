import React        from 'react';
import translate    from './../helpers/translate';
import isEqual      from 'lodash/isEqual';


class Translation extends React.Component {

    constructor(props) {
        super(props);

        this.componentIsMounted = false;

        this.state = {
            data: {}
        };
    }

    shouldComponentUpdate(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    }

    componentDidMount() {

        var self = this;

        this.componentIsMounted = true;

        translate.fetchData(this.props.domain, this.context.routing.getLanguage())
            .then(function(response) {
                if (self.componentIsMounted) {
                    self.setState({data: response.body});
                }

                return null;
            });
    }

    componentWillUnmount() {
        this.componentIsMounted = false;
    }

    render() {

        var label = translate.getTranslationForKey(this.props.children, this.state.data, this.props.placeholders);

        return (
            <span>{label}</span>
        )
    }
}

Translation.contextTypes = {
    routing: React.PropTypes.object
};

export default Translation;
