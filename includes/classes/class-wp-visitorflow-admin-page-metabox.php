<?php
/**
 * The admin page class including metaboxes
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Admin_Page_Metabox")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Admin_Page_Metabox extends WP_VisitorFlow_Admin_Page
{
	protected $hook;
	protected $page_title;
	protected $page_subtitle;
	protected $menu_title;
	protected $min_capability;
	protected $slug;
	protected $screen;
	protected $metabox_cb;
	protected $body_content_cb;

	/**
	 * Constructor class for WP_MetaboxPage
	 * @param $hook - (string) parent page hook
	 * @param $page_title - (string) the browser window title of the page
	 * @param $page_subtitle - (string) the browser window subtitle of the page
	 * @param $menu_title - (string)  the page title as it appears in the menu
	 * @param $min_capability - (string) the capability a user requires to see the page
	 * @param $slug - (string) a slug identifier for this page
	 * @param $metabox_cb - (callback) a callback that adds the metaboxes.
	 * @param (optional) $body_content_cb - (callback) a callback that prints to the page, above the metaboxes.
	 */
	public function __construct($hook, $page_title, $page_subtitle, $menu_title, $min_capability, $slug, $metabox_cb, $body_content_cb='__return_true' ){

		// Call the parent constructor (WP_VisitorFlow_Page::__construct)
		parent::__construct($min_capability);

		$this->hook = $hook;
		$this->page_title = $page_title;
		$this->page_subtitle = $page_subtitle;
		$this->menu_title = $menu_title;
		$this->min_capability = $min_capability;
		$this->slug = $slug;
		$this->metabox_cb = $metabox_cb;
		$this->body_content_cb = $body_content_cb;

		/* Add the page */
		$this->add_page();
	}


	/**
	 * Adds the custom submenu page
	 * Adds callbacks to the load-* and admin_footer-* hooks
	 */
	function add_page(){

		/* Add the screen */
		$this->screen = add_submenu_page($this->hook,$this->page_title, $this->menu_title, $this->min_capability,$this->slug,  array($this,'render_page'),10);

		/* Add callbacks for this screen only */
		add_action('load-'.$this->screen, $this->metabox_cb);
		add_action('load-'.$this->screen, array($this,'page_actions'), 9);
		add_action('admin_footer-'.$this->screen, array($this,'footer_scripts'));
	}


	/**
	 * Returns the screen of this page
	 */
	public function get_screen() {
		return $this->screen;
	}


	/*
	 * Actions to be taken prior to page loading. This is after headers have been set.
     * Called on load-$hook
	 * This calls the add_meta_boxes hooks, adds screen options and enqueues the postbox.js script.
	 */
	function page_actions(){
		do_action('add_meta_boxes_'.$this->screen, null);
		do_action('add_meta_boxes', $this->screen, null);

		/* User can choose between 1 or 2 columns (default 2) */
		add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );

		/* Enqueue WordPress' script for handling the metaboxes */
		wp_enqueue_script('postbox');
	}


	/**
	 * Prints the jQuery script to initialise the metaboxes
	 * Called on admin_footer-*
	 */
	function footer_scripts(){
		?>
		<script> postboxes.add_postbox_toggles(pagenow);</script>
		<?php
	}


	/**
	 * Renders the page
	 */
	function render_page(){
		?>
		 <div class="wrap">

			<div style="float:left;">
				<img src="<?php echo plugin_dir_url( __FILE__ ) . '../../assets/images/Logo_250.png'; ?>" align="left" width="80" height="80px" alt="Logo" />
			</div>

			<h1> <?php echo esc_html($this->page_title);?> </h1>
			<p><?php echo esc_html($this->page_subtitle);?></p>

			<div style="clear:both;"></div>
			<?php $this->printPageMenu(); ?>


			<form name="my_form" method="post">
				<input type="hidden" name="action" value="some-action">
				<?php wp_nonce_field( 'some-action-nonce' );
				/* Used to save closed metaboxes and their order */
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>

				<div id="poststuff">

					 <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

						  <div id="post-body-content">
							<?php call_user_func($this->body_content_cb); ?>
						  </div>

						  <div id="postbox-container-1" class="postbox-container">
						        <?php do_meta_boxes('','side',null); ?>
						  </div>

						  <div id="postbox-container-2" class="postbox-container">
						        <?php do_meta_boxes('','normal',null);  ?>
						        <?php do_meta_boxes('','advanced',null); ?>
						  </div>

					 </div> <!-- #post-body -->

				 </div> <!-- #poststuff -->

	      		  </form>

		 </div><!-- .wrap -->
		<?php
	}

}

endif;	// Prevent multiple class definitions
