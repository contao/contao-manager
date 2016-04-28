'use strict';

const browserify    = require('browserify');
const gulp          = require('gulp');
const source        = require('vinyl-source-stream');
const buffer        = require('vinyl-buffer');
const gutil         = require('gulp-util');
const uglify        = require('gulp-uglify');
const sourcemaps    = require('gulp-sourcemaps');
const rename        = require('gulp-rename');
const sass          = require('gulp-sass');
const cleanCSS      = require('gulp-clean-css');
const concat        = require('gulp-concat');
const composer      = require("gulp-composer");
const install       = require("gulp-install");
const runSequence   = require('run-sequence');

const production    = !!gutil.env.production;

// Composer install
gulp.task('composer-install', function() {
    return composer();
});

// npm install
gulp.task('npm-install', function() {
    return gulp.src('./package.json')
        .pipe(install());
});

// Build bundle.js
gulp.task('scripts', function () {
    return browserify({
            entries: './assets/js/index.js',
            debug: !production
        })
        .transform('babelify', {presets: ['react', 'es2015']})
        .bundle()
        .pipe(source('./assets/js/index.js'))
        .pipe(buffer())
        .pipe(production ? uglify() : gutil.noop())
        .pipe(rename('bundle.js'))
            .on('error', gutil.log)
        .pipe(production ? sourcemaps.write() : gutil.noop())
        .pipe(gulp.dest('./web/js'));
});


// Build bundle.css
gulp.task('sass', function () {
    return gulp.src('assets/css/bundle.scss')
        .pipe(production ? sourcemaps.init() : gutil.noop())
        .pipe(sass())
        .pipe(production ? sourcemaps.write() : gutil.noop())
        .pipe(concat('bundle.css'))
        .pipe(production ? cleanCSS() : gutil.noop())
        .pipe(gulp.dest('web/css'));
});

// Build by default
gulp.task('default', ['scripts', 'sass']);

// Watch task
gulp.task('watch', function() {
    gulp.watch(['./assets/js/app.js', './assets/js/**/*.js'], ['scripts']);
    gulp.watch('assets/css/*.scss', ['sass']);
});

// Build task
gulp.task('build', ['scripts', 'sass']);

// Build and watch task
gulp.task('build:watch', ['build', 'watch']);

// Update task
gulp.task('update', function() {
    runSequence(
        'composer-install',
        'npm-install',
        'build'
    );
});
