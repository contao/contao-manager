const gulp          = require('gulp');
const babel         = require('gulp-babel');
const concat        = require('gulp-concat');
const browserify    = require('gulp-browserify');
const watch         = require('gulp-watch');
const sass          = require('gulp-sass');
const sourcemaps    = require('gulp-sourcemaps');
const cleanCSS      = require('gulp-clean-css');
const uglify        = require('gulp-uglify');

var scriptsGlob  = 'assets/js/*.js';
var stylesGlob   = 'assets/css/*.scss';


// Build javascripts task
gulp.task('scripts', function() {
    gulp.src(scriptsGlob)
        .pipe(babel({
            presets: ['react']
        }))
        .pipe(browserify())
        .pipe(concat('scripts.js'))
        .pipe(uglify())
        .pipe(gulp.dest('web'));
});

// Build SASS task
gulp.task('sass', function () {
    return gulp.src(stylesGlob)
        .pipe(sourcemaps.init())
        .pipe(sass())
        .pipe(sourcemaps.write())
        .pipe(concat('styles.css'))
        .pipe(cleanCSS())
        .pipe(gulp.dest('web'));
});

// Build by default
gulp.task('default', ['scripts', 'sass']);

// Watch task
gulp.task('watch', function() {
    gulp.watch(scriptsGlob, ['scripts']);
    gulp.watch(stylesGlob, ['sass']);
});

