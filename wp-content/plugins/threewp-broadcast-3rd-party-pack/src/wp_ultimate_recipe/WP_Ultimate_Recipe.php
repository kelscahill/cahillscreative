<?php

namespace threewp_broadcast\premium_pack\wp_ultimate_recipe;

/**
	@brief			Adds support for <a href="http://bootstrapped.ventures">Bootstrapped Ventures'</a> <a href="http://bootstrapped.ventures">WP Ultimate Recipe</a> plugin.
	@plugin_group	3rd party compatability
	@since			2016-03-22 18:37:40
**/
class WP_Ultimate_Recipe
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The option key for the nutritional information.
		@since		2015-04-09 19:30:34
	**/
	public static $wpurp_nutritional_information = 'wpurp_nutritional_information';

	/**
		@brief		Option key for the ingredient metadata.
		@since		2015-04-10 13:46:31
	**/
	public static $wpurp_taxonomy_metadata_ingredient = 'wpurp_taxonomy_metadata_ingredient';

	/**
		@brief		The custom fields to translate.
		@since		2015-04-05 08:16:10
	**/
	public static $custom_fields_to_translate = [ 'recipe_ingredients', 'recipe_instructions', 'recipe_terms', 'recipe_terms_with_parents' ];

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
	}

	/**
		@brief		Replace the ingredients and terms with their equivalents.
		@since		2015-04-05 08:10:43
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		$this->maybe_restore_meal_plan( $bcd );
		$this->maybe_restore_recipe( $bcd );
	}

	/**
		@brief		Save the nutritional information and ingredient metadata.
		@since		2015-04-09 19:29:30
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		// Disable all of the save_actions that WPURP inserts.
		$wpur = \WPUltimateRecipe::get( false );
		remove_action( 'save_post', [ $wpur->helper( 'cache' ), 'check_save_post' ], 11, 2 );
		remove_action( 'save_post', [ $wpur->helper( 'recipe_save' ), 'save' ], 10, 2 );
		remove_action( 'save_post', [ $wpur->helper( 'search' ), 'save' ], 15, 2 );

		remove_action( 'save_post', [ $wpur->addon( 'recipe-grid' ), 'reset_saved_post_terms' ], 10, 2 );

		if ( class_exists( 'WPUltimatePostGrid' ) )
		{
			// And those of the premium addon.
			$upg = \WPUltimatePostGrid::get();
			remove_action( 'save_post', [ $upg->helper( 'grid_save' ), 'save' ], 10, 2 );
			remove_action( 'save_post', [ $upg->helper( 'post_save' ), 'save' ], 10, 2 );
			remove_action( 'save_post', [ $upg->helper( 'grid_cache' ), 'updated_post' ], 11, 2 );
		}

		$this->maybe_save_meal_plan( $bcd );
		$this->maybe_save_recipe( $bcd );
	}

	/**
		@brief		Add our types.
		@since		2016-07-27 20:15:57
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'recipe' );
		$action->add_type( 'meal_plan' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Save and restore
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Maybe save the meal plan.
		@since		2016-07-31 15:01:37
	**/
	public function maybe_save_meal_plan( $bcd )
	{
		if ( $bcd->post->post_type != 'meal_plan' )
			return;

		$meta_key = 'wpurp_meal_plan';

		// Find all the equivalent meals.
		$meal_plan = $bcd->custom_fields()->get_single( $meta_key, [] );
		$meal_plan = maybe_unserialize( $meal_plan );
		if ( ! isset( $meal_plan[ 'dates' ] ) )
			return;

		$this->prepare_bcd( $bcd );
		$bcd->wpurp->set( 'meal_plan', $meal_plan );
		$meal_plan_bcds = $bcd->wpurp->collection( 'meal_plan_bcds' );

		$this->debug( 'Current meal plan: %s', $meal_plan );

		foreach( $meal_plan[ 'dates' ] as $date_index => $date )
			foreach( $date as $meal_name => $meals )
				foreach( $meals as $meal_index => $meal )
				{
					$meal_id = $meal[ 'id' ];

					if ( $meal_id < 1 )
						continue;

					$meal_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( $meal_id );
					$meal_plan_bcds->set( $meal_id, $meal_bcd );
					$this->debug( 'Saved meal plan broadcast data for %s: %s',$meal_id, $meal_bcd );
				}
	}

	/**
		@brief		Save the data for a recipe post.
		@since		2016-07-27 20:22:09
	**/
	public function maybe_save_recipe( $bcd )
	{
		if ( $bcd->post->post_type != 'recipe' )
			return;

		$this->prepare_bcd( $bcd );

		$data = get_option( static::$wpurp_nutritional_information );
		if ( $data !== false )
		{
			$bcd->wpurp->set( 'nutritional_information', $data );
			$this->debug( 'Saving nutritional information. It contains %s ingredients.', count( $data ) );
		}

		// Save the recipe instructions.
		$ri = $bcd->custom_fields()->get_single( 'recipe_instructions' );
		$ri = maybe_unserialize( $ri );
		$this->debug( 'Recipe instructions: %s', $ri );
		foreach( $ri as $step_index => $step )
		{
			if ( ! isset( $step[ 'image' ] ) )
				continue;
			$image_id = intval( $step[ 'image' ] );
			if ( $image_id < 1 )
				continue;
			if ( $bcd->try_add_attachment( $image_id ) )
				$this->debug( 'Adding image %s for recipe step %s', $image_id, $step_index );
		}

		$data = get_option( static::$wpurp_taxonomy_metadata_ingredient );
		if ( $data !== false )
		{
			$bcd->wpurp->set( 'taxonomy_metadata_ingredient', $data );
			$this->debug( 'Saving taxonomy metadata. It contains %s ingredients.', count( $data ) );
		}
	}

	/**
		@brief		restore_meal_plan
		@since		2016-07-27 21:31:03
	**/
	public function maybe_restore_meal_plan( $bcd )
	{
		if ( $bcd->post->post_type != 'meal_plan' )
			return;

		if ( ! isset( $bcd->wpurp ) )
			return;

		$meal_plan_data = $bcd->wpurp->get( 'meal_plan' );
		if( ! $meal_plan_data )
			return;

		$meal_plan_bcds = $bcd->wpurp->collection( 'meal_plan_bcds' );

		foreach( $meal_plan_data[ 'dates' ] as $date_index => $date )
			foreach( $date as $meal_name => $meals )
				foreach( $meals as $meal_index => $meal )
				{
					$meal_id = $meal[ 'id' ];

					$new_meal_id = $meal_plan_bcds->get( $meal_id )->get_linked_post_on_this_blog();

					$this->debug( 'New meal ID for date %s, meal name %s, meal index %s: %s',
						$date_index,
						$meal_name,
						$meal_index,
						$new_meal_id
					);

					$meal_plan[ 'dates' ][ $date_index ][ $meal_name ][ $meal_index ][ 'id' ] = $new_meal_id;
				}

		$this->debug( 'Replacing new meal plan: %s', $meal_plan );
		$bcd->custom_fields()->child_fields()->update_meta( 'wpurp_meal_plan', $meal_plan );
	}

	/**
		@brief		Restore the recipe.
		@since		2016-07-27 20:24:41
	**/
	public function maybe_restore_recipe( $bcd )
	{
		if ( $bcd->post->post_type != 'recipe' )
			return;

		if ( ! isset( $bcd->wpurp ) )
			return;

		$blog_id = get_current_blog_id();

		// And it must contain the custom_fields we translate.
		foreach( static::$custom_fields_to_translate as $field )
		{
			if ( ! isset( $bcd->custom_fields->original[ $field ] ) )
				continue;

			// Retrieve the old value
			$old_value = $bcd->custom_fields->original[ $field ];
			$old_value = maybe_unserialize( reset( $old_value ) );
			$new_value = $old_value;

			switch( $field )
			{
				// This uses a different layout to the other arrays.
				case 'recipe_ingredients':
					foreach( $old_value as $index => $ingredient )
					{
						$ingredient_id = $ingredient[ 'ingredient_id' ];
						// And find the equivalent new term ID of the taxonomy.
						if ( ! isset( $bcd->parent_blog_taxonomies[ 'ingredient' ][ 'equivalent_terms' ][ $blog_id ][ $ingredient_id ] ) )
							continue;
						$new_value[ $index ][ 'ingredient_id' ] = intval( $bcd->parent_blog_taxonomies[ 'ingredient' ][ 'equivalent_terms' ][ $blog_id ][ $ingredient_id ] );
					}
				break;
				case 'recipe_instructions':
					foreach( $old_value as $index => $step )
					{
						if ( ! isset( $step[ 'image' ] ) )
							continue;
						$image_id = $step[ 'image' ];
						if ( $image_id < 1 )
							continue;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$new_value[ $index ][ 'image' ] = $new_image_id;
					}
				break;
				default:
					foreach( $old_value as $taxonomy => $old_terms )
						foreach( $old_terms as $old_term_index => $old_term_id )
						{
							// And find the equivalent new term ID of the taxonomy.
							if ( ! isset( $bcd->parent_blog_taxonomies[ $taxonomy ][ 'equivalent_terms' ][ $blog_id ][ $old_term_id ] ) )
								continue;
							$new_value[ $taxonomy ][ $old_term_index ] = intval( $bcd->parent_blog_taxonomies[ $taxonomy ][ 'equivalent_terms' ][ $blog_id ][ $old_term_id ] );
						}
			}


			// Update the field with the new values.
			$this->debug( 'Broadcast Ultimate Recipe: Updating %s <em>%s</em> with <em>%s</em>', $field, serialize( $old_value ), serialize( $new_value ) );
			update_post_meta( $bcd->new_post()->ID, $field, $new_value );
		}

		// Update the nutritional information by merging.
		if ( $bcd->wpurp->has( 'nutritional_information' ) )
		{
			$data = $bcd->wpurp->get( 'nutritional_information' );
			$info = get_option( static::$wpurp_nutritional_information );
			if ( ! is_array( $info ) )
				$info = [];
			foreach( $data as $ingredient_id => $ingredient_info )
			{
				if ( ! isset( $bcd->parent_blog_taxonomies[ 'ingredient' ][ 'equivalent_terms' ][ $blog_id ][ $ingredient_id ] ) )
					continue;
				$equivalent_id = $bcd->parent_blog_taxonomies[ 'ingredient' ][ 'equivalent_terms' ][ $blog_id ][ $ingredient_id ];
				$info[ $equivalent_id ] = $ingredient_info;
				$this->debug( 'Updating nutritional information for ingredient %s, which on this blog is called %s.', $ingredient_id, $equivalent_id );
			}
			$this->debug( 'Updating nutritional information. New info has %s ingredients.', count( $info) );
			update_option( static::$wpurp_nutritional_information, $info );
		}
		else
			$this->debug( 'No nutritional information.' );

		// Merge the ingredient metadata.
		if ( $bcd->wpurp->has( 'taxonomy_metadata_ingredient' ) )
		{
			$data = $bcd->wpurp->get( 'taxonomy_metadata_ingredient' );
			$old_metadata = get_option( static::$wpurp_taxonomy_metadata_ingredient );
			if ( ! is_array( $old_metadata ) )
				$old_metadata = [];
			foreach( $data as $key => $value )
				$old_metadata[ $key ] = $value;
			$this->debug( 'Updating ingredient metadata.' );
			update_option( static::$wpurp_taxonomy_metadata_ingredient , $old_metadata );
		}
		else
			$this->debug( 'No ingredient metadata.' );
	}

	/**
		@brief		Prepare the broadcasting_data object.
		@since		2016-07-27 21:28:24
	**/
	public function prepare_bcd( $bcd )
	{
		if ( ! isset( $bcd->wpurp ) )
			$bcd->wpurp = ThreeWP_Broadcast()->collection();
	}
}
