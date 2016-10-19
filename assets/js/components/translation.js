import React        from 'react';
import translate    from './../helpers/translate';
import isEqual      from 'lodash/isEqual';


var Translation = React.createClass({

    componentIsMounted: false,
    contextTypes: {
        routing: React.PropTypes.object
    },

    getInitialState: function() {
        return {
            data: {}
        };
    },

    shouldComponentUpdate: function(nextProps, nextState) {

        return !isEqual(nextProps, this.props) || !isEqual(nextState, this.state);
    },

    componentDidMount: function() {

        var self = this;

        this.componentIsMounted = true;

        translate.fetchData(this.props.domain, this.context.routing.getLanguage())
            .then(function(response) {
                if (self.componentIsMounted) {
                    self.setState({data: response.body});
                }

                return null;
            });
    },

    componentWillUnmount: function() {
        this.componentIsMounted = false;
    },

    render: function() {

        var label = translate.getTranslationForKey(this.props.children, this.state.data, this.props.placeholders);

        return (
            <span>{label}</span>
        )
    }
});

export default Translation;
