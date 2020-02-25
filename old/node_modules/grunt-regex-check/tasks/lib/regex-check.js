'use strict';
var grunt = require('grunt');

var RegexCheck = function (pattern, listOfExcludedFiles, gruntLog, gruntFile, negative, label) {
    var log = gruntLog;
    var file = gruntFile;

    var excludedFiles = listOfExcludedFiles || [];
    if (pattern === undefined) {
        throw "Configuration option 'pattern' was not specified";
    }
    if (typeof pattern !== 'object') {
        throw "Configuration option 'pattern' should be a javascript regular expression";
    }

    if (negative === undefined) {
        negative = false;
    }

    var isExcluded = function (filepath) {
        var isExcluded = false;
        excludedFiles.forEach(function (excludedFile) {
            if (excludedFile === filepath) {
                isExcluded = true;
            }
        });
        return isExcluded;
    };

    return {
        check: function (files) {
            var ranOnce = false;

            files.forEach(function (f) {
                var matchingFiles = f.src.filter(function (filepath) {
                    // Warn on and remove invalid source files (if nonull was set).
                    if (!file.exists(filepath)) {
                        log.warn('Source file "' + filepath + '" not found.');
                        return false;
                    } else {
                        return true;
                    }
                }).map(function (filepath) {
                    ranOnce = true;
                    var source = file.read(filepath);
                    var match = source.match(pattern);

                    return {
                      filepath: filepath,
                      match: match,
                      isNotExcluded: !isExcluded(filepath, excludedFiles)
                    };
                }).filter(function (result) {
                        if(result.isNotExcluded)
                        {
                            if(negative) 
                                return result.match === null;
                            else
                                return result.match !== null;

                        } else {
                            return false;
                        }
 
                    });

                if (matchingFiles.length === 0) {
                    log.writeln('grunt-regex-check passed');
                } else {

                    if(negative)
                    {
                        var filesMessages = matchingFiles.map(function (matchingFile) {

                            if (label === undefined)
                            {
                                return matchingFile.filepath + " - failed because it didn't match '" + pattern + "'";   
                            } else {
                                return matchingFile.filepath + " - failed test: " + label;
                            }
                          
                        }).join('\n');

                        var finalMsg = "\n" + filesMessages;

                        if(excludedFiles.length > 0) {
                             finalMsg += "\n\nFiles that were excluded:\n" + excludedFiles.join('\n')
                        }

                        grunt.log.error(finalMsg);

                    } else {
                        var filesMessages = matchingFiles.map(function (matchingFile) {

                            if (label === undefined)
                            {
                                return matchingFile.filepath + " - failed because it matched '" + matchingFile.match[0] + "'";
                            } else {
                                return matchingFile.filepath + " - failed test: " + label;
                            }
                          
                        }).join('\n');

                        var finalMsg = "\n" + filesMessages;

                        if(excludedFiles.length > 0) {
                             finalMsg += "\n\nFiles that were excluded:\n" + excludedFiles.join('\n')
                        }

                        grunt.log.error(finalMsg);

                    }
                }

            });
            if(!ranOnce) {
                log.warn("No files were processed. You may want to check your configuration. Files detected: " + files.join(','));
            }
        }
    };
};


module.exports = RegexCheck;


