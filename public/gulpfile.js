// 引入 gulp
const gulp = require('gulp');

// 引入组件
const minifyCSS = require('gulp-minify-css')
    uglify = require('gulp-uglify'),    // 混淆
    concat = require('gulp-concat');    // 合并

// 合并，压缩 css、js 文件
gulp.task('css', function () {
    return gulp.src('./css/*.css')
        .pipe(concat('slug-laravel.min.css'))
        .pipe(minifyCSS())
        .pipe(gulp.dest('./static/'))
});
gulp.task('autoload-js', function () {
    return gulp.src([
            "js/jquery/jquery-2.1.1.min.js",
            "js/plugins/jquery-ui/jquery-ui.js",
            "js/bootstrap/bootstrap.min.js",
            "js/plugins/metisMenu/jquery.metisMenu.js",
            "js/plugins/slimscroll/jquery.slimscroll.min.js",
            "js/plugins/pace/pace.min.js",
            "js/inspinia.js",
            "js/angular/angular.min.js",
            "js/angular/angular-sanitize.js",
            "js/plugins/oclazyload/dist/ocLazyLoad.min.js",
            "js/angular-translate/angular-translate.min.js",
            "js/ui-router/angular-ui-router.min.js",
            "js/bootstrap/ui-bootstrap-tpls-1.1.2.min.js",
            "js/plugins/angular-idle/angular-idle.js"])
        .pipe(concat('slug-laravel-autoload.min.js'))
        .pipe(gulp.dest('./static/'))
});
gulp.task('js', function() {
    return gulp.src([
            "js/app.js",
            "js/config.js",
            // "js/controllers.js",
            // "js/translations.js",
            "js/directives.js"
        ])
        .pipe(concat('slug-laravel.min.js'))
        .pipe(uglify({
            mangle: false
        }))
        .pipe(gulp.dest('./static/'))
});

// 默认任务
gulp.task('default', [
    'css', 'autoload-js', 'js'
], function () {
    gulp.watch('./css/*.css', ['css']);
    gulp.watch('./js/*.js', ['js']);
});