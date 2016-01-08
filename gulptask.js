var rename = require('gulp-rename'),
    size = require('gulp-size'),
    concat = require('gulp-concat'),
    sourcemaps = require('gulp-sourcemaps'),
    replace = require('gulp-replace');

module.exports = function(gulp, BUILD_PATH)
{
    // move css to scss file in order to be included in scss
    gulp.task('module-swagger-server:sass', function () {
        gulp.src("./bower_components/animate.css/animate.css")
            .pipe(rename("_css.animate.scss"))
            .pipe(gulp.dest("./bower_components/animate.css"));

        gulp.src("./bower_components/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.css")
            .pipe(rename("_css.bootstrap-switch.scss"))
            .pipe(gulp.dest("./bower_components/bootstrap-switch/dist/css/bootstrap3"));

        gulp.src("./bower_components/checkbox3/dist/checkbox3.css")
            .pipe(rename("_css.checkbox3.scss"))
            .pipe(gulp.dest("./bower_components/checkbox3/dist"));

        gulp.src("./bower_components/datatables/media/css/jquery.dataTables.css")
            .pipe(rename("_css.jquery.dataTables.scss"))
            .pipe(gulp.dest("./bower_components/datatables/media/css"));

        gulp.src("./bower_components/datatables/media/css/dataTables.bootstrap.css")
            .pipe(rename("_css.dataTables.bootstrap.scss"))
            .pipe(gulp.dest("./bower_components/datatables/media/css"));

        gulp.src("./bower_components/highlightjs/styles/darkula.css")
            .pipe(rename("_css.darkula.scss"))
            .pipe(gulp.dest("./bower_components/highlightjs/styles"));

        gulp.src(__dirname + "/assets/swaggerdocs.scss")
            .pipe(replace('../bower_components/', '../../../bower_components/'))
            .pipe(gulp.dest("./assets/modules/swagger-server"));

        gulp.src([
            './bower_components/jquery/dist/jquery.js',
            './bower_components/bootstrap/bootstrap.min.js',
            './bower_components/bootstrap-switch/dist/bootstrap-switch.js',
            './bower_components/select2/dist/js/select2.full.min.js',
            // './bower_components/flat-admin-bootstrap-templates/dist/js/*.js',
            './bower_components/highlightjs/highlight.pack.js',
            __dirname + "/assets/swaggerdocs.js"
        ])
            .pipe(sourcemaps.init())
            .pipe(concat("swaggerdocs.js"))
            .pipe(sourcemaps.write())
            .pipe(gulp.dest("./assets/modules/swagger-server"))
            .pipe(size({title: 'js'}));
    });

    // copy fonts on public path
    gulp.task('module-swagger-server:font', function () {
        return gulp.src('./bower_components/font-awesome/fonts/*')
            .pipe(gulp.dest(BUILD_PATH + "/fonts"))
            .pipe(size({title: 'font'}));
    });


    gulp.task("module-swagger-server", ["module-swagger-server:sass", "module-swagger-server:font"])

};


