import React        from 'react';
import Trappings    from '../trappings/main';
import * as taskmanager from '../../helpers/taskmanager';

class MaintenanceComponent extends React.Component {

    handleCacheClear() {
        var task = {'type': 'contao-cache-clear'};
        taskmanager.addTask(task).then(taskmanager.runNextTask);
    }

    render() {
        return (
            <Trappings>
                <h2>Cache clear and warmup</h2>
                <p>This action will clear the cache for both, the development (dev) as well as the production (prod) environment. It will automatically warm it up again after it has been cleared.</p>
                <button onClick={this.handleCacheClear}>Execute</button>
            </Trappings>
        );
    }
}

export default MaintenanceComponent;
