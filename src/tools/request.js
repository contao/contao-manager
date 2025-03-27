import axios from 'axios';
import store from '../store';

const request = new Proxy(axios, {
    get(target, prop) {
        const methods = {
            request: 1,
            get: 2,
            delete: 2,
            head: 2,
            options: 2,
            post: 3,
            put: 3,
            patch: 3,
        };

        if (!Object.keys(methods).includes(prop)) {
            return Reflect.get(...arguments);
        }

        return async (...args) => {
            let response;
            let handler = {};

            if (args.length > methods[prop]) {
                handler = args.pop();
            }

            try {
                response = await target[prop](...args);
            } catch (err) {
                response = err.response;
            }

            if (handler[response.status]) {
                return handler[response.status](response);
            } else if (response.status >= 400 && response.status <= 599) {
                store.commit('apiError', response);
            }

            return response;
        };
    },
});

export default request;
