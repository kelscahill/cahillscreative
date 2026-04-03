<?php
namespace Perfmatters\PMCS;

//load WP_List_Table class file
if(!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

//extending wp list table class
class ListTable extends \WP_List_Table
{
	private $config = [];

	public function __construct()
    {
    	parent::__construct();
        $this->config = PMCS::get_snippet_config();
    }

	//add table classes
	function get_table_classes()
	{
		return array('widefat', 'pmcs-snippets');
	}

	//modify table rows
	function single_row($item)
	{
		$status = $item['active'] ? 'active' : 'inactive';
		echo '<tr class="pmcs-' . $status . '-snippet">';
			echo $this->single_row_columns($item);
		echo '</tr>';
	}

    //table columns
    function get_columns()
    {
        $columns = array(
			'cb'          => '<input type="checkbox">',
			'active'      => '',
			'name'        => esc_html__('Name', 'perfmatters'),
			'type'        => esc_html__('Type', 'perfmatters'),
			'description' => esc_html__('Description', 'perfmatters'),
			'tags'        => esc_html__('Tags', 'perfmatters'),
			'author'      => esc_html__('Author', 'perfmatters'),
			'created'     => esc_html__('Created', 'perfmatters'),
			'modified'    => esc_html__('Modified', 'perfmatters'),
			'priority'    => esc_html__('Priority', 'perfmatters')
		);

		return $columns;
    }

    //checkbox column
    function column_cb($item)
    {
        return '<input type="checkbox" name="snippets[]" value="' . esc_attr($item['file_name']) . '" />';
    }

    //column values
    protected function column_default($item, $column_name) : string {

		switch($column_name) {

			case 'active':

				if(!empty($item['file_name'])) {

					if(!empty($this->config['error_files'][$item['file_name']])) {

						//snippet error warning
						$output = '<span class="dashicons dashicons-warning"></span>';
					}
					else {

						//status toggle
						$output = '<label for="pmcs-active-' . esc_attr($item['file_name']) . '" class="perfmatters-switch" style="display: inline-flex; align-items: center;">';						
							$output.= '<input type="checkbox" id="pmcs-active-' . esc_attr($item['file_name']) . '" class="pmcs-active" value="1" data-pmcs-file-name="' . esc_attr($item['file_name']) . '"' . (!empty($item['active']) ? ' checked' : '') . ' style="display: inline-block; margin: 0px;">';
							$output.= '<div class="perfmatters-slider"></div>';
						$output.= '</label>';
					}

					return $output;
				}

				break;

			case 'name':

				//snippet name and row actions
				if(!empty($item['file_name'])) {

					$output = '<a href="?page=' . $_REQUEST['page'] . '&snippet=' . esc_attr($item['file_name']) . '#code">';
						$output.= esc_html($item['name']);
					$output.= '</a>';
					
					$actions = array(
	                	'edit'   => sprintf('<a href="?page=%s&snippet=%s#code">' . esc_html__('Edit', 'perfmatters') . '</a>', $_REQUEST['page'], esc_attr($item['file_name'])),
	                	'export' => sprintf('<a href="%s#code">' . esc_html__('Export', 'perfmatters') . '</a>', esc_url(wp_nonce_url(add_query_arg(array('page' => sanitize_text_field($_REQUEST['page']), 'export' => $item['file_name']), admin_url('admin.php')), 'pmcs-action'))),
	                	'delete' => sprintf('<a href="%s#code" class="pmcs-delete">' . esc_html__('Delete', 'perfmatters') . '</a>', esc_url(wp_nonce_url(add_query_arg(array('page' => sanitize_text_field($_REQUEST['page']), 'delete' => $item['file_name']), admin_url('admin.php')), 'pmcs-action'))),
					);

					$output.= $this->row_actions($actions, true);

	        		return $output;
				}

        		break;

			case 'type':

				//code type badge
				if(!empty($item['type'])) {
					return '<a class="pmcs-snippet-type-badge" href="' . add_query_arg('type', $item['type']) . '#code" data-snippet-type="' . $item['type'] . '">' . $item['type'] . '</a>';
				}
				
				break;

			case 'tags':

				//tags
				if(!empty($item['tags'])) {

					$tag_html = '';

					foreach($item['tags'] as $tag) {
						$tag_html.= '<a class="" href="' . esc_url(add_query_arg('tag', urlencode($tag))) . '#code">' . esc_html__($tag) . '</a>, ';
					}

					return rtrim($tag_html, ', ');
				}

				break;

			case 'author':

				//original author
				if(!empty($item['author'])) {
					$author = get_user_by('id', $item['author']);
					if(!empty($author->display_name)) {
						return $author->display_name;
					}
				}
		
				break;

			case 'modified':

				//modified date
				if(!empty($item['modified'])) {
					return PMCS::human_date($item['modified']);
				}
				
				break;

			default:

				//default column value
				if(!empty($item[$column_name])) {
					return esc_html($item[$column_name]);
				}
				
				break;
		}

		return '--';
	}

	//default hidden columns
	function get_hidden_columns() {
	    return array(
	    	'author',
	    	'tags',
	    	'created',
	    	'priority'
	    );
	}

    //prepare table with columns, pagination, requested snippets
    function prepare_items()
    {
    	//set up columns
        $columns = $this->get_columns();
        $hidden = (is_array(get_user_meta(get_current_user_id(), 'managesettings_page_perfmatterscolumnshidden', true))) ? get_user_meta(get_current_user_id(), 'managesettings_page_perfmatterscolumnshidden', true) : $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $primary  = 'name';
        $this->_column_headers = array($columns, $hidden, $sortable, $primary);

    	//get snippets
        $config = PMCS::get_snippet_config();

        if(empty($config)) {
			return;
		}

		//status filter
        $snippets = !empty($_GET['status']) ? $config[$_GET['status']] : array_merge($config['active'] ?? [], $config['inactive'] ?? []);

        //search filter
		$search_term = !empty($_REQUEST['s']) ? strtolower(trim($_REQUEST['s'])) : false;
		if($search_term) {

			$snippets = array_filter($snippets, function($snippet) use ($search_term) {

				//concatenate relevant fields into one haystack string for searching
				$haystack = strtolower(
					($snippet['name'] ?? '') . ' ' .
					($snippet['description'] ?? '') . ' ' .
					($snippet['type'] ?? '') . ' ' .
					(is_array($snippet['tags']) ? implode(' ', $snippet['tags']) : '')
				);
				
				//search combined string
				return strpos($haystack, $search_term) !== false;
			});
		}

        //filter snippet type
        if(!empty($_GET['type'])) {
        	foreach($snippets as $key => $snippet) {
	        	if(!empty($snippet['type']) && $snippet['type'] == $_GET['type']) {
	        		continue;
	        	}
	        	unset($snippets[$key]);
	        }
        }

        //filter snippet tag
        if(!empty($_GET['tag'])) {
        	foreach($snippets as $key => $snippet) {
	        	if(!empty($snippet['tags']) && in_array($_GET['tag'], $snippet['tags']) !== false) {
	        		continue;
	        	}
	        	unset($snippets[$key]);
	        }
        }
        
        //sort order
        usort($snippets, array(&$this, 'usort_reorder'));

        //pagination
        $per_page = $this->get_items_per_page('snippets_per_page', 10);
        $current_page = $this->get_pagenum();
        $total_items = count($snippets);
        
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));

        //snippets being viewed
		$snippets = array_slice($snippets, (($current_page - 1) * $per_page), $per_page);
        $this->items = $snippets;
    }

    //sortable columns
    function get_sortable_columns()
	{
		$sortable_columns = array(
		    'id'       => ['id', true],
			'name'     => ['name'],
			'created'  => ['created', true],
			'modified' => ['modified', true]
		);
		return $sortable_columns;
	}

	//top control bar
    function get_views()
    {
    	$config = PMCS::get_snippet_config();

    	//status counts
    	$active = count($config['active'] ?? []);
    	$inactive = count($config['inactive'] ?? []);
    	$all = $active + $inactive;

    	//status links
		if($all) {
			$status_links['all'] = '<a href="?page=perfmatters#code"' . (!isset($_GET['status']) ? ' class="current"' : '') . '>' . esc_html__('All', 'perfmatters') . ' <span class="count">(' . $all . ')</span></a>';

			if($active) {
	    		$status_links['active'] = '<a href="?page=perfmatters&status=active#code"' . (!empty($_GET['status']) && $_GET['status'] == 'active' ? ' class="current"' : '') . '>' . esc_html__('Active', 'perfmatters') . ' <span class="count">(' . $active . ')</span></a>';
	    	}
	    	if($inactive) {
	    		$status_links['inactive'] = '<a href="?page=perfmatters&status=inactive#code"' . (!empty($_GET['status']) && $_GET['status'] == 'inactive' ? ' class="current"' : '') . '>' . esc_html__('Inactive', 'perfmatters') . ' <span class="count">(' . $inactive . ')</span></a>';
	    	}

	    	return $status_links;
		}
	}

    //bulk actions
    function get_bulk_actions()
    {
	    $actions = array(
	    	'activate'   => esc_html__('Activate', 'perfmatters'),
	        'deactivate' => esc_html__('Deactivate', 'perfmatters'),
	        'export'     => esc_html__('Export', 'perfmatters'),
	        'delete'     => esc_html__('Delete', 'perfmatters')
	    );

	    return $actions;
    }

    //custom table controls
    public function extra_tablenav($which) {

	    if($which === 'top') {

	        echo '<div class="alignleft actions" style="margin-right: 8px;">';

	        	//type filter
		        echo '<select name="type" id="pmcs-type-filter">';

			        echo '<option value="">' . esc_html__('All Types', 'perfmatters') . '</option>';

			        foreach(['php', 'html', 'js', 'css'] as $type) {
			            $selected = isset($_REQUEST['type']) && $_REQUEST['type'] == $type ? ' selected' : '';
			            echo '<option value="' . $type . '"' . $selected . '>' . strtoupper($type) . "</option>";
			        }

		        echo '</select>';

		        submit_button(esc_html__('Filter', 'perfmatters'), 'button', 'filter_action', false);

		    echo '</div>';

		    echo '<div class="alignleft actions" style="display: flex; align-items: center; height: 100%;">';

		    	//selected tag
			    if(!empty($_GET['tag'])) {
			    	echo '<a href="' . remove_query_arg('tag') . '#code" class="pmcs-tag" style="color: #333; text-decoration: none; margin-right: 10px;">' . esc_html($_GET['tag']) . '<span class="pmcs-tag-close">×</span></a>';
			    }

		        //screen options toggle
    			echo '<a id="pmcs-show-settings-link" class="button">' . esc_html__('Screen Options', 'perfmatters') . '</a>';

	        echo '</div>';
	    }
	}

	//sorting function
    function usort_reorder($a, $b)
    {
        //default orderby
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'created';

        //default order
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'desc';

        //determine sort order
        $result = strcmp($a[$orderby] ?? '', $b[$orderby] ?? '');

        //send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }


    //pagination html
    protected function pagination($which) {

    	//capture original html
        ob_start();
        parent::pagination($which);
        $pagination_html = ob_get_clean();

	    //add code hash to any href values
	    echo $pagination_html = preg_replace('/href=["\']([^"\']*)["\']/i', 'href="${1}#code"', $pagination_html);
    }

    //column header html
    public function print_column_headers($with_id = true) {

    	//capture original html
        ob_start();
        parent::print_column_headers($with_id = true);
        $column_header_html = ob_get_clean();

	    //add code hash to any href values
	    echo $column_header_html = preg_replace('/href=["\']([^"\']*)["\']/i', 'href="${1}#code"', $column_header_html);
    }
}