<?php
/**
 *	Table Class for WP VisitorFlow
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Table")) :	// Prevent multiple class definitions

// Derive my own class from original WP List Table:
class WP_VisitorFlow_Table extends WP_List_Table_WPVF {

	protected $myColumns = array();
	protected $mySortableColumns = array();
	protected $myData = array();
	protected $myDefaultOrderBy;
	protected $myDefaultOrder = 'desc';

	/**
     * Constructor
     *
     * @param $columns - associative array with column_name => column_title pairs
	 * @param $sortableColumns - associative array with column_name => array with WP_List_Table sort options
	 * @param $data - array of associative arrays with the table data (table rows)
     */
	function __construct($columns, $sortableColumns, $data) {
       $this->myColumns = $columns;
       $this->mySortableColumns = $sortableColumns;
       $this->myDefaultOrderBy = key($sortableColumns);
       $this->myData = $data;
	   parent::__construct();
	}

   /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
		$columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 50;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
		return $this->myColumns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return $this->mySortableColumns;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array
     * @param  String $column_name - Current column name
     *
     * @return column_name with the
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            // case 'id'       	: return $item[ $column_name ];
            default:
                // return print_r( $item, true ) ;
                return $item[ $column_name ];
        }
    }

	/**
     * Returns the table data
     *
     * @return Mixed
     */
	private function table_data() {
		return $this->myData;
	}

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = $this->myDefaultOrderBy;
        $order = $this->myDefaultOrder;

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strnatcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}

endif;	// Prevent multiple class definitions
