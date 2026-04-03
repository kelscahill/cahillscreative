<?php
namespace Perfmatters\PMCS;

class Conditions
{
    //check for a condition match
    public static function evaluate($conditions)
    {
        if(empty($conditions) || !is_array($conditions)) {
            return true;
        }

        $conditions = array_filter($conditions);
        $location = self::get_current_location();
        $show = false;

        //check for includes
        if(!empty($conditions['include'])) {
            foreach($conditions['include'] as $condition) {

                //include match
                if($show = self::check_condition_match($condition, $location)) {
                    break;
                }
            }

            //no matches
            if(!$show) {
                return false;
            }
        }

        //check for exclusions
        if(!empty($conditions['exclude'])) {
            foreach($conditions['exclude'] as $condition) {

                //exclude match
                if(self::check_condition_match($condition, $location)) {
                    return false;
                }
            }
        }

        //user role restriction
        if(!empty($conditions['users'])) {

            $user_info = self::get_current_user();
            $roles = array_column($conditions['users'], 'rule');

            if(empty(array_intersect($roles, $user_info))) {
                return false;
            }
        }

        return true;
    }

    //check if the given condition matches the given location
    public static function check_condition_match($condition, $location) {

        if(!empty($condition['rule'])) {

            if(is_singular() && $condition['rule'] === 'general:singular') {
                return true;
            }
            elseif(is_archive() && $condition['rule'] === 'general:archive') {
                return true;
            }
            elseif(($location['rule'] ?? null) === $condition['rule']) {
                if(empty($condition['object']) || ((int) ($location['object'] ?? null) === (int) $condition['object'])) {
                    return true;
                }
            }
            //when on front page or blog page, also match conditions that target the current page by post type + ID
            elseif(in_array(($location['rule'] ?? null), ['general:front_page', 'general:blog']) && !empty($condition['object'])) {
                $queried_id = (int) get_queried_object_id();
                if($queried_id && (int) $condition['object'] === $queried_id) {
                    $post_type = get_post_type($queried_id);
                    if($post_type && ($condition['rule'] === 'post:' . $post_type)) {
                        return true;
                    }
                }
            }
            elseif(is_singular() && strpos($condition['rule'], ':taxonomy:') !== false) {
                $tax = substr($condition['rule'], strrpos($condition['rule'], ':') + 1);
                if($tax && !empty($condition['object']) && has_term($condition['object'], $tax)) {
                    return true;
                }
            }
            /*elseif(is_front_page() && is_home() && ($condition['rule'] === 'general:blog' || $condition['rule'] === 'general:front_page')) {
                return true;
            }*/
            elseif(is_paged() && $condition['rule'] === 'general:is_paged') {
                return true;
            }
        }

        return false;
    }

    //get current location
    public static function get_current_location() {

        global $wp_query;
        global $post;

        $location = null;
        $object = null;
        $queried_object = get_queried_object();

        //get location string
        if(is_front_page()) {
            $location = 'general:front_page';
        } 
        elseif(is_home()) {
            $location = 'general:blog';
        } 
        elseif(is_author()) {
            $location = 'general:author';
        }
        elseif(is_date()) {
            $location = 'general:date';
        } 
        elseif(is_search()) {
            $location = ($wp_query->found_posts === 0) ? 'general:no_results' : 'general:search';
        } 
        elseif(is_404()) {
            $location = 'general:404';
        } 
        elseif(is_category()) {
            $location = 'taxonomy:category';
            if(is_object($queried_object)) {
                $object = $queried_object->term_id;
            }
        }
        elseif(is_tag()) {
            $location = 'taxonomy:post_tag';
            if(is_object($queried_object)) {
                $object = $queried_object->term_id;
            }
        }
        elseif(is_tax()) {
            $location = 'taxonomy:' . get_query_var('taxonomy');
            if(is_object($queried_object)) {
                $location = 'taxonomy:' . $queried_object->taxonomy;
                $object = $queried_object->term_id;
            }
        } 
        elseif(is_post_type_archive()) {
            $post_type = $wp_query->get('post_type');
            $location = 'archive:' . (is_array($post_type) ? $post_type[0] : $post_type);
        } 
        elseif(is_singular()) {
            if(is_object($post)) {
                $location = 'post:' . $post->post_type;
            }
            if(is_object($queried_object)) {
                $object = $queried_object->ID;
            }
        }

        //admin location
        if(is_admin() && function_exists('get_current_screen')) {

            $current_screen = get_current_screen();

            if(isset($current_screen->is_block_editor) && $current_screen->is_block_editor) {
                
                $post_id = isset($_GET['post']) ? absint($_GET['post']) : false;

                //get location string by post ID
                if($post_id) {

                    $front_page_id = get_option('page_on_front');
                    $blog_id = get_option('page_for_posts');

                    if($post_id === (int) $front_page_id) {
                        $location = 'general:front_page';
                    }
                    elseif($post_id === (int) $blog_id) {
                        $location = 'general:blog';
                    }
                    else {
                        if(isset($current_screen->post_type)) {
                            $location = 'post:' . $current_screen->post_type;
                        }
                        $object = $post_id;
                    }
                }
                elseif(isset($_GET['post_type'])) {
                    $location = 'post:' . esc_attr($_GET['post_type']);
                }
            }
        }

        return array(
            'rule' => $location,
            'object' => $object,
        );
    }

    //return current user roles
    public static function get_current_user() {

        //status first
        $roles = [is_user_logged_in() ? 'general:logged_in' : 'general:logged_out'];

        //add user roles
        $user = wp_get_current_user();
        foreach((array) $user->roles as $role) {
            $roles[] = $role;
        }

        return $roles;
    }

    //return location conditions
    public static function get_conditions() {

        //general
        $types = array(
            'general' => array(
                'label' => esc_attr__('General', 'perfmatters'),
                'options' => array(
                    'general:front_page' => esc_attr__('Front Page', 'perfmatters'),
                    'general:blog'       => esc_attr__('Blog', 'perfmatters'),
                    'general:singular'   => esc_attr__('All Singular', 'perfmatters'),
                    'general:archive'    => esc_attr__('All Archives', 'perfmatters'),
                    'general:author'     => esc_attr__('Author Archives', 'perfmatters'),
                    'general:date'       => esc_attr__('Date Archives', 'perfmatters'),
                    'general:search'     => esc_attr__('Search Results', 'perfmatters'),
                    'general:no_results' => esc_attr__('No Search Results', 'perfmatters'),
                    'general:404'        => esc_attr__('404 Template', 'perfmatters'),
                    'general:is_paged'   => esc_attr__('Paginated Results', 'perfmatters'),
                ),
            ),
        );

        //post types
        $post_types = get_post_types(array('public' => true), 'objects');

        foreach($post_types as $post_type_slug => $post_type) {

            //post type object
            $post_type_object = get_post_type_object($post_type_slug);

            //add post type
            $types[$post_type_slug] = array(
                'label'   => $post_type->labels->name,
                'options' => array(
                    'post:' . $post_type_slug => $post_type->labels->singular_name,
                )
            );

            //add post type archive
            if($post_type_slug === 'post' || !empty($post_type_object->has_archive)) {
                $types[$post_type_slug . '_archive'] = array(
                    'label' => $post_type->labels->singular_name . ' ' . esc_attr__('Archives', 'perfmatters'),
                    'options' => array(
                        'archive:' . $post_type_slug => $post_type->labels->singular_name . ' ' . esc_attr__('Archive', 'perfmatters'),
                    )
                );
            }

            //add post type taxonomies
            $taxonomies = get_object_taxonomies($post_type_slug, 'objects');

            foreach($taxonomies as $taxonomy_slug => $taxonomy) {

                //skip post_format
                if($taxonomy_slug === 'post_format') {
                    continue;
                }

                //get label without name
                $label = str_replace(
                    array(
                        $post_type->labels->name,
                        $post_type->labels->singular_name,
                    ),
                    '',
                    $taxonomy->labels->singular_name
                );

                //add post type taxonomy
                if(isset($types[$post_type_slug]['options'])) {
                    $types[$post_type_slug]['options'][$post_type_slug . ':taxonomy:' . $taxonomy_slug] = $post_type->labels->singular_name . ' ' . $label;
                }

                //add post type taxonomy archive
                if(isset($types[$post_type_slug . '_archive']['options'])) {
                    $types[$post_type_slug . '_archive' ]['options']['taxonomy:' . $taxonomy_slug] = $post_type->labels->singular_name . ' ' . $label . ' ' . esc_attr__('Archive', 'perfmatters');
                }
            }
        }

        return $types;
    }

    //return user role conditions
    public static function get_user_conditions() {

        //general
        $rules = array(
            'general' => array(
                'label' => esc_attr__('General', 'perfmatters'),
                'options' => array(
                    'general:logged_in'  => esc_attr__('Logged In', 'perfmatters'),
                    'general:logged_out' => esc_attr__('Logged Out', 'perfmatters'),
                )
            ),
            'role' => array(
                'label' => esc_attr__('Roles', 'perfmatters' ),
                'options' => array(),
            )
        );

        //add user role options
        $roles = get_editable_roles();
        foreach($roles as $slug => $data) {
            $rules['role']['options'][$slug] = $data['name'];
        }

        return $rules;
    }

    //print condition input row html
    public static function print_input_row($type, $conditions, $row_count = 0, $value = [], $hidden = false) {

        $load_objects = !empty($value['rule']) && array_intersect(['post', 'taxonomy'], explode(':', $value['rule'])) ? true : false;
       
        if(is_array($value)) {
            $selected_rule = $value['rule'] ?? '';
            $selected_object = $value['object'] ?? '';
        }
        else {
            $selected_rule = $value;
        }
        
        echo '<div class="condition perfmatters-input-row' . ($load_objects ? ' pmcs-condition-load-objects' : '') . ($hidden ? ' hidden screen-reader-text' : '') . '"' . (empty($value) ? ' style="display: none;"' : '') . '>';
            
            //primary condition select
            echo '<select class="condition-select" name="conditions[' . $type . '][' . $row_count. '][rule]">';
                echo '<option value="">' . __('Select an option', 'perfmatters') . '</option>';
                
                foreach($conditions as $group) {

                    echo '<optgroup label="' . $group['label'] . '">';

                        foreach($group['options'] as $id => $label) {
                            printf('<option value="%1$s" %2$s>%3$s</option>', esc_attr($id), selected($selected_rule, $id), esc_html($label));
                        }

                    echo '</optgroup>';
                }

            echo '</select>';

            if($type !== 'users') {

                //condition object select
                echo '<select class="condition-object-select" data-saved-value="' . (!empty($selected_object) ? $selected_object : '') . '" name="conditions[' . $type . '][' . $row_count . '][object]"></select>';

                echo '<svg class="perfmatters-button-spinner" viewBox="0 0 100 100" role="presentation" focusable="false" style="background: rgba(0,0,0,.1); border-radius: 100%; width: 16px; height: 28px; margin: 0px 2px; overflow: visible; opacity: 1; background-color: transparent;"><circle cx="50" cy="50" r="50" vector-effect="non-scaling-stroke" style="fill: transparent; stroke-width: 1.5px; stroke: #fff;"></circle><path d="m 50 0 a 50 50 0 0 1 50 50" vector-effect="non-scaling-stroke" style="fill: transparent; stroke-width: 1.5px; stroke: #4A89DD; stroke-linecap: round; transform-origin: 50% 50%; animation: 1.4s linear 0s infinite normal both running perfmatters-spinner;"></path></svg>';
            }

            echo '<a href="#" class="perfmatters-delete-input-row" title="' . esc_attr__('Remove', 'perfmatters') . '"><span class="dashicons dashicons-trash"></span></a>';

        echo '</div>';
    }
}