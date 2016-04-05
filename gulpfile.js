'use strict';

var browserify    = require('browserify');
var gulp          = require('gulp');
var source        = require('vinyl-source-stream');
var buffer        = require('vinyl-buffer');
var gutil         = require('gulp-util');
var uglify        = require('gulp-uglify');
var sourcemaps    = require('gulp-sourcemaps');
var rename        = require('gulp-rename');
var sass          = require('gulp-sass');
var cleanCSS      = require('gulp-clean-css');
var concat        = require('gulp-concat');

var production      = !!gutil.env.production;

// Build bundle.js
gulp.task('scripts', function () {
    return browserify({
            entries: './assets/js/index.js',
            debug: true
        })
        .transform('babelify', {presets: ['react', 'es2015']})
        .bundle()
        .pipe(source('./assets/js/index.js'))
        .pipe(buffer())
        .pipe(production ? sourcemaps.init({loadMaps: true}) : gutil.noop())
        .pipe(production ? uglify() : gutil.noop())
        .pipe(rename('bundle.js'))
            .on('error', gutil.log)
        .pipe(production ? sourcemaps.write() : gutil.noop())
        .pipe(gulp.dest('./web/js'));
});


// Build bundle.css task
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

// Build and watch task
gulp.task('build:watch', ['scripts', 'sass', 'watch']);
