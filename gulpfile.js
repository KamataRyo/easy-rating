var gulp    = require('gulp');
var coffee  = require('gulp-coffee');
var plumber = require('gulp-plumber');
var notify  = require('gulp-notify');
var sort    = require('gulp-sort');
var gettext = require('gulp-gettext');
var meta    = require('./package.json');

gulp.task('coffee', function(){
    gulp.src('./assets/*.coffee')
        .pipe(plumber({errorHandler: notify.onError('<%= error.message %>')}))
        .pipe(coffee({bare:false}))
        .pipe(gulp.dest('./assets'));
});


gulp.task('po2mo', function(){
    gulp.src('./languages/*.po')
        .pipe(gettext())
        .pipe(gulp.dest('./languages/'));
});

gulp.task('build',['coffee', 'po2mo']);

gulp.task('watch', ['build'], function(){
    gulp.watch(['./assets/*.coffee','./languages/*.po'], ['build']);
});
