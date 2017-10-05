module.exports = function(grunt) {

    // Paths you can change:
    var siteConfig = {
        outputFolder: 'public/',            // output from build processes
        buildFolder: 'build/',
        harpCompileFolder: 'static/',
        siteURL: "http://localhost:9000/"       // used for YSlow, validation, uncss
    };

    var allTemplates = ["<%= config.outputFolder %>**/*.html", "<%= config.outputFolder %>**/*.php", "<%= config.outputFolder %>**/*.ejs"];

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        config: siteConfig,
        concat: {
            css: {
                src: [
                    '<%= config.buildFolder %>css/*'
                ],
                dest: '<%= config.outputFolder %>css/styles.css'
            },
            js: {
                separator: ";",
                src: [
                    '<%= config.buildFolder %>js/*'
                ],
                dest: '<%= config.outputFolder %>js/all.js'
            }
        },
        cssmin: {
            css: {
                src: '<%= config.outputFolder %>css/styles.css',
                dest: '<%= config.outputFolder %>css/styles.min.css'
            }
        },
        uglify: {
            js: {
                drop_console: true,
                files: {
                    '<%= config.outputFolder %>js/all.min.js': [ '<%= config.outputFolder %>js/all.js' ]
                }
            }
        },
        sass: {
            dist: {
                options: {
                    includePaths: [ '<%= config.buildFolder %>sass']
                },
                files: {
                    '<%= config.buildFolder %>css/screen.css': '<%= config.buildFolder %>sass/screen.scss'
                }
            },
        },
        dss: {
            docs: {
                files: {
                    '<%= config.outputFolder %>styleguide/': '<%= config.buildFolder %>sass/screen.scss'
                },
                options: {
                    template: '<%= config.buildFolder %>dss-styleguide/',
                    parsers: {
                      link: function(i, line, block, file){

                          var link = line.split(' - ');
                          return {
                            link: (link[0]) ? (link[0].trim()) : '',
                            description: (link[1]) ? link[1].trim() : ''
                          };
                      }
                    }
                }
            }
        },
        sprite:{
          all: {
            padding: 2,
            src: '<%= config.buildFolder %>sprites/*.png',
            dest: '<%= config.buildFolder %>images/spritesheet.png',
            destCss: '<%= config.buildFolder %>sass/_sprites.scss'
          }
        },
        validation: {
            options: {
                reset: grunt.option('reset') || true,
                stoponerror: false,
                remotePath: '<%= config.siteURL %>',
                remoteFiles: [ 'index.html' ]                           // NOTE: you can specify more remote files to check here.
            },
            files: {
                src: [ '<%= config.outputFolder %>empty.html' ]         // NOTE: you can add static HTML files here.. There must be atleast one file or this fails
            }
        },
        yslow: {
            options: {
                thresholds: {
                    weight: 180,
                    speed: 1000,
                    score: 80,
                    requests: 15
                }
            },
            pages: {
                    files: [
                    {
                        src: '<%= config.siteURL %>index.html'          // NOTE: you can specify more files here..
                    }
                ]
            }
        },
        uncss: {
          dist: {
            options: {
                ignore: ['.uncss-keep'],
                htmlroot     : siteConfig.outputFolder,
                stylesheets: [ '<%= config.siteURL %>/css/styles.min.css' ],
                urls: [ siteConfig.siteURL ]                            // NOTE: you must list all site .html files or url's here for uncss to work.
            },
            files: {
                // NOTE: the file here is basically a dummy file.. Grunt is a bit dumb.
                './httpdocs/css/styles-uncss.min.css': [ '<%= config.outputFolder %>empty.html' ]
            }
          }
        },
        "regex-check": {
            // NOTE: Add your templates extension to the list of files to check here.
            headers: {
                options: {
                    pattern : /<h1>/g,
                    negative: true,
                    label: "Must have an H1 tag"
                },
                files: { src: allTemplates }

            },
            description: {
                options: {
                    pattern : /meta\s{1,}name=['"]description['"]\s{1,}content=['"][a-zA-Z0-9\s]{3,}['"]/g,
                    negative: true,
                    label: "Must have a META description"
                },
                files: { src: allTemplates }

            },
            title: {
                options: {
                    pattern : /<title>[a-zA-Z0-9\s&;-]{1,}<\/title>/g,
                    negative: true,
                    label: "Must have a title"
                },
                files: { src: allTemplates }

            },
            keywords: {
                options: {
                    pattern : /meta\s{1,}name=['"]keywords['"]\s{1,}content=['"][a-zA-Z0-9\s]{3,}['"]/g,
                    negative: true,
                    label: "Must have a META keywords"
                },
                files: { src: allTemplates }
            },
            facebook: {
                options: {
                    pattern : /meta\s{1,}property=['"]og:description['"]\s{1,}content=['"][a-zA-Z0-9\s]{3,}['"]/g,
                    negative: true,
                    label: "No Facebook og:description"
                },
                files: { src: allTemplates }
            }

        },
        watch: {
            sassify: {
                files: [ '<%= config.buildFolder %>sass/*'],
                tasks: ['sass:dist', 'dss' ]
            },
            sprites: {
                files: [ '<%= config.buildFolder %>sprites/*'],
                tasks: [ 'sprite', 'sass:dist' ],
                options: {
                    livereload: true,
                },
            },
            images: {
                files: [ '<%= config.buildFolder %>images/**'],
                options: {
                    livereload: true,
                },
            },
            css: {
                files: [ '<%= config.buildFolder %>css/*'],
                tasks: [ 'concat:css', 'cssmin' ],
                options: {
                    livereload: true,
                },
            },
            js: {
                files: [ '<%= config.buildFolder %>js/*.js'],
                tasks: ['concat:js', 'uglify'  ],
                options: {
                    livereload: true,
                },
            },
            html: {
                files: [ '<%= config.outputFolder %>**/*.{html,ejs,php}', '<%= config.outputFolder %>**/.{png,jpg,gif,svg,ico}'],
                options: {
                    livereload: true,
                }
            },
        },
        harp: {
            server: {
                server: true,
                source: '.'
            },
            compile: {
                source: '.',
                dest: '<%= config.harpCompileFolder %>'
            }
        },
        harp_post: {
            post: {
                options: {
                    destFolderBase: "public/",
                    templatePath: "build/templates/post.md",
                    path: "posts/",
                    fields: [{
                        type: "checkbox",
                        message: "Categories",
                        name: "categories",
                        choices: [
                            "News",
                            "Social",
                            "SEO",
                            "Technology",
                            "Press Release",
                        ]
                    }]
                }
            }
        },
        concurrent: {
            dev: ['watch', 'harp:server', 'open:dev'],
            options: {
                logConcurrentOutput: true
            }
        },
        open : {
            dev : {
                path: 'http://localhost:9000',
                app: 'Google Chrome'
            }
        },
        aws: grunt.file.readJSON('grunt-aws.json'),
        aws_s3: {
            options: {
                accessKeyId: '<%= aws.key %>',
                secretAccessKey: '<%= aws.secret %>',
                region: 'us-east-1',
                bucket: '<%= aws.bucket %>',
                access: 'public-read',
                differential: true
            },
            pushprod: {
                files: [{
                    action: 'upload',
                    expand: true,
                    cwd: '<%= config.harpCompileFolder %>',
                    src: ['**'],
                    dest: ''
                }]
            }
        }
    });



    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-harp');
    grunt.loadNpmTasks('grunt-harp-post');
    grunt.loadNpmTasks('grunt-concurrent');
    grunt.loadNpmTasks('grunt-dss');
    grunt.loadNpmTasks('grunt-spritesmith');
    grunt.loadNpmTasks('grunt-html-validation');
    grunt.loadNpmTasks('grunt-uncss');
    grunt.loadNpmTasks('grunt-yslow');
    grunt.loadNpmTasks('grunt-regex-check');
    grunt.loadNpmTasks('grunt-aws-s3');
    grunt.loadNpmTasks('grunt-open');
    grunt.loadNpmTasks('grunt-exec');


    //grunt.file.setBase('/')

    grunt.registerTask('test-html', [ 'validation', 'regex-check:headers', 'regex-check:description', 'regex-check:title', 'regex-check:keywords', 'regex-check:facebook' ]);
    grunt.registerTask('test-performance', [ 'yslow' ]);
    grunt.registerTask('styleguide', [ 'dss' ] );
    grunt.registerTask('post', [ 'harp_post:post' ] );      // alias
    grunt.registerTask('build', ['sprite', 'sass', 'concat:css', 'cssmin:css', 'concat:js', 'uglify:js', 'dss' ]);
    grunt.registerTask('default', ['concurrent:dev' ]);
    grunt.registerTask('start', ['concurrent:dev' ]);
    grunt.registerTask('compile', ['default', 'harp:compile']);
    grunt.registerTask('pushlive', ['default', 'harp:compile', 'aws_s3:pushprod']);
};
