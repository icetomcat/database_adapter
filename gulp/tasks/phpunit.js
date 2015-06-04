var gulp = require('gulp');
var phpunit = require('gulp-phpunit');
var config = require('../config').phpunit;
var handleErrors = require('../util/handleErrors');

gulp.task('phpunit', function () {
	var options = {debug: false, notify: false, stderr: true};
	gulp.src(config.src)
			.pipe(phpunit('', options))
			.on('error', function(){});
});
