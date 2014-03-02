<?php
/**
 * GravityView Widget Pagination
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



class GravityView_Widget_Pagination {
	
	function __construct() {
		
		add_action( 'gravityview_before', array( $this, 'render_pagination' ) );
		
	}
	
	
	public function render_pagination() {
		global $gravity_view;
		
		$offset = $gravity_view->paging['offset'];
		$page_size = $gravity_view->paging['page_size'];
		$total = $gravity_view->total_entries;
		
		
		// displaying info
		if( $total == 0 ) {
			$first = $last = 0;
		} else {
			$first = empty( $offset ) ? 1 : $offset + 1;
			$last = $offset + $page_size > $total ? $total : $offset + $page_size;
		}
		
		echo '<span class="">'. sprintf(__( 'Displaying %1$s - %2$s of %3$s', 'gravity-view' ), $first , $last , $total ) . '</span>';
		
		
		
		
		
		// pagination links
		$curr_page = empty( $_GET['pagenum'] ) ? 1 : intval( $_GET['pagenum'] );
		
		$page_links = array(
			'base' => add_query_arg('pagenum','%#%'),
			'format' => '&pagenum=%#%',
			'add_args' => array(), //
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'total' => ceil( $total / $page_size ),
			'current' => $curr_page,
			'show_all' => true, // to be available at backoffice
		);

		$page_links = paginate_links( $page_links );
		
		echo $page_links;
		
		
		
		
		
		
		
		// Search box and filters
		$curr_search = empty( $_GET['gv_search'] ) ? '' : $_GET['gv_search'];
		$curr_start = empty( $_GET['gv_start'] ) ? '' : $_GET['gv_start'];
		$curr_end = empty( $_GET['gv_end'] ) ? '' : $_GET['gv_end'];
		?>
		<form id="lead_form" method="get" action="">
			<p class="search-box">
				<label for="gv_search"><?php esc_html_e('Search Entries:', 'gravity-view' ); ?></label>
				<input type="text" name="gv_search" id="gv_search" value="<?php echo $curr_search; ?>" />
				
				<label for="gv_start_date"><?php esc_html_e('Filter by date:', 'gravity-view' ); ?></label>
				<input name="gv_start" id="gv_start_date" type="text" class="gv-datepicker" placeholder="<?php esc_attr_e('Start date', 'gravity-view' ); ?>" value="<?php echo $curr_start; ?>">
				<input name="gv_end" id="gv_end_date" type="text" class="gv-datepicker" placeholder="<?php esc_attr_e('End date', 'gravity-view' ); ?>" value="<?php echo $curr_end; ?>">
				
				<input type="submit" class="button" id="gv_search_button" value="<?php esc_attr_e( 'Search', 'gravity-view' ); ?>" />
			</p>
		</form>
		
		<?php
			
		
		
		// date filters
		
		
		
		
	}

	
}





