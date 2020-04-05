//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

const cssnano = require('cssnano');
const gulp    = require('gulp');
const concat  = require('gulp-concat');
const gulpif  = require('gulp-if');
const postcss = require('gulp-postcss');
const rename  = require('gulp-rename');
const sass    = require('gulp-sass');
const uglify  = require('gulp-uglify');
const yargs   = require('yargs');

/**
 * Installs vendor fonts to the "public/fonts" folder.
 */
const vendorFonts = () => {

    const files = [
        'node_modules/font-awesome/fonts/*',
    ];

    return gulp.src(files)
        .pipe(gulp.dest('public/fonts/'));
};

/**
 * Installs vendor CSS files as one combined "public/css/vendor.css" asset.
 */
const vendorStyles = () => {

    const files = [
        'node_modules/normalize.css/normalize.css',
        'node_modules/font-awesome/css/font-awesome.css',
    ];

    return gulp.src(files)
        .pipe(gulpif(yargs.argv.prod, postcss([cssnano()])))
        .pipe(concat('vendor.css'))
        .pipe(gulp.dest('public/css/'));
};

/**
 * Installs vendor JavaScript files as one combined "public/js/vendor.js" asset.
 */
const vendorScripts = () => {

    const files = [
        yargs.argv.prod ? 'node_modules/vue/dist/vue.min.js'   : 'node_modules/vue/dist/vue.js',
        yargs.argv.prod ? 'node_modules/vuex/dist/vuex.min.js' : 'node_modules/vuex/dist/vuex.js',
        'node_modules/axios/dist/axios.js',
        'node_modules/@babel/polyfill/dist/polyfill.js',
    ];

    return gulp.src(files)
        .pipe(gulpif(yargs.argv.prod, uglify()))
        .pipe(concat('vendor.js'))
        .pipe(gulp.dest('public/js/'));
};

/**
 * Installs eTraxis CSS files as one combined "public/css/etraxis.css" asset.
 */
const etraxisStyles = () => {

    return gulp.src('assets/scss/themes/*.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulpif(yargs.argv.prod, postcss([cssnano()])))
        .pipe(rename(path => {
            path.basename = `etraxis-${path.basename}`;
            path.extname  = '.css';
        }))
        .pipe(gulp.dest('public/css/'));
};

/**
 * Watches for changes in source files and updates affected assets when necessary.
 */
if (yargs.argv.watch || yargs.argv.w) {
    gulp.watch('assets/scss/**/*.scss', gulp.parallel(etraxisStyles));
}

/**
 * Performs all installation tasks in one.
 */
gulp.task('default', gulp.series(gulp.parallel(
    vendorFonts,            // install vendor fonts to the "public/fonts" folder
    vendorStyles,           // install vendor CSS files as one combined "public/css/vendor.css" asset
    vendorScripts,          // install vendor JavaScript files as one combined "public/js/vendor.js" asset
    etraxisStyles           // install eTraxis CSS files as one combined "public/css/etraxis.css" asset
)));
