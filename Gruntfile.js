module.exports = function (grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		jshint: {
			files: ['Gruntfile.js', 'js/*.js', '!**/*.min.js'],
			options: {
				globals: {
					jQuery: true
				}
			}
		},
		uglify: {
			build: {
				files: [{
					expand: true,
					cwd: 'js',
					src: ['*.js', '!*.min.js'],
					dest: 'js/',
					ext: '.min.js'
				}]
			}
		},
		less: {
			development: {
				options: {
					paths: ["css/"],
					plugins: [new (require('less-plugin-autoprefix'))({browsers: ["> 1%"]})]
				},
				files: [{
					expand: true,
					cwd: 'css/',
					src: '*.less',
					dest: 'css/',
					ext: '.css'
				}]
			}
		},
		cssmin: {
			target: {
				files: [{
					expand: true,
					cwd: 'css',
					src: ['*.css', '!*.min.css'],
					dest: 'css',
					ext: '.min.css'
				}]
			}
		},
		makepot: {
			target: {
				options: {
					include: [
						'nicebackgrounds.php',
						'includes/admin.php',
						'includes/ajax.php',
						'includes/collection.php',
						'includes/data.php',
						'includes/display.php',
						'includes/markup.php',
						'includes/set.php',
						'includes/utils.php',
					],
					type: 'wp-plugin'
				}
			}
		},
		watch: {
			css: {
				files: ['css/*'],
				tasks: ['css', 'cssmin']
			},
			js: {
				files: ['js/*'],
				tasks: ['jshint', 'uglify']
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-wp-i18n');
	grunt.loadNpmTasks('grunt-contrib-watch');

	// Tasks.
	grunt.registerTask('default', ['less', 'cssmin', 'jshint', 'uglify', 'makepot']);
	grunt.registerTask('watch', ['watch']);

};