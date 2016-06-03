gulp    = require 'gulp'
coffee  = require 'gulp-coffee'
plumber = require 'gulp-plumber'
notify  = require 'gulp-notify'
sass    = require 'gulp-sass'
gettext = require 'gulp-gettext'
meta    = require './package.json'

gulp.task 'coffee', ->
    gulp.src './js/*.coffee'
        .pipe plumber errorHandler: notify.onError '<%= error.message %>'
        .pipe coffee bare:false
        .pipe gulp.dest './js/'


gulp.task 'sass', ->
    gulp.src './css/*.scss'
        .pipe plumber errorHandler: notify.onError '<%= error.message %>'
        .pipe sass()
        .pipe gulp.dest './css/'


gulp.task 'po2mo', ->
    gulp.src './languages/*.po'
        .pipe gettext()
        .pipe gulp.dest './languages/'


gulp.task 'build',['coffee','sass', 'po2mo']

gulp.task 'watch', ['build'], ->
    gulp.watch ['./js/*.coffee', './css/*.scss' , './languages/*.po'], ['build']
