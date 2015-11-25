'use strict';

var gulp = require('gulp'),
// gulp modules
    autoprefixer = require('gulp-autoprefixer'),
    bower = require('gulp-bower'),
    concat = require('gulp-concat'),
    jade = require('gulp-jade'),
    livereload = require('gulp-livereload'),
    minify = require('gulp-minify-css'),
    jsonminify = require('gulp-jsonminify'),
    newer = require('gulp-newer'),
    sass = require('gulp-sass'),
    uglify = require('gulp-uglify'),
    sourcemaps = require('gulp-sourcemaps'),
// native modules
    del = require('del'),
    exec = require('sync-exec'),
    debug = require('gulp-debug'),
    data = require('gulp-data'),
    globby = require('globby'),
    clone = require('clone'),
    cwd = process.cwd();

var out             = process.env.DEST_DIR || cwd + '/src/Resources/.build',
    tensideApi      = process.env.TENSIDE_API || false,
    tensideVersion  = process.env.TENSIDE_VERSION || false;

function getTensideApi() {
    if (!tensideApi) {
        tensideApi = 'window.location.href.substring(0, window.location.href.split(\'#\')[0].lastIndexOf(\'/\'))';
    }

    return tensideApi;
}

function getTensideVersion() {
    if (!tensideVersion) {
        tensideVersion = 'unknown';
    }

    return tensideVersion;
}

var paths = {
    templates: {
        'watch': [
            cwd + '/src/Resources/assets/templates/**/*.jade',
            out + '/js/*.js',
            out + '/css/*.css'
        ],
        'src': cwd + '/src/Resources/assets/templates/**/[^_]*.jade'
    },
    stylesheets: {
        'watch': cwd + '/src/Resources/assets/stylesheets/**/*.scss',
        'src': cwd + '/src/Resources/assets/stylesheets/tenside.scss',
        'loadOrder': [
            'css/tenside*.css'
        ]
    },
    fonts: {
        'src': [
            cwd + '/bower_components/font-awesome/fonts/*'
        ]
    },
    javascripts: {
        'watch': [
            cwd + '/src/Resources/assets/javascripts/*.js'
        ],
        'src': [
            cwd + '/bower_components/angular/angular.js',
            cwd + '/bower_components/angular-ui-router/release/angular-ui-router.js',
            cwd + '/bower_components/angular-translate/angular-translate.js',
            cwd + '/bower_components/angular-translate-loader-static-files/angular-translate-loader-static-files.js',
            cwd + '/bower_components/angular-bootstrap/ui-bootstrap.js',
            cwd + '/bower_components/angular-bootstrap/ui-bootstrap-tpls.js',
            cwd + '/bower_components/ace/build/src/ace.js',
            cwd + '/bower_components/ace/build/src/mode-json.js',
            cwd + '/bower_components/ace/build/src/mode-php.js',
            cwd + '/bower_components/ace/build/src/worker-json.js',
            cwd + '/src/Resources/assets/javascripts/tenside.js', // keep this first, as the others depend on it.
            cwd + '/src/Resources/assets/javascripts/tenside-api.js',
            cwd + '/src/Resources/assets/javascripts/tenside-*.js'
        ],
        'loadOrder': [
            'js/angular.js',
            'js/angular-ui-router.js',
            'js/angular-translate.js',
            'js/angular-translate-loader-static-files.js',
            'js/ui-bootstrap.js',
            'js/ui-bootstrap-tpls.js',
            'js/ace.js',
            'js/mode-json.js',
            'js/mode-php.js',
            'js/worker-json.js',
            'js/tenside.js',
            'js/tenside-*.js'
        ]
    },
    images: {
        'watch': cwd + '/src/Resources/assets/images/*',
        'src': cwd + '/src/Resources/assets/images/*'
    },
    localization: {
        'watch': cwd + '/src/Resources/assets/l10n/*',
        'src': cwd + '/src/Resources/assets/l10n/*'
    }
};

var variables = {
    'stylesheets': ['css/tenside.css'],
    'javascripts': ['js/tenside.js'],
    'app': {
        'tensideApi': getTensideApi(),
        'tensideVersion': getTensideVersion()
    }
};

var globVariables = function(variables) {
    return data(
        function() {
            var data = variables ? clone(variables) : {
                'stylesheets': paths.stylesheets.loadOrder,
                'javascripts': paths.javascripts.loadOrder
            };
            data.stylesheets = globby.sync(data.stylesheets, {cwd: out});
            data.javascripts = globby.sync(data.javascripts, {cwd: out});

            data.app = {
                'tensideApi': getTensideApi(),
                'tensideVersion': getTensideVersion()
            };

            return data;
        }
    );
};

var globJsSource = function(javascripts) {
    var myScripts = javascripts || paths.javascripts.src;
    console.log(cwd, myScripts);
    myScripts = globby.sync(myScripts, {cwd: cwd});
    console.log(myScripts);

    return myScripts
};

/**
 * Installation tasks
 */
gulp.task('install-bower', function () {
    return bower();
});

gulp.task('install-ace', ['install-bower'], function () {
    // Ensure npm install is valid.
    var result = exec('npm install', {cwd: process.cwd() + '/bower_components/ace'});
    if (0 !== result.status) {
        console.log(result.stdout);
        console.log(result.stderr);
    }
    // Remove built files and rebuild then.
    del(['bower_components/ace/build'], {force: true}, function() {
        var result = exec(
            'node Makefile.dryice.js --s --target ./build minimal',
            {cwd: process.cwd() + '/bower_components/ace'}
        );
        if (0 !== result.status) {
            console.log(result.stdout);
            console.log(result.stderr);
        }
    });
});

gulp.task('install', ['install-bower', 'install-ace']);

/**
 * Build templates tasks
 */
gulp.task('clean-templates', function (cb) {
    del([out + '/*.html'], {force: true}, cb);
});

gulp.task('build-templates', ['clean-templates', 'build-stylesheets', 'build-javascripts'], function () {
    return gulp.src(paths.templates.src)
        .pipe(globVariables())
        .pipe(jade())
        .pipe(debug({title: 'templates:'}))
        .pipe(gulp.dest(out));
});

gulp.task('watch-templates', [], function () {
    return gulp.src(paths.templates.src)
        .pipe(globVariables())
        .pipe(jade())
        .pipe(debug({title: 'templates:'}))
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
    return gulp.src(globJsSource(paths.javascripts.src))
        .pipe(debug({title: 'javascript in:'}))
        .pipe(sourcemaps.init())
        .pipe(uglify())
        .pipe(concat('tenside.js'))
        .pipe(sourcemaps.write('.'))
        .pipe(debug({title: 'javascript:'}))
        .pipe(gulp.dest(out + '/js'));
});

gulp.task('watch-javascripts', [], function () {
    return gulp.src(globJsSource(paths.javascripts.src))
        .pipe(sourcemaps.init())
        .pipe(sourcemaps.write('.'))
        .pipe(debug({title: 'javascript:'}))
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
 * Build localization tasks.
 */
gulp.task('clean-localization', function (cb) {
    del([out + '/l10n'], {force: true}, cb);
});

gulp.task('build-localization', ['clean-localization'], function () {
    return gulp.src(globJsSource(paths.localization.src))
        .pipe(sourcemaps.init())
        .pipe(jsonminify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(out + '/l10n'));
});

gulp.task('watch-localization', [], function () {
    return gulp.src(globJsSource(paths.localization.src))
        .pipe(sourcemaps.init())
        .pipe(jsonminify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(out + '/l10n'));
});

/**
 * Global build tasks
 */
gulp.task('clean', function (cb) {
    del([out], {force: true}, cb);
});

gulp.task('build', ['build-templates', 'build-images', 'build-fonts', 'build-localization']);

gulp.task('watch', function () {
    livereload.listen();
    gulp.watch(paths.templates.watch, ['watch-templates']);
    gulp.watch(paths.stylesheets.watch, ['watch-stylesheets']);
    gulp.watch(paths.javascripts.watch, ['watch-javascripts']);
    gulp.watch(paths.images.watch, ['watch-images']);
    gulp.watch(paths.fonts.watch, ['watch-fonts']);
    gulp.watch(paths.localization.watch, ['watch-localization']);
    gulp.watch(out + '/**/*').on('change', livereload.changed);
});

gulp.task('default', ['watch', 'watch-templates', 'watch-stylesheets', 'watch-javascripts', 'watch-images', 'watch-fonts', 'watch-localization']);
