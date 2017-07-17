<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

if( ! class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

final class Shapla_DB_Admin_List_Table extends WP_List_Table {

    public function __construct(){
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'table',
            'plural'    => 'tables',
            'ajax'      => false
        ) );
    }

    /**
     * Message to show if no designation found
     *
     * @return void
     */
    public function no_items() {
        esc_html_e( 'No table found' );
    }

    /**
     * the table's columns and titles
     * @return array
     */
    public function get_columns(){
        $columns = array(
            'cb'        	=> '<input type="checkbox"/>',
            'table'         => __('Table', 'textdomain'),
            'type'          => __('Type', 'textdomain'),
            'collection'    => __('Collection', 'textdomain'),
            'rows'          => __('Rows', 'textdomain'),
            'size'          => __('Size', 'textdomain'),
            'actions'   	=> __('Actions', 'textdomain'),
        );

        return $columns;
    }


    /**
     * Get sortable columns
     * @return array
     */
    public function get_sortable_columns() {
        return array();
    }


    /**
     * Get column value
     * @param  array $item
     * @param  string $column_name
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        return isset( $item->{$column_name} ) ? $item->{$column_name} : NULL;
    }

    public function column_cb( $item ){
        return '<input type="checkbox" name="table[]" value="'. esc_attr( $item->TABLE_NAME ) .'" />';
    }

    public function column_table( $item ){
        return esc_attr( $item->TABLE_NAME );
    }

    public function column_type( $item ){
        return esc_attr( $item->ENGINE );
    }

    public function column_collection( $item ){
        return esc_attr( $item->TABLE_COLLATION );
    }

    public function column_rows( $item ){
        return intval( $item->TABLE_ROWS );
    }

    public function column_size( $item ){
    	$data_length 	= $item->DATA_LENGTH;
    	$index_length 	= $item->INDEX_LENGTH;
    	$size_in_kb 	= $data_length + $index_length;
        return $this->formatSizeUnits( $size_in_kb );
    }

    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
	}

    /**
     * Get actions clumn
     * @param  array $item
     * @return string
     */
    public function column_actions( $item ){
        
        $view_url = $this->__action_url( array( 'tab' => 'table-data', 'table' => esc_attr( $item->TABLE_NAME ) ));
        $empty_url = $this->__action_url( array(
            'tab' => 'tables-list',
            'table' => esc_attr( $item->TABLE_NAME ),
            'action' => 'empty',
        ));
        $drop_url = $this->__action_url( array(
            'tab' => 'tables-list',
            'table' => esc_attr( $item->TABLE_NAME ),
            'action' => 'drop',
        ));
        
        //Build row actions
        $actions = array();
        $actions['view'] = sprintf('<a title="Browse table" href="%1$s">%2$s</a>', $view_url, __('Browse', 'textdomain'));
        $actions['empty'] = sprintf('<a title="Empty table" href="%1$s">%2$s</a>', $empty_url, __('Empty', 'textdomain'));
        $actions['drop'] = sprintf('<a title="Drop table" href="%1$s">%2$s</a>', $drop_url, __('Drop', 'textdomain'));

        $item_value = sprintf( '<span class="row-info" data-table="%1$s"></span>',
            esc_attr( $item->TABLE_NAME ) // %1$s
        );
        
        //Return the title contents
        return sprintf('%1$s %2$s',
            $item_value,
            $this->row_actions($actions)
        );
    }

    public function __action_url( array $args, $page = 'tools.php' )
    {
        $defaults = array(
            'page'      => empty($_GET['page']) ? null : esc_attr( $_GET['page'] ),
            'tab'       => empty($_GET['tab']) ? null : esc_attr( $_GET['tab'] ),
            'table'     => empty($_GET['table']) ? null : esc_attr( $_GET['table'] ),
        );

        $args = wp_parse_args( $args, $defaults );

        return wp_nonce_url( add_query_arg( $args, admin_url( $page ) ), 'sp_delete_table_date');
    }


    /**
     * Get bult action
     * @return array
     */
    public function get_bulk_actions() {
        $actions = array(
            'empty'   => __( 'Empty', 'textdomain' ),
            'drop'    => __( 'Drop', 'textdomain' ),
        );
        return $actions;
    }


    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    public function prepare_items( $search = null ) {
        
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable.
         *
         * Finally, we build an array to be used by the class for column headers.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();

        // First, lets decide how many records per page to show
        $per_page = $this->get_items_per_page( 'shapla_import_export_per_page', 300 );
        // What page the user is currently looking at
        $current_page = $this->get_pagenum();

        $args = array(
            'orderby'   => !empty($_REQUEST['orderby']) ? $_REQUEST['orderby'] : $this->_primary_key,
            'order'     => !empty($_REQUEST['order']) ? $_REQUEST['order'] : 'desc',
            'offset'    => ( $current_page -1 ) * $per_page,
            'per_page'  => $per_page,
        );
        
        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $this->items = $this->get_results( $args, $search );
        
        // Total number of items
        $total_items = count( $this->items );

        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ) );
    }

    /**
     * Get all movies data from movies database table
     * 
     * @return object
     */
    public function get_results( array $args, $search = null )
    {
        $cache_key 	= 'database-tables-list';
        $items      = wp_cache_get( $cache_key, 'shapla-database-admin' );

        if ( false === $items ) {
            
	        global $wpdb;
			$items = $wpdb->get_results("
				SELECT *
				FROM INFORMATION_SCHEMA.TABLES
				WHERE TABLE_TYPE='BASE TABLE'
				AND TABLE_SCHEMA = '$wpdb->dbname'
			");

            wp_cache_set( $cache_key, $items, 'shapla-database-admin' );
        }

        return $items;
    }

    /**
     * Process bulk actions
     * 
     * @return void
     */
    public function process_bulk_action() {

        global $wpdb;
        $table = ! empty($_GET['table']) ? $_GET['table'] : '';

        //Detect when a bulk action is being triggered...
        if( 'empty'===$this->current_action() ) {
            if ( is_array( $table ) ) {
                foreach ($table as $_table) {
                    $_table = esc_attr( $_table );
                    $wpdb->query("TRUNCATE TABLE $_table");
                }
            } else {
                $_table = esc_attr( $table );
                $wpdb->query("TRUNCATE TABLE $_table");
            }
            
        }

        
        if( 'drop'===$this->current_action() ) {
            if ( is_array( $table ) ) {
                foreach ($table as $_table) {
                    $_table = esc_attr( $_table );
                    $wpdb->query("DROP TABLE IF EXISTS $_table");
                }
            } else {
                $_table = esc_attr( $table );
                $wpdb->query("DROP TABLE IF EXISTS $_table");
            }
        }
    }
}

?>

<div class="wrap">
    
    <h1 class="wp-heading-inline">Tables</h1>
    <hr class="wp-header-end">

    <!-- Show error message if any -->
    <?php if (array_key_exists('error', $_GET)): ?>
        <div class="notice notice-error is-dismissible"><p><?php echo $_GET['error']; ?></p></div>
    <?php endif; ?>

    <!-- Show success message if any -->
    <?php if (array_key_exists('success', $_GET)): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo $_GET['success']; ?></p></div>
    <?php endif; ?>
    
    <form id="form-tables-list" class="form-tables-list" method="get" autocomplete="off" accept-charset="utf-8">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>" />
        <input type="hidden" name="tab" value="<?php echo esc_attr( $_GET['tab'] ); ?>" />
        <?php
            //Create an instance of our package class...
            $listTable = new Shapla_DB_Admin_List_Table();
            //Fetch, prepare, sort, and filter our data...
            $listTable->prepare_items();
            // Display table with data
            $listTable->display();
        ?>
    </form>
    
</div>
