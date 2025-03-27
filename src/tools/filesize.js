export default (bytes) => {
    let sizes = ['KB', 'MB', 'GB'];
    let size = 'Bytes';

    while (bytes > 1024) {
        bytes = bytes / 1024;
        size = sizes.shift();
    }

    return `${Math.round(bytes * 100) / 100} ${size}`;
};
