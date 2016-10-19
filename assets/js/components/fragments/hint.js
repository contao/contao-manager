'use strict';

import React from 'react';

var HintComponent = React.createClass({

    render: function() {
        var icon = '';
        var close = '';
        var buttons = [];

        switch (this.props.type) {
            case 'warning':
                icon = (
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.1 71.6">
                        <path d="M79.1 59.6L47 4c-1.4-2.5-4.1-4-6.9-4s-5.5 1.5-6.9 4L1.1 59.6c-1.4 2.5-1.4 5.5 0 8s4.1 4 6.9 4h64.1c2.9 0 5.5-1.5 6.9-4 1.5-2.5 1.5-5.6.1-8zM8 63.6L40 8l32.1 55.5-64.1.1z" />
                        <path d="M36 26.5v16.7c0 2.2 1.8 4 4 4s4-1.8 4-4V26.5c0-2.2-1.8-4-4-4s-4 1.8-4 4z" />
                        <circle cx="40" cy="54.3" r="4" />
                    </svg>
                );
                break;
        }

        if (this.props.close) {
            close = (
                <a href="#" className="close" onClick={this.props.close.action}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31.2 31.2"><path d="M18.4 15.6l12.8 12.7-2.9 2.9-12.7-12.8L2.9 31.2 0 28.3l12.8-12.7L0 2.9 2.9 0l12.7 12.8L28.3 0l2.9 2.9-12.8 12.7z"/></svg>
                    {this.props.close.label}
                </a>
            );
        }

        if (this.props.buttons) {
            this.props.buttons.forEach(function(button) {
                buttons.push(
                    <a href="#" className={button.class} onClick={button.action}>{button.label}</a>
                );
            });
        }

        return (
            <div className={'hint ' + this.props.type}>
                {close}
                {icon}
                <span>{this.props.children}</span>
                {buttons}
            </div>
        );
    }
});

export default HintComponent;