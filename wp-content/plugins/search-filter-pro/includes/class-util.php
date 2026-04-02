<?php
/**
 * Util class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A helper class with functions used across the plugin
 */
class Util {

	/**
	 * Logged messages for deduplication within a request.
	 *
	 * @var array
	 */
	private static $logged_messages = array();

	/**
	 * Converts a shorthand byte value to an integer byte value.
	 *
	 * Wrapper for wp_convert_hr_to_bytes(), moved to load.php in WordPress 4.6 from media.php
	 *
	 * Credit goes to the Action Scheduler Libary - https://github.com/woocommerce/action-scheduler/
	 *
	 * @link https://secure.php.net/manual/en/function.ini-get.php
	 * @link https://secure.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
	 *
	 * @param string|int $value A (PHP ini) byte value, either shorthand or ordinary.
	 * @return int An integer byte value.
	 */
	public static function convert_hr_to_bytes( $value ) {
		if ( function_exists( 'wp_convert_hr_to_bytes' ) ) {
			return wp_convert_hr_to_bytes( $value );
		}

		$value = strtolower( trim( $value ) );
		$bytes = (int) $value;

		if ( false !== strpos( $value, 'g' ) ) {
			$bytes *= GB_IN_BYTES;
		} elseif ( false !== strpos( $value, 'm' ) ) {
			$bytes *= MB_IN_BYTES;
		} elseif ( false !== strpos( $value, 'k' ) ) {
			$bytes *= KB_IN_BYTES;
		}

		// Deal with large (float) values which run into the maximum integer size.
		return min( $bytes, PHP_INT_MAX );
	}

	/**
	 * Get the memory limit.
	 *
	 * Uses the memory_limit ini setting if available,
	 * otherwise uses a sensible default.
	 *
	 * @return int
	 */
	public static function get_memory_limit() {
		$memory_limit = false;
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		}

		// False means not set, empty string means null value.
		if ( $memory_limit === false || $memory_limit === '' ) {
			// So set a sensible default.
			$memory_limit = '128M';
		} elseif ( intval( $memory_limit ) === -1 ) {
			// Then it's set to unlimited, lets still be reasonably
			// conservative and set to 4GB.
			$memory_limit = '4000M';
		}
		return self::convert_hr_to_bytes( $memory_limit );
	}
	/**
	 * Get the max execution time.
	 *
	 * Uses the max_execution_time ini setting if available,
	 * otherwise uses a sensible default.
	 *
	 * @return int
	 */
	public static function get_max_execution_time() {
		$max_execution_time = false;

		if ( function_exists( 'ini_get' ) ) {
			$max_execution_time = ini_get( 'max_execution_time' );
		}
		// False means not set, empty string means null value.
		if ( $max_execution_time === false || $max_execution_time === '' ) {
			// So set a sensible default.
			$max_execution_time = 30;
		}
		return intval( $max_execution_time );
	}
	/**
	 * Attempts to raise the PHP memory limit for memory intensive processes.
	 *
	 * Only allows raising the existing limit and prevents lowering it.
	 *
	 * Wrapper for wp_raise_memory_limit(), added in WordPress v4.6.0
	 *
	 * Credit goes to the Action Scheduler Libary - https://github.com/woocommerce/action-scheduler/
	 *
	 * @return bool|int|string The limit that was set or false on failure.
	 */
	public static function raise_memory_limit() {
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			return wp_raise_memory_limit( 'admin' );
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Fallback when wp_raise_memory_limit() doesn't exist.
		$current_limit     = @ini_get( 'memory_limit' );
		$current_limit_int = self::convert_hr_to_bytes( $current_limit );

		if ( -1 === $current_limit_int ) {
			return false;
		}

		$wp_max_limit       = defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : '256M';
		$wp_max_limit_int   = self::convert_hr_to_bytes( $wp_max_limit );
		$filtered_limit     = apply_filters( 'admin_memory_limit', $wp_max_limit );
		$filtered_limit_int = self::convert_hr_to_bytes( $filtered_limit );

		if ( -1 === $filtered_limit_int || ( $filtered_limit_int > $wp_max_limit_int && $filtered_limit_int > $current_limit_int ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.memory_limit_Disallowed -- Fallback when wp_raise_memory_limit() doesn't exist.
			if ( false !== @ini_set( 'memory_limit', $filtered_limit ) ) {
				return $filtered_limit;
			} else {
				return false;
			}
		} elseif ( -1 === $wp_max_limit_int || $wp_max_limit_int > $current_limit_int ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.memory_limit_Disallowed -- Fallback when wp_raise_memory_limit() doesn't exist.
			if ( false !== @ini_set( 'memory_limit', $wp_max_limit ) ) {
				return $wp_max_limit;
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Flush the cache if possible (intended for use after a batch of actions has been processed).
	 *
	 * This is useful because running large batches can eat up memory and because invalid data can accrue in the
	 * runtime cache, which may lead to unexpected results.
	 *
	 * Credit goes to the Action Scheduler Libary - https://github.com/woocommerce/action-scheduler/
	 */
	public static function clear_object_caches() {
		/*
		 * Calling wp_cache_flush_runtime() lets us clear the runtime cache without invalidating the external object
		 * cache, so we will always prefer this method (as compared to calling wp_cache_flush()) when it is available.
		 *
		 * However, this function was only introduced in WordPress 6.0. Additionally, the preferred way of detecting if
		 * it is supported changed in WordPress 6.1 so we use two different methods to decide if we should utilize it.
		 */
		$flushing_runtime_cache_explicitly_supported = function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_runtime' );
		$flushing_runtime_cache_implicitly_supported = ! function_exists( 'wp_cache_supports' ) && function_exists( 'wp_cache_flush_runtime' );

		if ( $flushing_runtime_cache_explicitly_supported || $flushing_runtime_cache_implicitly_supported ) {
			wp_cache_flush_runtime();
		} elseif (
			! wp_using_ext_object_cache()
			/**
			 * When an external object cache is in use, and when wp_cache_flush_runtime() is not available, then
			 * normally the cache will not be flushed after processing a batch of actions (to avoid a performance
			 * penalty for other processes).
			 *
			 * This filter makes it possible to override this behavior and always flush the cache, even if an external
			 * object cache is in use.
			 *
			 * @since 1.0
			 *
			 * @param bool $flush_cache If the cache should be flushed.
			 */
			|| apply_filters( 'action_scheduler_queue_runner_flush_cache', false )
		) {
			wp_cache_flush();
		}
	}

	/**
	 * Log an error message to the error log.
	 *
	 * Only if WP_DEBUG is enabled. Automatically defers logging if called
	 * during an active database transaction to prevent DB access issues.
	 *
	 * This is a duplicate of the function in the parent plugin,
	 * because we need to use it when the parent plugin is not
	 * loaded.
	 *
	 * @param string $message The error message.
	 * @param string $level   The log level (error, warning, notice).
	 * @param bool   $once    If true, only log this message once per request.
	 */
	public static function error_log( $message, $level = 'error', $once = false ) {

		// If the base plugin is not loaded, Transaction won't exist.
		if ( class_exists( '\Search_Filter\Database\Transaction' ) ) {
			// If inside a transaction, defer logging to prevent DB access.
			if ( \Search_Filter\Database\Transaction::is_active() ) {
				\Search_Filter\Database\Transaction::defer(
					function () use ( $message, $level, $once ) {
						self::do_error_log( $message, $level, $once );
					}
				);
				return;
			}
		}

		self::do_error_log( $message, $level, $once );
	}

	/**
	 * Actually perform the logging (internal, bypasses transaction check).
	 *
	 * @param string $message The error message.
	 * @param string $level   The log level (error, warning, notice).
	 * @param bool   $once    If true, only log this message once per request.
	 */
	private static function do_error_log( $message, $level = 'error', $once = false ) {
		// Handle once-per-request deduplication.
		if ( $once ) {
			$key = md5( $level . $message );
			if ( isset( self::$logged_messages[ $key ] ) ) {
				return;
			}
			self::$logged_messages[ $key ] = true;
		}

		$log_level       = 'errors';
		$log_to_database = 'no';

		$has_base_plugin = class_exists( '\Search_Filter\Features' ) && class_exists( '\Search_Filter\Debugger' );

		if (
			$has_base_plugin &&
			did_action( 'search-filter/settings/features/init' ) &&
			\Search_Filter\Features::is_enabled( 'debugMode' )
		) {
			$log_level = \Search_Filter\Features::get_setting_value( 'debugger', 'logLevel' );
			if ( $log_level === null ) {
				$log_level = 'errors';
			}
			$log_to_database = \Search_Filter\Features::get_setting_value( 'debugger', 'logToDatabase' );
			if ( $log_to_database === null ) {
				$log_to_database = 'no';
			}
		}

		$log_matrix = array(
			'errors'   => array( 'error' ),
			'warnings' => array( 'warning', 'error' ),
			'all'      => array( 'notice', 'warning', 'error' ),
		);

		if ( ! in_array( $level, $log_matrix[ $log_level ], true ) ) {
			return;
		}

		$pid = '';
		// Some hosting companies like Kinsta disable this function.
		if ( function_exists( 'getmypid' ) ) {
			$pid = getmypid() . ' | ';
		}

		if ( self::is_debug_logging_enabled() ) {
			// Translators: %1$s is the process ID, %2$s is the message.
			$full_message = wp_kses_post( sprintf( '%1$sSearch & Filter Pro: %2$s', $pid, $message ) );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $full_message );
		}

		if ( $log_to_database === 'yes' ) {
			$full_message = sprintf( '%1$sSearch & Filter Pro: %2$s', $pid, $message );
			Database\Queries\Logs_Direct::resilient_create_log(
				sanitize_text_field( $full_message ),
				$level
			);
		}
	}

	/**
	 * Is debug logging enabled?
	 *
	 * @return bool
	 */
	public static function is_debug_logging_enabled() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG === true && defined( 'WP_DEBUG_LOG' ) && ! empty( WP_DEBUG_LOG );
	}
	/**
	 * Get author IDs from author slugs.
	 *
	 * TODO - We should use the WP_Data class to handle this.
	 *
	 * @since 3.0.0
	 *
	 * @param array $author_slugs The author slugs to get the IDs for.
	 * @return array    The author IDs.
	 */
	public static function get_author_ids_from_slugs( $author_slugs ) {
		$author_ids = array();
		foreach ( $author_slugs as $author_slug ) {
			$author = get_user_by( 'slug', $author_slug );
			if ( $author ) {
				$author_ids[] = $author->ID;
			}
		}
		return $author_ids;
	}

	/**
	 * Check if we're only in the admin, exclude AJAX and REST requests.
	 *
	 * Important: must be kept here in pro so it can be called when S&F base is not loaded.
	 *
	 * @return bool
	 */
	public static function is_admin_only() {
		return is_admin() && ! wp_doing_ajax() && ! wp_is_serving_rest_request() && ! wp_doing_cron() && ! wp_is_json_request();
	}
}
