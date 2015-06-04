var gulp = require('gulp');
var config = require('../config');

gulp.task('watch', ['phpunit'], function () {
	gulp.watch(config.phpunit.watch, { debounceDelay: 20000 }, ['phpunit']);
});