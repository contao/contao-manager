'use strict';

var Task = function(content, title) {
    this.id = null;
    this.title = title;
    this.content = content;
};

Task.prototype.getContent = function() {
    return this.content;
};

Task.prototype.setContent = function(content) {
    this.content = content;
};

Task.prototype.getTitle = function() {
    return this.title;
};

Task.prototype.setTitle = function(title) {
    this.title = title;
};

Task.prototype.setId = function(id) {
    this.id = id;
};

Task.prototype.getId = function() {
    return this.id;
};

module.exports = Task;
