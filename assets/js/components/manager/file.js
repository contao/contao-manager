'use strict';

const React     = require('react');
const Trappings = require('./trappings.js');

var FileComponent = React.createClass({
    getInitialState: function() {
        return {};
    },

    render: function() {
        return (
            <Trappings>

            <p>Edit content of file "{this.props.fileName}"</p>

            </Trappings>
        );
    }
});

module.exports = FileComponent;
