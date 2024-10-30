<?php
/**
* Various widgets for ClickSold
*
* Copyright (C) 2012 ClickSold.com
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
* 
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
* 
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

require_once(plugin_dir_path(__FILE__) . '/CS_request.php');
require_once(plugin_dir_path(__FILE__) . '/CS_response.php');
require_once(plugin_dir_path(__FILE__) . '/cs_constants.php');
global $cs_widgets_cssjs_included;
$cs_widgets_cssjs_included = false;

/**
 * Returns true if the widget name is one of the ones defined in this file. Defined UP HERE so that you remember to update
 * this list every time that a new widget is added.
 */
function cs_is_cs_widget( $name ) {
	
	if( $name == 'cs-brokerage-info-widget' )		{ return true; }
	if( $name == 'cs-widget-personal-profile' )		{ return true; }
	if( $name == 'cs-widget-idx-search' )			{ return true; }
	if( $name == 'cs-widget-mobile-site' )			{ return true; }
	if( $name == 'cs-widget-buying-info' )			{ return true; }
	if( $name == 'cs-widget-selling-info' )			{ return true; }
	if( $name == 'cs-listing-quick-search-widget' )	{ return true; }
	if( $name == 'cs-feature-listing-widget' )		{ return true; }
	if( $name == 'cs-vip-widget' )					{ return true; }
	if( $name == 'cs-idx-qs-widget' )				{ return true; }
	if( $name == 'cs-community-search-widget' )		{ return true; }
	if( $name == 'cs-listing-details-page-widget' )	{ return true; }

	return false;
}

/**
*  Base class for ClickSold widgets.  Contains re-usable functions for plugin functionality.
*  @author ClickSold
*/
class CS_Widget extends WP_Widget {
	
	// These cache the response that queried the plugin server for the widget scripts. This is done because the inline scripts and the linked scripts are enqueued in different handlers and we don't want to always have to hit the
	// plugin server twice in order to get them.
	protected $front_widget_scripts_cs_response;
	protected $admin_widget_scripts_cs_response;
	
	function __construct( $class_name, $description, $opts ) {
		
		/**
		 * We only call the parent's constructor if we have decent values. This is because the cs dashboard widget constructs this class so it can call
		 * get_widget_scripts to encueue the widget css (which it needs.
		 */
		if( isset( $class_name ) ) {
			parent::__construct( $class_name, $description, $opts );
		}
	}

	public function fix_async_upload_image() {
		if(isset($_REQUEST['attachment_id'])) {
			$GLOBALS['post'] = get_post($_REQUEST['attachment_id']);
		}
	}
		
	/**
	 * Retrieve resized image URL
	 *
	 * @param int $id Post ID or Attachment ID
	 * @param int $width desired width of image (optional)
	 * @param int $height desired height of image (optional)
	 * @return string URL
	 * @author ClickSold
	 */
	public function get_image_url( $id, $width=false, $height=false ) {
		// Get attachment and resize but return attachment path (needs to return url)
		$attachment = wp_get_attachment_metadata( $id );
		$attachment_url = wp_get_attachment_url( $id );
		if (isset($attachment_url)) {
			if ($width && $height) {
				$uploads = wp_upload_dir();
				$imgpath = $uploads['basedir'].'/'.$attachment['file'];
				//error_log($imgpath);
				$image = image_resize( $imgpath, $width, $height );
				if ( $image && !is_wp_error( $image ) ) {
					//error_log( is_wp_error($image) );
					$image = path_join( dirname($attachment_url), basename($image) );
				} else {
					$image = $attachment_url;
				}
			} else {
				$image = $attachment_url;
			}
			if (isset($image)) {
				return $image;
			}
		}
	}
	
	/**
	 * Test context to see if the uploader is being used for the Personal Profile Widget or for other regular uploads
	 *
	 * @return void
	 * @author ClickSold
	 */
	public function is_in_widget_context() {	
		if ( isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],$this->id_base) !== false ) return true;
		elseif ( isset($_REQUEST['_wp_http_referer']) && strpos($_REQUEST['_wp_http_referer'],$this->id_base) !== false ) return true;
		elseif ( isset($_REQUEST['widget_id']) && strpos($_REQUEST['widget_id'],$this->id_base) !== false ) return true;
		
		return false;
	}
	
	/**
	 * Loads theme files in appropriate hierarchy: 1) child theme, 
	 * 2) parent template, 3) plugin resources. will look in the clicksold
	 * plugin directory in a theme and the views/ directory in the plugin
	 *
	 * @param string $custom_filter_name unique name for filter
	 * @param string $template template file to search for
	 * @return template path
	 * @author ClickSold
	 **/
	public function getTemplateHierarchy($custom_filter_name, $template) {
		// whether or not .php was added
		$template_slug = rtrim($template, '.php');
		$template = $template_slug . '.php';
		
		// get plugin folder name - may not necessarily be "clicksold"
		$plugin_folder = plugin_basename(__FILE__);
		$plugin_folder = str_ireplace("widgets.php", "", $plugin_folder);
		
		if ( $theme_file = locate_template(array($plugin_folder.$template)) ) {
			$file = $theme_file;
		} else {
			$file = 'views/' . $template;
		}
		
		return apply_filters( $custom_filter_name . $template, $file);
	}
		
	/**
	 * Loads the widget's translated strings (if any, for localization)
	 */
	public function loadPluginTextDomain() {
		load_plugin_textdomain( $this->pluginDomain, false, trailingslashit(basename(dirname(__FILE__))) . 'lang/');
	}
	
	/**
	 * DEPRECATED: Enqueue style-file, if it exists.
	 */
	function add_stylesheet() {
		$styleUrl = plugins_url('/css/' . $this->widget_stylesheet, __FILE__);
		$styleFile = plugin_dir_path(__FILE__) . '/css/' . $this->widget_stylesheet;
		
		if ( file_exists($styleFile) ) {
		    wp_register_style( $this->pluginDomain . '_stylesheet', $styleUrl );
		    wp_enqueue_style( $this->pluginDomain . '_stylesheet' );
		}
	}
	
	/**
	 * General call to enqueue script calls to the server for widget Javascript & CSS files
	 */
	public function get_widget_scripts($admin = false) {
		global $cs_widgets_cssjs_included;
		
		if($cs_widgets_cssjs_included === true) return;
		else $cs_widgets_cssjs_included = true;
		
		$cs_response = null;
		
		// Enqueue CSS & JS libs
		if($admin === true) {
			add_action('admin_enqueue_scripts', array($this, 'get_admin_widget_scripts'));	
			add_action('admin_head', array($this, 'get_admin_widget_inline_scripts'));
		} else {
			add_action('wp_enqueue_scripts', array($this, 'get_front_widget_scripts'));
			add_action('wp_head', array($this, 'get_front_widget_inline_scripts'));
		}
	}
	
	/**
	 *  Retrieves Javascript / CSS scripts used in widgets.php
	 */
	public function get_front_widget_scripts() {
		
		// If we have not yet queried for the widget scripts let's do so now.
		if( !isset( $this->front_widget_scripts_cs_response ) ) {
			$cs_request = new CS_request("pathway=590", null);
			$this->front_widget_scripts_cs_response = new CS_response($cs_request->request());
		}
		if(!$this->front_widget_scripts_cs_response->is_error()) $this->front_widget_scripts_cs_response->cs_get_header_contents_linked_only();
	}
	
	/**
	 *  Retrieves inline Javascript for widgets
	 */
	public function get_front_widget_inline_scripts() {

		// If we have not yet queried for the widget scripts let's do so now.
		if( !isset( $this->front_widget_scripts_cs_response ) ) {
			$cs_request = new CS_request("pathway=590", null);
			$this->front_widget_scripts_cs_response = new CS_response($cs_request->request());
		}
		if(!$this->front_widget_scripts_cs_response->is_error()) $this->front_widget_scripts_cs_response->cs_get_header_contents_inline_only();
	}
	
	/**
	 *  Retrieves Javascript / CSS scripts used in widgets.php called in admin area
	 */
	public function get_admin_widget_scripts(){
		global $CS_SECTION_ADMIN_PARAM_CONSTANT;

		// If we have not yet queried for the widget scripts let's do so now.
		if( !isset( $this->admin_widget_scripts_cs_response ) ) {
			$cs_request = new CS_request("pathway=591", $CS_SECTION_ADMIN_PARAM_CONSTANT["wp_admin_pname"]);
			$this->admin_widget_scripts_cs_response = new CS_response($cs_request->request());
		}
		if(!$this->admin_widget_scripts_cs_response->is_error()) $this->admin_widget_scripts_cs_response->cs_get_header_contents_linked_only();
	}
	
	/**
	 *  Retrieves inline Javascript for widgets called in admin area
	 */
	public function get_admin_widget_inline_scripts(){
		global $CS_SECTION_ADMIN_PARAM_CONSTANT;

		// If we have not yet queried for the widget scripts let's do so now.
		if( !isset( $this->admin_widget_scripts_cs_response ) ) {
			$cs_request = new CS_request("pathway=591", $CS_SECTION_ADMIN_PARAM_CONSTANT["wp_admin_pname"]);
			$this->admin_widget_scripts_cs_response = new CS_response($cs_request->request());
		}
		if(!$this->admin_widget_scripts_cs_response->is_error()) $this->admin_widget_scripts_cs_response->cs_get_header_contents_inline_only();
	}
	
	/**
	 * Calls wp_enqueue_media() 
	 */
	public function call_admin_media_upload_files(){
		wp_enqueue_media();
	}	
	
	/**
	 * Turns off usage of the WP 3.5 media uploader if returns false
	 */
	private function activate_new_media_upload() {
		return true;
	}
	
	/**
	 *  Returns boolean on whether or not the WP version is 3.5 or higher
	 */
	public function use_new_media_upload() {
		global $wp_version;
		if($this->activate_new_media_upload() == false) return false;
		return version_compare($wp_version, '3.5', '>=');
	}
	
	/**
	 *  Initializes media upload functionality
	 */
	public function init_media_upload() {
		global $wp_version;
		if ( $this->activate_new_media_upload() == false || version_compare($wp_version, '3.5', '<') )
			$this->init_old_media_upload();
		else
			$this->init_new_media_upload();
	}
	
/** Old Media Upload **/
	
	public function init_old_media_upload() {
		global $pagenow;
		add_action( 'admin_init', array( $this, 'fix_async_upload_image' ) );
		if ( 'widgets.php' == $pagenow ) {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script( 'thickbox' );
			$this->get_widget_scripts(true);
		} else if ( 'media-upload.php' == $pagenow || 'async-upload.php' == $pagenow ) {
			add_filter( 'image_send_to_editor', array( $this,'image_send_to_editor'), 1, 8 );
			add_filter( 'gettext', array( $this, 'replace_text_in_thickbox' ), 1, 3 );
			add_filter( 'media_upload_tabs', array( $this, 'media_upload_tabs' ) );
		}
	}
	
	/**
	 * Filter image_end_to_editor results
	 *
	 * @param string $html 
	 * @param int $id 
	 * @param string $alt 
	 * @param string $title 
	 * @param string $align 
	 * @param string $url 
	 * @param array $size 
	 * @return string javascript array of attachment url and id or just the url 
	 * @author ClickSold 
	 */
	public function image_send_to_editor( $html, $id, $caption, $title, $align, $url, $size, $alt = '' ) {
		// Normally, media uploader return an HTML string (in this case, typically a complete image tag surrounded by a caption).
		// Don't change that; instead, send custom javascript variables back to opener.
		// Check that this is for the widget. Shouldn't hurt anything if it runs, but let's do it needlessly.
		if ( $this->is_in_widget_context() ) {
			if ($alt=='') $alt = $title;
			?>
			<script type="text/javascript">
				// send image variables back to opener
				var win = window.dialogArguments || opener || parent || top;
				win.IW_html = '<?php echo addslashes($html) ?>';
				win.IW_img_id = '<?php echo $id ?>';
				win.IW_size = '<?php echo $size ?>';
			</script>
			<?php
		}
		return $html;
	}
	
	/**
	 * Somewhat hacky way of replacing "Insert into Post" with "Insert into Widget"
	 *
	 * @param string $translated_text text that has already been translated (normally passed straight through)
	 * @param string $source_text text as it is in the code
	 * @param string $domain domain of the text aka $this->pluginDomain
	 * @return void
	 * @author ClickSold
	 */
	public function replace_text_in_thickbox($translated_text, $source_text, $domain) {	
		if ( $this->is_in_widget_context() ) {
			if ('Insert into Post' == $source_text) {
				return __('Insert Into Widget', $this->pluginDomain );
			}
		}
		return $translated_text;
	}
	
	/**
	 * Remove from url tab until that functionality is added to widgets.
	 *
	 * @param array $tabs 
	 * @return void
	 * @author ClickSold
	 */
	public function media_upload_tabs($tabs) {
		if ( $this->is_in_widget_context() ) {
			unset($tabs['type_url']);
		}
		return $tabs;
	}
	
/** New Media Upload **/
	public function init_new_media_upload() {
		global $pagenow;
		if ( 'widgets.php' == $pagenow ) {
			add_action('admin_enqueue_scripts', array($this, 'call_admin_media_upload_files'));
			$this->get_widget_scripts(true);
		}
	}
	
	/**
	 * Gets a region list in JSON format -- This one is used when the front end widget loads.
	 * 
	 * - Used by the Community Search Widget
	 * - Used by the IDX Quick Search Widget
	 */
	public function get_community_search_regions($instance, $widget_id){
		global $CS_SECTION_PARAM_CONSTANTS;
		
		$widget_id_parts = explode("-", $widget_id);
		$params = '&incOrExcSelected=' . $instance['incOrExcSelected'] . '&widgetId=' . end($widget_id_parts);
		if(array_key_exists('cities', $instance) && !empty($instance['cities'])) {
			foreach($instance['cities'] as $city) {
				$params .= '&cities[]=' . $city;
			}
		}
		
		$cs_request = new CS_request('pathway=659' . $params, $CS_SECTION_PARAM_CONSTANTS["community_pname"]);
		$cs_response = new CS_response($cs_request->request());
		if($cs_response->is_error()) return;
		
		return $cs_response->cs_get_json();
	}

	/**
	 * Gets a region list -- This one is used when the back end widget loads.
	 * 
	 * - Used by the Community Search Widget
	 * - Used by the IDX Quick Search Widget
	 */
	public function get_init_config_community_search_regions($instance){
		global $CS_SECTION_ADMIN_PARAM_CONSTANT;
		
		$params = '&incOrExcSelected=' . $instance['incOrExcSelected'] . '&load_community_search_opts=true';
		if(array_key_exists('cities', $instance) && !empty($instance['cities'])) {
			foreach($instance['cities'] as $city) {
				$params .= '&cities[]=' . $city;
			}
		}
		
		$cs_request = new CS_request('pathway=640' . $params, $CS_SECTION_ADMIN_PARAM_CONSTANT["wp_admin_pname"]);
		$cs_response = new CS_response($cs_request->request());
		if($cs_response->is_error()) return;
		
		return $cs_response->get_body_contents();
	}
	
	/**
	 * Gets the relative url to a ClickSold section. Taking into account if the wp install is using permalinks or is a subdirectory install.
	 * - Used mainly by the widgets in widgets-search.php but also any widget that needs to forward to a specific cs section.
	 * 
	 * $page_name - one of listings, idx or community.
	 * 
	 * Returns: page_id=xyz type link if not using permalinks or <subdir>/<section> if using them.
	 * 
	 */
	public function get_cs_section_relative_url( $page_name ) {
		global $wpdb;
		global $wp_rewrite;
		global $CS_GENERATED_PAGE_PARAM_CONSTANTS;
				
		$table_name = $wpdb->prefix . "cs_posts";
		
		//Get the relative url to the IDX search page
		$page = $wpdb->get_row("SELECT postid, parameter FROM " . $table_name . " WHERE parameter = '" . $CS_GENERATED_PAGE_PARAM_CONSTANTS[ $page_name ] . "'");
		
		$page_id = $page->postid;
		if(is_null($page_id)) return null;
		
		$using_permalinks = $wp_rewrite->using_permalinks();
		
		// Get the pathname or query string of those pages
		if( $using_permalinks ) {
			
			$page_url = $wpdb->get_var("SELECT post_name FROM " . $wpdb->posts . " WHERE ID = " . $page_id . " AND post_type = 'page' AND post_status != 'trash'");
			
		} else {

			$page_url = $wpdb->get_var("SELECT guid FROM " . $wpdb->posts . " WHERE ID = " . $page_id . " AND post_type = 'page' AND post_status != 'trash'");
			
			//Strip the root url
			$patt = "/\/\?/";
			$page_url_parts = preg_split($patt, $page_url);
			
			//Check if the guid is valid
			if(count($page_url_parts) < 2) return null;
			
			$page_url = "?" . $page_url_parts[1];
		}
				
		if(is_null($page_url)) return null;
		
		// In order to support subdirectory installs we must add the directory to the page_url (if present).
		if( parse_url(site_url(), PHP_URL_PATH) != '' ) { // If it's a subdir install.
			
			// Remember the path has a leading / that we need to get rid of (the js adds this in if needed).
			$page_url = substr( parse_url(site_url(), PHP_URL_PATH), 1 ) . '/' . $page_url;
		}

		return $page_url;
	}
	
}

/**
 * Feature Listing Widget
 * @author ClickSold
 */
class Feature_Listing_Widget extends CS_Widget{
	
	private $PLUGIN_NAME = 'ClickSold Feature Listing Widget';
	private $PLUGIN_SLUG = 'cs-feature-listing-widget';
	private $PLUGIN_CLASSNAME = 'widget-feature-listing';
	private $PLUGIN_FEAT_LIST_OPTS = array();
	private $BROKERAGE = false;
	private $IDX = false;
	
	function __construct(){
	
		global $PLUGIN_NAME;
		global $PLUGIN_SLUG;
		global $PLUGIN_CLASSNAME;
		global $BROKERAGE;
		global $IDX;
		
		$this->pluginDomain = 'feature_listing_widget';
		
		$this->loadPluginTextDomain();
		$widget_opts = array(
			'classname' => $this->PLUGIN_CLASSNAME,
			'description' => 'Add a section for viewing your listings'
		);
		
		parent::__construct( $this->PLUGIN_SLUG, $this->PLUGIN_NAME, $widget_opts );
		
		global $pagenow;
		
		$this->BROKERAGE = (bool) get_option("cs_opt_brokerage", "");
		
		if(get_option("cs_opt_tier_name", "") == "Platinum") $this->IDX = true;
		else $this->IDX = false;
		
		if( defined( "WP_ADMIN" ) && WP_ADMIN && 'widgets.php' == $pagenow ) {
			$this->get_feature_listing_options();
			$this->get_widget_scripts(true);
		} else if( is_admin() === false && is_active_widget(false, false, $this->id_base, true) && !wp_script_is($this->PLUGIN_SLUG . '-js') ) {
			$this->get_widget_scripts(false);
		}
	}
	
	function widget( $args, $instance ){
		global $wpdb;
		global $wp_rewrite;
		global $CS_GENERATED_PAGE_PARAM_CONSTANTS;
		global $blog_id;

		// Check if we should even display anything in the first place
		if($this->show_widget($instance) == false) return;
		
		$table_name = $wpdb->prefix . "cs_posts";
		
		//Get the ids of the idx and listings pages
		$page = $wpdb->get_row("SELECT postid, parameter FROM " . $table_name . " WHERE parameter = '" . $CS_GENERATED_PAGE_PARAM_CONSTANTS["listings"] . "'");
		
		$listings_id = $page->postid;
		
		if(is_null($listings_id)) return;
		
		// Get the pathname or query string of those pages
		if( $wp_rewrite->using_permalinks() ) $listings_url = $wpdb->get_var("SELECT post_name FROM " . $wpdb->posts . " WHERE ID = " . $listings_id . " AND post_type = 'page' AND post_status != 'trash'");
		else $listings_url = $wpdb->get_var("SELECT guid FROM " . $wpdb->posts . " WHERE ID = " . $listings_id . " AND post_type = 'page' AND post_status != 'trash'");
		
		if(is_null($listings_url)) return;
		
		// Partial url for exclusive listings
		$listings_excl_url = $listings_url;
		
		if($wp_rewrite->using_permalinks()) {
			$listings_url .= '/';
			$listings_excl_url .= '/exclusive-';
		} else { 
			$listings_url .= '&mlsNum='; 
			$listings_excl_url .= '&listNum=';
		}
		
		// Turn urls absolute
		if(method_exists($this, 'is_multisite') && is_multisite()) {
			$listings_url = network_home_url($listings_url);
			$listings_excl_url = network_home_url($listings_excl_url);
		} else {
			$listings_url = home_url($listings_url);
			$listings_excl_url = home_url($listings_excl_url);
		}
		
		if( !array_key_exists('listing_type', $instance) || empty($instance['listing_type']) ) $instance['listing_type'] = 0;
		if( !array_key_exists('listing_status', $instance) || empty($instance['listing_status']) ) $instance['listing_status'] = 0;
		if( !array_key_exists('numDisp', $instance) || empty($instance['numDisp']) ) $instance['numDisp'] = '1';
		if( !array_key_exists('vertical', $instance)) $instance['vertical'] = true;
		if( !array_key_exists('arrows_on_slick_slider', $instance)) $instance['arrows_on_slick_slider'] = true;
		if( !array_key_exists('fade', $instance)) $instance['fade'] = true;
		if( !array_key_exists('listing_set_shift', $instance)) $instance['listing_set_shift'] = 0;
		if( !array_key_exists('widget_type', $instance) || empty($instance['widget_type']) ) $instance['widget_type'] = 'legacy';
		if( !array_key_exists('minCntWidth', $instance)) $instance['minCntWidth'] = '';
		if( !array_key_exists('maxCntWidth', $instance)) $instance['maxCntWidth'] = '';
		if( !array_key_exists('minCntHeight', $instance)) $instance['minCntHeight'] = '';
		if( !array_key_exists('maxCntHeight', $instance)) $instance['maxCntHeight'] = '';
		if( !array_key_exists('listingPhotoContainer_style', $instance)) $instance['listingPhotoContainer_style'] = '';
		if( !array_key_exists('break_points_for_slick_slider', $instance)) $instance['break_points_for_slick_slider'] = '';
		
		extract( $args );
		extract( $instance );
		include( $this->getTemplateHierarchy( 'cs_template_feature-listing-widget_', 'feature-listing-widget' ) );
	}
	
	function update( $new_instance, $old_instance ){
		global $PLUGIN_FEAT_LIST_OPTS;
		global $BROKERAGE;
		global $IDX;
		
		if(empty($old_instance)) {
			if(empty($PLUGIN_FEAT_LIST_OPTS)) $this->get_feature_listing_options();
			$instance['listing_section'] = $PLUGIN_FEAT_LIST_OPTS['listing_section']['values'][0]['opt_val'];
			$instance['listing_type'] = $PLUGIN_FEAT_LIST_OPTS['listing_type']['values'][0]['opt_val'];
			$instance['listing_status'] = $PLUGIN_FEAT_LIST_OPTS['listing_status']['values'][0]['opt_val'];
			$instance['listing_set_shift'] = $PLUGIN_FEAT_LIST_OPTS['listing_set_shift']['values'][0]['opt_val'];
			$instance['widget_type'] = 'legacy';
		} else {
			$instance['title'] = $new_instance['title'];
			$instance['listing_type'] = $new_instance['listing_type'];
			$instance['listing_status'] = $new_instance['listing_status'];
			$instance['listing_set_shift'] = $new_instance['listing_set_shift'];
			$instance['widget_type'] = $new_instance['widget_type'];

			if($new_instance['listing_section'] == 3 && empty($new_instance['user_defined_listings'])) {
				$instance['listing_section'] == $old_instance['listing_section'];
				if(!empty($old_instance['user_defined_listings'])) $instance['user_defined_listings'] = $old_instance['user_defined_listings'];
			} else {
				$instance['listing_section'] = $new_instance['listing_section'];
				$instance['user_defined_listings'] = $new_instance['user_defined_listings'];
			}
		}
		
		if(empty($new_instance['freq']) || (int) $new_instance['freq'] < 1000) {
			$instance['freq'] = "1000";
		} else {
			$instance['freq'] = $new_instance['freq'];
		}
			
		if(empty($new_instance['numDisp']) || !is_numeric($new_instance['numDisp']) || ((int) $new_instance['numDisp'] < 1 && (int) $new_instance['numDisp'] > 10) ) {
			$instance['numDisp'] = "1";
		} else {
			$instance['numDisp'] = $new_instance['numDisp'];
		}
		
		$instance['vertical'] = $new_instance['vertical'];
		$instance['arrows_on_slick_slider'] = $new_instance['arrows_on_slick_slider'];
		$instance['fade'] = $new_instance['fade'];
		$instance['minCntWidth'] = $new_instance['minCntWidth'];
		$instance['maxCntWidth'] = $new_instance['maxCntWidth'];
		$instance['minCntHeight'] = $new_instance['minCntHeight'];
		$instance['maxCntHeight'] = $new_instance['maxCntHeight'];
		$instance['listingPhotoContainer_style'] = $new_instance['listingPhotoContainer_style'];
		$instance['break_points_for_slick_slider'] = $new_instance['break_points_for_slick_slider'];
		
		return $instance;
	}
	
	function form( $instance ){	
		global $PLUGIN_FEAT_LIST_OPTS;
		global $BROKERAGE;
		global $IDX;
		
		if(empty($PLUGIN_FEAT_LIST_OPTS)) $this->get_feature_listing_options();
		
		$listing_section_label = $PLUGIN_FEAT_LIST_OPTS['listing_section']['label'];
		$listing_type_label = $PLUGIN_FEAT_LIST_OPTS['listing_type']['label'];
		$listing_status_label = $PLUGIN_FEAT_LIST_OPTS['listing_status']['label'];
		$listing_set_shift_label = $PLUGIN_FEAT_LIST_OPTS['listing_set_shift']['label'];
		
		$instance_opts = array(
			'listing_section' => $PLUGIN_FEAT_LIST_OPTS['listing_section']['values'][0]['opt_val'],
			'listing_type' => $PLUGIN_FEAT_LIST_OPTS['listing_type']['values'][0]['opt_val'],
			'listing_status' => $PLUGIN_FEAT_LIST_OPTS['listing_status']['values'][1]['opt_val'],
			'listing_set_shift' => $PLUGIN_FEAT_LIST_OPTS['listing_set_shift']['values'][0]['opt_val'],
			'vertical' => true,
			'arrows_on_slick_slider' => false,
			'fade' => false,
			'widget_type' => 'legacy',
			'freq' => '10000',
			'title' => '',
			'numDisp' => '1',
			'minCntWidth' => '',
			'maxCntWidth' => '',
			'minCntHeight' => '',
			'maxCntHeight' => '',
			'listingPhotoContainer_style' => '',
			'break_points_for_slick_slider' => '',
			'useDefault' => true,
			'user_defined_listings' => array()
		);
		
		$instance = wp_parse_args((array) $instance, $instance_opts);
		include( $this->getTemplateHierarchy( 'cs_template_feature-listing-widget_', 'feature-listing-widget-admin' ) );
	}
	
   /**
	*  Gets the "Show listings from section" and listing type options for the feature listing widget
	*/
	private function get_feature_listing_options(){
		global $CS_SECTION_ADMIN_PARAM_CONSTANT;
		global $PLUGIN_FEAT_LIST_OPTS;
		
		$cs_request = new CS_request("pathway=604", $CS_SECTION_ADMIN_PARAM_CONSTANT["wp_admin_pname"]);
		$cs_response = new CS_response($cs_request->request());
		if($cs_response->is_error()) return; 
		
		$json_response = $cs_response->cs_get_json();
		$PLUGIN_FEAT_LIST_OPTS = $json_response['featListWidgetOpts'];
	}

	/**
	 *  Tells us whether or not to show the widget based on if any results will be returned
	 */
	private function show_widget($instance){
		global $CS_SECTION_PARAM_CONSTANTS;
		
		$params = '&listSection=' . $instance['listing_section'] . '&listType=' . $instance['listing_type'] . '&listStatus=' . $instance['listing_status'];
		if(array_key_exists('numDisp', $instance) && !empty($instance['numDisp'])) {
			$params .= '&numDisp=' . $instance['numDisp'];
		} else {
			$params .= '&numDisp=1';
		}
				
		if(array_key_exists('user_defined_listings', $instance) && !empty($instance['user_defined_listings'])) {
			foreach($instance['user_defined_listings'] as $mlsNum) {
				$params .= '&userDefinedListings[]=' . $mlsNum;
			}
		}
		
		$cs_request = new CS_request('pathway=9' . $params, $CS_SECTION_PARAM_CONSTANTS["listings_pname"]);
		$cs_response = new CS_response($cs_request->request());
		if($cs_response->is_error()) return false;
		
		$json_response = $cs_response->cs_get_json();
		if($json_response['total'] > 0) return true;
		else return false;
	}
}

/**
 * VIP Options Widget
 * @author ClickSold
 */
class VIP_Widget extends CS_Widget{
	
	private $PLUGIN_NAME = 'ClickSold VIP Widget';
	private $PLUGIN_SLUG = 'cs-vip-widget';
	private $PLUGIN_CLASSNAME = 'widget-vip';
	private $BROKERAGE = false;
	
	function __construct(){
		
		global $PLUGIN_NAME;
		global $PLUGIN_SLUG;
		global $PLUGIN_CLASSNAME;
		global $BROKERAGE;
		
		$this->pluginDomain = 'vip_widget';
		
		$this->loadPluginTextDomain();
		$widget_opts = array(
			'classname' => $this->PLUGIN_CLASSNAME,
			'description' => 'Adds the ClickSold VIP feature to any of your pages.'
		);
		
		parent::__construct( $this->PLUGIN_SLUG, $this->PLUGIN_NAME, $widget_opts );
		
		global $pagenow;
		
		$this->BROKERAGE = (bool) get_option("cs_opt_brokerage", "");
		
		if( defined( "WP_ADMIN" ) && WP_ADMIN && 'widgets.php' == $pagenow ) {
			$this->get_widget_scripts(true);
		}else if( is_admin() === false && is_active_widget(false, false, $this->id_base, true) && !wp_script_is($this->PLUGIN_SLUG . '-js') ) {
			$this->get_widget_scripts(false);
		}
	}
	
	function widget( $args, $instance ){
		
		global $CS_SECTION_VIP_PARAM_CONSTANT;
	
		$cs_request = new CS_request("pathway=168&vipLoginCheck=true", $CS_SECTION_VIP_PARAM_CONSTANT["wp_vip_pname"]);
		$cs_response = new CS_response($cs_request->request());
		if($cs_response->is_error()) return;
		
		$json_response = $cs_response->cs_get_json();
		
		$hideVIPOpts = "";
		if( !empty($instance['hideOpts']) ) $hideVIPOpts = "display:none;";
		
		extract( $args );
		extract( $instance );
		include( $this->getTemplateHierarchy( 'cs_template_vip-widget_', 'vip-widget' ) );
	}
	
	function update( $new_instance, $old_instance ){
		$instance = $new_instance;
		if(empty($instance['hideOpts'])) $instance['hideOpts'] = 0;
		else $instance['hideOpts'] = 1;
		return $instance;
	}
	
	function form( $instance ){
		$instance_opts = array(
			'title' => 'VIP Options',
			'hideOpts' => 1
		);
		$instance = wp_parse_args((array) $instance, $instance_opts);
		include( $this->getTemplateHierarchy( 'cs_template_vip-widget_', 'vip-widget-admin' ) );
	}
	
}

/**
 * Listing Details Page Widget
 * @author ClickSold
 */
class Listing_Details_Page_Widget extends CS_Widget{
	
	private $PLUGIN_NAME = 'ClickSold Listing Details Page Widget';
	private $PLUGIN_SLUG = 'cs-listing-details-page-widget';
	private $PLUGIN_CLASSNAME = 'widget-listing-details-page';
	
	function __construct(){
		global $PLUGIN_NAME;
		global $PLUGIN_SLUG;
		global $PLUGIN_CLASSNAME;
		
		$this->pluginDomain = 'listing_details_page_widget';
		
		$this->loadPluginTextDomain();
		$widget_opts = array(
			'classname' => $this->PLUGIN_CLASSNAME,
			'description' => 'Displays additional features when appearing on a listing details page.'
		);
		
		parent::__construct( $this->PLUGIN_SLUG, $this->PLUGIN_NAME, $widget_opts );
		
		global $pagenow;
		if( defined( "WP_ADMIN" ) && WP_ADMIN && 'widgets.php' == $pagenow ) {
			$this->get_widget_scripts(true);
		} else if( is_admin() === false && is_active_widget(false, false, $this->id_base, true) && !wp_script_is($this->PLUGIN_SLUG . '-js') ) {
			$this->get_widget_scripts(false);
		}
	}
	
	
	function widget( $args, $instance ){
		global $CS_SECTION_PARAM_CONSTANTS;
		
		// Extract all of the values here and place them into variables -- this is where all of the seemingly missing variables are from when we get down to the widget php files.
		extract( $args );
		extract( $instance );

		/**
		 * Determine if we are being displayed on a listings details page.
		 * 
		 * - conveniantly the rewrite rules give us the mlsnum -- parameter.
		 */
		global $wp_query;
		
		// Bail on all of the queries that we're not supposed to be doing anything for (aka non listing details pages).
		if( !isset( $wp_query->query ) ) { return; }
		if( !isset( $wp_query->query['mlsnum'] ) ) { return; }
		
		// This value can either be the listnum or the mlsnum.
		$mlsnum = null;
		$listnum = null;

		if( cs_str_starts_with( $wp_query->query['mlsnum'], "exclusive-") ) { // This is an exclusive listing and we need to parse out the listnum.
			
			// The format of this is exclusive-<list num>-<some other value according to whatever they configured in their query stings format>
			// eg: exclusive-12508988-123_-_Main_St*Edmonton
			if(preg_match("/^exclusive-([^-]*)-?.*$/", $wp_query->query['mlsnum'], $matches)) {
				$listnum = $matches[1];
			}

		} else { // This is an mls listing.

			// The format of this is <mls num>-<some other value according to whatever they configured in their query stings format>
			// eg: E308988-123_-_Main_St*Edmonton
			if(preg_match("/^([^-]*)-?.*$/", $wp_query->query['mlsnum'], $matches)) {
				$mlsnum = $matches[1];
			}
			
		}

		// Finally if both Mls Num and ListNum is still null, well we did not get enough info to actually proceed.
		if( $mlsnum == null && $listnum == null) { return; }

		/**
		 * Now that we have either the mlsnumber or the listing number we can make the call to the api to grab the listing's location.
		 */

		// Ping the server for the listing information.
		$params = '';
		if( $mlsnum != null ) {
			$params = '&mlsNumber=' . $mlsnum;
		} else {
			$params = '&listingNumber=' . $listnum;
		}

		$cs_request = new CS_request('pathway=722' . $params, $CS_SECTION_PARAM_CONSTANTS["listings_pname"]);
		$cs_response = new CS_response($cs_request->request());
		if($cs_response->is_error()) return;
		
		// Check the response... we see if it's got a chance to be json, if not we quit here -- this is cause the $cs_response->cs_get_json throws an eval() error if it's not json.
		if( !cs_str_starts_with($cs_response->get_body_contents(), "{") && !cs_str_ends_with($cs_response->get_body_contents(), "}") ) { return; }
		$listing_details_json = $cs_response->cs_get_json();

		/**
		 * Depending on the type of widget that we are -- enable some extra processing.
		 */
		if( $instance['widget_info_type'] == "map-loc" ) {
			
			// This one needs map coords, if we don't have them we don't show the widget.
			if( $listing_details_json['latitude'] == ''    ) { return; }			if( $listing_details_json['longitude'] == ''    ) { return; }
			if( $listing_details_json['latitude'] == '0'   ) { return; }			if( $listing_details_json['longitude'] == '0'   ) { return; }
			if( $listing_details_json['latitude'] == '0.0' ) { return; }			if( $listing_details_json['longitude'] == '0.0' ) { return; }
			
		} else if( $instance['widget_info_type'] == "walkscore" ) {
			// Defered for now.
			return;
		} else if( $instance['widget_info_type'] == "streetview" ) {

			// This one needs map coords, if we don't have them we don't show the widget.
			if( $listing_details_json['latitude'] == ''    ) { return; }			if( $listing_details_json['longitude'] == ''    ) { return; }
			if( $listing_details_json['latitude'] == '0'   ) { return; }			if( $listing_details_json['longitude'] == '0'   ) { return; }
			if( $listing_details_json['latitude'] == '0.0' ) { return; }			if( $listing_details_json['longitude'] == '0.0' ) { return; }

		} else if( $instance['widget_info_type'] == "featured_info" ) {
			// Defered for now.
			return;
		}

		include( $this->getTemplateHierarchy( 'cs_template_listing-details-page-widget_', 'listing-details-page-widget' ) );
	}
	
	function update( $new_instance, $old_instance ){

		if(empty($old_instance)) {
			$instance['title']            = '';
			$instance['widget_info_type'] = 'map-loc';
			$instance['widget_height']    = '265';
		} else {
			$instance['title']            = $new_instance['title'];
			$instance['widget_info_type'] = $new_instance['widget_info_type'];
			$instance['widget_height']    = $new_instance['widget_height'];
		}

		return $instance;
	}
	
	function form( $instance ){	
		$instance_opts = array(
			'title' => '',
			'widget_info_type' => 'map-loc',
			'widget_height' => '265',
		);
		
		$instance = wp_parse_args((array) $instance, $instance_opts);
		include( $this->getTemplateHierarchy( 'cs_template_listing-details-page-widget_', 'listing-details-page-widget-admin' ) );
	}
}

/**
 * Add all of the other widget files that are grouped together in other files.
 */
require_once(plugin_dir_path(__FILE__) . '/widgets-buttons.php');
require_once(plugin_dir_path(__FILE__) . '/widgets-profiles.php');
require_once(plugin_dir_path(__FILE__) . '/widgets-search.php');

?>
