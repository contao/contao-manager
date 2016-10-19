import React from 'react';

export default function(props) {
    return (
        <div id="content" className={"table" + (props.wide ? ' wide' : '')}>
            <div className="cell">
                <main className={props.mainClass ? ' '+props.mainClass : ''}>
                    {props.children}

                    <footer>
                        Contao Manager v1.0
                        <a href="https://manager.contao.org" target="_blank" className="support">Support</a>
                    </footer>
                </main>
            </div>
        </div>
    );
}
