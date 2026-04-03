<?php
namespace Perfmatters\PMCS;

class Error
{
    private static $previous_handler = null;
    private static $is_processing = false;
    private static $storage_dir = '';

    public static function init()
    {
        //capture directory path once at boot to prevent method calls during crashes
        self::$storage_dir = (string)PMCS::get_storage_dir();

        //wordpress recovery mode filter
        add_filter('wp_php_error_args', array(__CLASS__, 'handle_fatal_error'), 1, 2);

        //global exception handler
        self::$previous_handler = set_exception_handler(array(__CLASS__, 'exception_handler'));

        //native php shutdown
        register_shutdown_function(function() {
            $error = error_get_last();
            if($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                if(!defined('PMCS_CRASHING')) {
                    self::handle_fatal_error($error, ['response' => 500]);
                }
            }
        });
    }

    //uncaught exception/error handler
    public static function exception_handler($e) {

        //prevent recursion
        if(defined('PMCS_CRASHING')) {
            return;
        }
        define('PMCS_CRASHING', true);

        try {

            $file = $e->getFile();

            //handle snippet error
            if(!empty(self::$storage_dir) && self::$storage_dir === dirname($file)) {
                
                self::handle_fatal_error([
                    'message' => $e->getMessage(),
                    'file'    => $file,
                    'type'    => ($e instanceof \Exception) ? 'exception' : 'error',
                    'line'    => $e->getLine()
                ], ['response' => 500]);
            }

            //manual logging
            error_log(sprintf(
                'PMCS Caught %s: %s in %s on line %d',
                get_class($e),
                $e->getMessage(),
                $file,
                $e->getLine()
            ));

        } catch (\Throwable $t) {

            //internal handler failure
            error_log('PMCS Internal Handler Failure: ' . $t->getMessage());
        }

        //restore original handler
        restore_exception_handler();

        //hand off to original handler
        if(is_callable(self::$previous_handler)) {
            call_user_func(self::$previous_handler, $e);
            exit(1);
        }

        exit(1);
    }

    //handle fatal error
    public static function handle_fatal_error($error, $args = [])
    {
        //only handle actual 500 errors
        if(isset($args['response']) && $args['response'] != 500) {
            return $args;
        }

        //safety check
        if(empty($error['file']) || self::$is_processing) {
            return $args;
        }

        //make sure the error file is in our snippet directory
        if(empty(self::$storage_dir) || self::$storage_dir !== dirname($error['file'])) {
            return $args;
        }

        //lock the process
        self::$is_processing = true;

        try {

            $file_name = basename($error['file']);
            $config = PMCS::get_snippet_config();

            if(!empty($config) && !isset($config['error_files'][$file_name])) {

                //build message
                $message = !empty($error['message']) ? $error['message'] : 'Unknown Error';

                //cleanup
                $message = explode("\n", $message)[0]; //first line only
                $message = str_replace(self::$storage_dir, '', $message); //hide server path
                $message = str_ireplace('Fatal error: ', '', $message); //remove WP redundant prefix

                //default prefix
                $prefix = 'PHP Fatal Error: ';
                
                //prefix with PHP error type if available
                if(isset($error['type'])) {
                    if($error['type'] === 'exception') {
                        $prefix = 'Uncaught Exception: ';
                    }
                    elseif ($error['type'] === 'error') {
                        $prefix = 'Uncaught Error: ';
                    }
                    elseif ($error['type'] === E_PARSE) {
                        $prefix = 'PHP Parse Error: ';
                    } 
                    elseif ($error['type'] === E_COMPILE_ERROR) {
                        $prefix = 'PHP Compile Error: ';
                    }
                }

                //add prefix
                $message = $prefix . $message;

                //add line to message
                if(!empty($error['line'])) {

                    //adjust for dockblock offset
                    $offset = Snippet::get_docblock_line_count($error['file']);
                    $message.= ' on line ' . max(1, $error['line'] - $offset);
                }

                //add error message for snippet in config
                $config['error_files'][$file_name] = $message;
                PMCS::update_snippet_config($config);

                //deactivate the snippet
                Snippet::deactivate($file_name);
            }
        } catch (\Throwable $t) {
            //silently fail if deactivation logic crashes
        }

        self::$is_processing = false;
        return $args;
    }

    //set processing status
    public static function set_processing($status) {
        self::$is_processing = (bool)$status;
    }
}