'use strict';

var gulp = require('gulp'),
// gulp modules
    autoprefixer = require('gulp-autoprefixer'),
    bower = require('gulp-bower'),
    concat = require('gulp-concat'),
    imagemin = require('gulp-imagemin'),
    jade = require('gulp-jade'),
    livereload = require('gulp-livereload'),
    minify = require('gulp-minify-css'),
    newer = require('gulp-newer'),
    run = require('gulp-run'),
    sass = require('gulp-sass'),
    uglify = require('gulp-uglify'),
    sourcemaps = require('gulp-sourcemaps'),
// image optimizers
    optipng = require('imagemin-optipng'),
    svgo = require('imagemin-svgo'),
// native modules
    del = require('del'),
    sh = require('sync-exec'),
    debug = require('gulp-debug');

var out = process.env.DEST_DIR || 'build',
    tensideApi      = process.env.TENSIDE_API || false,
    tensideVersion  = process.env.TENSIDE_VERSION || false,
    composerVersion = process.env.COMPOSER_VERSION || false;

function getTensideApi() {
    if (!tensideApi) {
        tensideApi = 'window.location.href.split(\'#\')[0]';
    }

    return tensideApi;
}

function getTensideVersion() {
    if (!tensideVersion) {
        var result = sh('git describe --always --abbrev=8');
        tensideVersion = result.stdout || 'unknown';
    }

    return tensideVersion;
}

function getComposerVersion() {
    if (!composerVersion) {
        var result = sh('git describe --always --abbrev=8');
        composerVersion = result.stdout || 'unknown';
    }

    return composerVersion;
}

var paths = {
    templates: {
        'watch': 'assets/templates/**/*.jade',
        'src': 'assets/templates/**/[^_]*.jade'
    },
    stylesheets: {
        'watch': 'assets/stylesheets/**/*.scss',
        'src': 'assets/stylesheets/tenside.scss'
    },
    fonts: {
        'src': [
            'bower_components/font-awesome/fonts/*'
        ]
    },
    javascripts: {
        'watch': [
            'assets/javascripts/*.js'
        ],
        'src': [
            'bower_components/jquery/dist/jquery.js',
            'bower_components/bootstrap-sass-official/assets/javascripts/bootstrap.js',
            'bower_components/angular/angular.js',
            'bower_components/angular-route/angular-route.js',
            'bower_components/angular-bootstrap/ui-bootstrap.js',
            'bower_components/angular-bootstrap/ui-bootstrap-tpls.js',
            'bower_components/ace/build/src/ace.js',
            'bower_components/ace/build/src/mode-json.js',
            'bower_components/ace/build/src/worker-json.js',
            'assets/javascripts/*.js'
        ]
    },
    images: {
        'watch': 'assets/images/*',
        'src': 'assets/images/*'
    }
};

/**
 * Installation tasks
 */
gulp.task('install-bower', function () {
    return bower();
});

gulp.task('install-ace', ['install-bower'], function () {
    run('npm install', {cwd: process.cwd() + '/bower_components/ace'}).exec(function() {
        del(['bower_components/ace/build'], {force: true}, function() {
            run('node Makefile.dryice.js --s --target ./build minimal', {cwd: process.cwd() + '/bower_components/ace'}).exec();
        });
    });
});

gulp.task('install', ['install-bower', 'install-ace']);

/**
 * Build templates tasks
 */
gulp.task('clean-templates', function (cb) {
    del([out + '/*.html'], {force: true}, cb);
});

gulp.task('build-templates', ['clean-templates'], function () {
    var variables = {
        'stylesheets': ['css/tenside.css'],
        'javascripts': ['js/tenside.js'],
        'app': {
            'tensideVersion': getTensideVersion(),
            'composerVersion': getComposerVersion()
        }
    };

    return gulp.src(paths.templates.src)
        .pipe(jade({ locals: variables }))
        .pipe(debug({title: 'templates:'}))
        .pipe(gulp.dest(out));
});

gulp.task('watch-templates', [], function () {
    var variables = {
        'stylesheets': ['css/tenside.css'],
        'javascripts': [
            'js/jquery.js',
            'js/angular.js',
            'js/angular-route.js',
            'js/ui-bootstrap.js',
            'js/ui-bootstrap-tpls.js',
            'js/bootstrap.js',
            'js/ace.js',
            'js/mode-json.js',
            'js/worker-json.js',
            'js/user-session.js',
            'js/tenside.js',
            'js/tenside-editor.js',
            'js/tenside-search.js'
        ],
        'app': {
            'tensideApi': getTensideApi(),
            'tensideVersion': getTensideVersion(),
            'composerVersion': getComposerVersion()
        }
    };

    return gulp.src(paths.templates.src)
        .pipe(jade({ locals: variables }))
        .pipe(gulp.dest(out));
});

/**
 * Build stylesheets tasks
 */
gulp.task('clean-stylesheets', function (cb) {
    del([out + '/css'], {force: true}, cb);
});

gulp.task('build-stylesheets', ['clean-stylesheets'], function () {
    return gulp.src(paths.stylesheets.src)
        .pipe(sourcemaps.init())
        .pipe(sass())
        .pipe(autoprefixer({
            browsers: ['last 2 versions'],
            cascade: false
        }))
        .pipe(minify())
        .pipe(concat('tenside.css'))
        .pipe(sourcemaps.write('.'))
        .pipe(debug({title: 'css:'}))
        .pipe(gulp.dest(out + '/css'));
});

gulp.task('watch-stylesheets', [], function () {
    return gulp.src(paths.stylesheets.src)
        .pipe(sourcemaps.init())
        .pipe(sass())
        .pipe(autoprefixer({
            browsers: ['last 2 versions'],
            cascade: false
        }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(out + '/css'));
});

/**
 * Build javascripts tasks
 */
gulp.task('clean-javascripts', function (cb) {
    del([out + '/js'], {force: true}, cb);
});

gulp.task('build-javascripts', ['clean-javascripts'], function () {
    return gulp.src(paths.javascripts.src)
        .pipe(debug({title: 'javascript in:'}))
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('tenside.js'))
        .pipe(sourcemaps.write('.'))
        .pipe(debug({title: 'javascript:'}))
        .pipe(gulp.dest(out + '/js'));
});

gulp.task('watch-javascripts', [], function () {
    return gulp.src(paths.javascripts.src)
        .pipe(sourcemaps.init())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(out + '/js'));
});

/**
 * Build images tasks
 */
gulp.task('clean-images', function (cb) {
    del([out + '/img'], {force: true}, cb);
});

gulp.task('build-images', ['clean-images'], function () {
    return gulp.src(paths.images.src)
        .pipe(imagemin({
            use: [optipng(), svgo()]
        }))
        .pipe(debug({title: 'image:'}))
        .pipe(gulp.dest(out + '/img'));
});

gulp.task('watch-images', [], function () {
    return gulp.src(paths.images.src)
        .pipe(newer(out + '/img'))
        .pipe(gulp.dest(out + '/img'));
});

/**
 * Build fonts task
 */

gulp.task('clean-fonts', function (cb) {
    del([out + '/fonts'], {force: true}, cb);
});

gulp.task('build-fonts', ['clean-fonts'], function () {
    return gulp.src(paths.fonts.src)
        .pipe(debug({title: 'fonts:'}))
        .pipe(gulp.dest(out + '/fonts'));
});

gulp.task('watch-fonts', [], function () {
    return gulp.src(paths.fonts.src)
        .pipe(newer(out + '/fonts'))
        .pipe(gulp.dest(out + '/fonts'));
});

/**
 * Global build tasks
 */
gulp.task('clean', function (cb) {
    del([out], {force: true}, cb);
});

gulp.task('build', ['clean', 'build-templates', 'build-stylesheets', 'build-javascripts', 'build-images', 'build-fonts']);

gulp.task('watch', function () {
    livereload.listen();
    gulp.watch(paths.templates.watch, ['watch-templates']);
    gulp.watch(paths.stylesheets.watch, ['watch-stylesheets']);
    gulp.watch(paths.javascripts.watch, ['watch-javascripts']);
    gulp.watch(paths.images.watch, ['watch-images']);
    gulp.watch(paths.fonts.watch, ['watch-fonts']);
    gulp.watch(out + '/**/*').on('change', livereload.changed);
});

gulp.task('default', ['watch', 'watch-templates', 'watch-stylesheets', 'watch-javascripts', 'watch-images', 'watch-fonts']);
