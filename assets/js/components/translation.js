import React        from 'react';
import { getTranslationForKey, fetchTranslationData } from './../helpers/translate';

class Translation extends React.Component {

    constructor(props) {
        super(props);

        this.componentIsMounted = false;

        this.state = {
            data: {}
        };
    }

    componentDidMount() {

        var self = this;

        this.componentIsMounted = true;

        fetchTranslationData(this.props.domain, this.context.routing.getLanguage())
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

        var label = getTranslationForKey(this.props.children, this.state.data, this.props.placeholders);

        return (
            <span>{label}</span>
        )
    }
}

Translation.contextTypes = {
    routing: React.PropTypes.object
};

export default Translation;
