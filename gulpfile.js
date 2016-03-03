const gulp          = require('gulp');
const babel         = require('gulp-babel');
const concat        = require('gulp-concat');
const browserify    = require('gulp-browserify');
const watch         = require('gulp-watch');
const sass          = require('gulp-sass');
const sourcemaps    = require('gulp-sourcemaps');
const cleanCSS      = require('gulp-clean-css');
const uglify        = require('gulp-uglify');

var combinedScriptsGlob     = 'assets/js/*.js';
var standaloneScriptsGlob   = 'assets/js/components/*.js';
var combinedStylesGlob      = 'assets/css/*.scss';


// Build javascripts task
gulp.task('scripts', function() {

    // Combined
    gulp.src(combinedScriptsGlob)
        .pipe(babel({
            presets: ['react']
        }))
        .pipe(browserify())
        .pipe(concat('global.js'))
        .pipe(uglify())
        .pipe(gulp.dest('web/js'));

    // Standalone
    gulp.src(standaloneScriptsGlob)
        .pipe(babel({
            presets: ['react']
        }))
        .pipe(browserify())
        .pipe(uglify())
        .pipe(gulp.dest('web/js'));
});

// Build SASS task
gulp.task('sass', function () {
    return gulp.src(combinedStylesGlob)
        .pipe(sourcemaps.init())
        .pipe(sass())
        .pipe(sourcemaps.write())
        .pipe(concat('global.css'))
        .pipe(cleanCSS())
        .pipe(gulp.dest('web/css'));
});

// Build by default
gulp.task('default', ['scripts', 'sass']);

// Watch task
gulp.task('watch', function() {
    gulp.watch(standaloneScriptsGlob, ['scripts']);
    gulp.watch(combinedScriptsGlob, ['scripts']);
    gulp.watch(combinedStylesGlob, ['sass']);
});

