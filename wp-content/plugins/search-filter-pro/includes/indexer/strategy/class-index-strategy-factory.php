<?php
/**
 * Index Strategy Factory.
 *
 * Factory class that determines which indexing strategy to use for a given field.
 * Supports registration of custom strategies by extensions.
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Strategy
 */

namespace Search_Filter_Pro\Indexer\Strategy;

use Search_Filter\Fields\Field;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Index Strategy Factory.
 *
 * Manages the registry of indexing strategies and determines which
 * strategy handles a given field. Supports priority-based ordering
 * so more specific strategies can take precedence.
 *
 * @since 3.2.0
 */
class Index_Strategy_Factory {

	/**
	 * Registered strategies keyed by type.
	 *
	 * @var array<string, Index_Strategy>
	 */
	private static $strategies = array();

	/**
	 * Whether the factory has been initialized.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Map of interaction type to strategy type.
	 *
	 * @var array<string, string>
	 */
	private static $interaction_type_map = array();

	/**
	 * Initialize the factory with built-in strategies.
	 *
	 * Should be called during plugin initialization.
	 *
	 * @since 3.2.0
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		// Register built-in strategies.
		self::register( new Search_Strategy() );
		self::register( new Bitmap_Strategy() );
		self::register( new Bucket_Strategy() );

		self::$initialized = true;

		/**
		 * Action fired after built-in strategies are registered.
		 *
		 * Extensions can use this to register custom strategies.
		 *
		 * @since 3.2.0
		 *
		 * @example
		 * add_action( 'search-filter-pro/indexer/register_strategies', function() {
		 *     Index_Strategy_Factory::register( new My_Custom_Strategy(), 8 );
		 * });
		 */
		do_action( 'search-filter-pro/indexer/register_strategies' );
	}

	/**
	 * Register a strategy.
	 *
	 * Strategies are keyed by their type identifier. The interaction type
	 * map is automatically populated from the strategy's supported types.
	 *
	 * @since 3.2.0
	 *
	 * @param Index_Strategy $strategy The strategy to register.
	 */
	public static function register( Index_Strategy $strategy ): void {
		$type                      = $strategy->get_type();
		self::$strategies[ $type ] = $strategy;

		// Populate interaction type map.
		foreach ( $strategy->get_interaction_types() as $interaction_type ) {
			self::$interaction_type_map[ $interaction_type ] = $type;
		}
	}

	/**
	 * Get the appropriate strategy for a field.
	 *
	 * Iterates through registered strategies and returns the first
	 * one that supports the given field.
	 *
	 * @since 3.2.0
	 *
	 * @param Field $field The field to get a strategy for.
	 * @return Index_Strategy|null The strategy, or null if no strategy supports the field.
	 */
	public static function for_field( Field $field ): ?Index_Strategy {
		// Ensure initialized.
		if ( ! self::$initialized ) {
			self::init();
		}

		// Don't check if the parent query is using the indexer, as sometimes
		// we need to know what a fields strategy _was_ in order to determine
		// how to handle it.

		// Find first matching strategy.
		foreach ( self::$strategies as $strategy ) {
			$supports = $strategy->supports( $field );

			if ( $supports ) {
				return $strategy;
			}
		}

		// No strategy found.
		return null;
	}

	/**
	 * Get strategy by type identifier.
	 *
	 * Useful for cases where you know the type but don't have a field.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type The strategy type ('bitmap', 'bucket', 'search').
	 * @return Index_Strategy|null The strategy, or null if not found.
	 */
	public static function get_by_type( string $type ): ?Index_Strategy {
		// Ensure initialized.
		if ( ! self::$initialized ) {
			self::init();
		}

		return self::$strategies[ $type ] ?? null;
	}
	/**
	 * Get strategy by interaction type identifier.
	 *
	 * Useful for cases where you know the interaction type but don't
	 * necessarily want to use a field.
	 *
	 * @since 3.2.0
	 *
	 * @param string $interaction_type The interaction type ('choice', 'range', 'search', etc.).
	 * @return Index_Strategy|null The strategy, or null if not found.
	 */
	public static function get_by_interaction_type( string $interaction_type ): ?Index_Strategy {
		// Ensure initialized.
		if ( ! self::$initialized ) {
			self::init();
		}

		$type = self::$interaction_type_map[ $interaction_type ] ?? null;

		if ( $type === null ) {
			return null;
		}

		return self::$strategies[ $type ] ?? null;
	}

	/**
	 * Reset the factory state.
	 *
	 * Clears strategies and initialization state.
	 * Used primarily for testing to reset to clean state.
	 *
	 * @since 3.2.0
	 */
	public static function reset(): void {
		self::$strategies           = array();
		self::$interaction_type_map = array();
		self::$initialized          = false;
	}

	/**
	 * Get all registered strategies.
	 *
	 * Primarily used for debugging/introspection.
	 *
	 * @since 3.2.0
	 *
	 * @return array<string, Index_Strategy> Strategies keyed by type.
	 */
	public static function get_strategies(): array {
		return self::$strategies;
	}

	/**
	 * Get all registered strategies as a flat array.
	 *
	 * Useful for operations that need to iterate all strategies
	 * (e.g., clearing all index types).
	 *
	 * @since 3.2.0
	 *
	 * @return Index_Strategy[] All strategies in a flat array.
	 */
	public static function get_all(): array {
		return array_values( self::$strategies );
	}
}
