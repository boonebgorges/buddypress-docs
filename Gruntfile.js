'use strict';
module.exports = function(grunt) {

	// load all grunt tasks matching the `grunt-*` pattern
	require('load-grunt-tasks')(grunt);

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		// Watch for changes and trigger less, jshint, uglify and livereload
		watch: {
			options: {
				livereload: true
			},
			scripts: {
				files: ['public/js/src/*.js'],
				tasks: ['jshint', 'uglify']
			},
			styles: {
				files: ['includes/scss/*.scss'],
				tasks: ['sass', 'postcss']
			}
		},

		sass: {
			options: {
				sourceMap: true
			},
			dist: {
				files: [{
					// Set to true for recursive search
					expand: true,
					cwd: 'includes/scss/',
					src: ['screen.scss', 'folders.scss'],
					dest: 'includes/css/',
					ext: '.css'
				}]
			}
		},

		// Generate RTL stylesheet.
		rtlcss: {
			basic: {
				options: {
					opts: {
						processUrls: false,
						autoRename: false,
						clean: false
					},
					saveUnmodified: true
				},
				expand: true,
				ext: '-rtl.css',
				cwd: 'includes/css/',
				src: ['**/*.css'],
				dest: 'includes/css-rtl/'
			}
		},

		// PostCSS handles post-processing on CSS files. Used here to autoprefix and minify.
		postcss: {
			options: {
				processors: [
					require('autoprefixer')(),
					require('cssnano')()
				]
			},
			dist: {
				src: [
					'includes/css/*.css',
					// 'admin/css/*.css'
				]
			}
		},

		// JavaScript linting with jshint
		jshint: {
			all: [
				// 'public/js/src/*.js'
				]
		},

		// Uglify to concat, minify, and make source maps
		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' +
						'<%= grunt.template.today("yyyy-mm-dd") %> */'
			},
			common: {
				files: {
					// 'public/js/public.min.js': ['public/js/src/*.js']
				}
			}
		},

		// Create language translation source files.
		makepot: {
			target: {
				options: {
					cwd: '.',                          // Directory of files to internationalize.
					domainPath: '.',                   // Where to save the POT file.
					exclude: [],                      // List of files or directories to ignore.
					include: [],                      // List of files or directories to include.
					mainFile: 'loader.php',
					potComments: '',                  // The copyright at the beginning of the POT file.
					potFilename: 'languages/buddypress-docs.pot', // Name of the POT file.
					potHeaders: {
						poedit: true,                 // Includes common Poedit headers.
						'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
					},                                // Headers to add to the generated POT file.
					processPot: null,                 // A callback function for manipulating the POT file.
					type: 'wp-plugin',                // Type of project (wp-plugin or wp-theme).
					updateTimestamp: true,            // Whether the POT-Creation-Date should be updated without other changes.
					updatePoFiles: false              // Whether to update PO files in the same directory as the POT file.
				}
			}
		}

	});

	// Register tasks
	// Typical run, cleans up css and js, starts a watch task.
	grunt.registerTask('default', ['sass', 'postcss', 'watch']);
	// Before releasing a build, also create the RTL CSS stylesheet and the language file.
	grunt.registerTask('release', ['sass', 'postcss', 'rtlcss', 'makepot']);

};
