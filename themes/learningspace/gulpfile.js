const { src, dest, watch } = require('gulp');
const sass = require('gulp-sass');
const browserSync = require('browser-sync').create();

function css() {
    return src('./inc/assets/css/sass/*.scss', { sourcemaps: true })
        .pipe(sass())
        .pipe(dest('./inc/assets/css'), { sourcemaps: true })
        .pipe(browserSync.stream());
}

function browser() {
    browserSync.init({
        proxy: 'learningspace.test',
        files: [
            './**/*.php'
        ]
    });

    watch('./inc/assets/css/sass/**/*.scss', css);
}

exports.css = css;
exports.default = browser;
