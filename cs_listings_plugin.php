<?php
/*
Plugin Name: ClickSold IDX
Author: ClickSold | <a href="http://www.ClickSold.com">Visit plugin site</a>
Version: 1.90
Description: This plugin allows you to have a full map-based MLS&reg; search on your website, along with a bunch of other listing tools. If you need wordpress hosting go to <a href="http://www.clicksold.com/">www.ClickSold.com</a> to sign up for an account. Alternatively you can sign up for an account directly from the WP admin area, ClickSold(Menu) -> My Account -> Plugin Activation (Tab).
Author URI: http://www.ClickSold.com/
*/
/** NOTE NOTE NOTE NOTE ---------------------- The plugin version here must match what is in the header just above -----------------------*/
global $cs_plugin_version;
$cs_plugin_version = '1.90';

global $cs_plugin_type;
$cs_plugin_type = 'cs_listings_plugin';

require_once('cs_constants.php');

global $cs_db_version;
$cs_db_version =  "1.1"; 	// change this db version,deactivate,activate the plugin
							// to regenerate the table that it uses

global $cs_posts_table;
$cs_posts_table = "cs_posts";

// options we will add to the wp_options table
global $cs_opt_plugin_key;	/** NOTE: These are also used in wp-mu-control.php plz update that as well if changing the names of the options. **/
$cs_opt_plugin_key     = "cs_opt_plugin_key";
global $cs_opt_plugin_num;
$cs_opt_plugin_num     = "cs_opt_plugin_num";
global $cs_opt_plugin_hostname;
$cs_opt_plugin_hostname = "cs_opt_plugin_hostname";
global $cs_opt_brokerage;
$cs_opt_brokerage = "cs_opt_brokerage";
global $cs_change_products_request;
$cs_change_products_request = "cs_change_products_request";
global $cs_opt_first_login;
$cs_opt_first_login = "cs_opt_first_login";
global $cs_opt_tier_name;
$cs_opt_tier_name = "cs_opt_tier_name";
global $cs_opt_acct_type;
$cs_opt_acct_type = "cs_opt_acct_type";

// options for the auto blogger
global $cs_autoblog_new;
$cs_autoblog_new = 'cs_autoblog_new';
global $cs_autoblog_sold;
$cs_autoblog_sold = 'cs_autoblog_sold';
global $cs_autoblog_last_update;
$cs_autoblog_last_update = 'cs_autoblog_last_update';
global $cs_autoblog_freq;
$cs_autoblog_freq = 'cs_autoblog_freq';
global $cs_autoblog_default_post_title_active;   // These four are the default values.
global $cs_autoblog_default_post_title_sold;     //     "
global $cs_autoblog_default_post_content_active; //     "
global $cs_autoblog_default_post_content_sold;   //     "
$cs_autoblog_new_title = 'cs_autoblog_new_title';
$cs_autoblog_new_content = 'cs_autoblog_new_content';
$cs_autoblog_sold_title = 'cs_autoblog_sold_title';
$cs_autoblog_sold_content = 'cs_autoblog_sold_content';

// CS delayed shortcode insertion system - this holds the captured output of cs_shortcodes if this option is being used.
global $cs_delayed_shortcodes_captured_values;

// initial values for this plugin. By default the plugin key
// and plugin number are empty. These values can be updated
// once the plugin is activated by calling ClickSold.
global $cs_plugin_options;
$cs_plugin_options = array(
  $cs_opt_plugin_key     => "",
  $cs_opt_plugin_num     => "",
  $cs_opt_brokerage => "0",
  $cs_change_products_request => "0",
  $cs_autoblog_new => "0",
  $cs_autoblog_sold => "0",
  $cs_autoblog_last_update => "0",
  $cs_autoblog_freq => "1",
  $cs_autoblog_new_title => $cs_autoblog_default_post_title_active,
  $cs_autoblog_new_content => $cs_autoblog_default_post_content_active,
  $cs_autoblog_sold_title => $cs_autoblog_default_post_title_sold,
  $cs_autoblog_sold_content => $cs_autoblog_default_post_content_sold
);

global $cs_logo_path;
$cs_logo_path = plugins_url("orbGreen.png", __FILE__);

global $cs_response;

global $wpdb;

// A hash of all of the names of our included scripts.
// $cs_included_script_names['header'] - list of header includes.
// $cs_included_script_names['footer'] - list of footer includes.
// *** Scripts only no css.
global $cs_included_script_names;
$cs_included_script_names = array();
$cs_included_script_names['header'] = array();
$cs_included_script_names['footer'] = array();

//Include the WP_Http class used for making HTTP requests
if ( !class_exists( 'WP_Http' ) ) :
include_once( ABSPATH . WPINC. '/class-http.php' );
endif;

//Include the CS_Rewrite class to create dynamic rewrite rules for the plugin
if( !class_exists('CS_rewrite') ):
include_once( plugin_dir_path(__FILE__) . 'CS_rewrite.php');
endif;

require_once('CS_request.php');
require_once('CS_response.php');
require_once('CS_shortcodes.php');
require_once('CS_admin.php'); // 2014-10-31 - EZ - this can't be loaded selectively on if is_admin() as routines from here are needed for wp-control.php
require_once('CS_config.php');
require_once('CS_utilities.php');

// 2016-01-28 EZ - Calling this here causes the Gmail SMTP plugin to fail -- I tested quite extensively and can't see a reason for this to be included here as opposed to just letting Wordpress include it in due time.
//               - I also grepped for each function defined in this routine to see if it's used anywhere in the ClickSold tree -- the functions are used in certain things but does not appear to cause any ill effects -- I checked the relevant sections.
//require_once(ABSPATH. 'wp-includes/pluggable.php');

/**
 * WP - Customizer - is the component in the wp-admin area that allows you to make changes to a live site that don't go live until you publish them.
 * This section breaks as we don't enqueue our widget scripts correctly (it uses diff hooks). I've tried to fix this 2015-03-19 EZ but ran out of time,
 * so for the time being if we are running the customizer call we just exit. The downside of this is that CS widgets are not available via the customizer
 * but are still present correctly on the widgets.php page.
 */
if(cs_is_customizer()) {
	
	/**
	 * 2015-09-29 EZ - found this on them internets: https://codex.wordpress.org/Theme_Customization_API -- appears to have documentation that
	 * could finally be used to make this section work with our ClickSold widgets.
	 */
	return;
}

//hook add_query_vars function to query_vars.
//query_vars: applied to the list of public WordPress query variables before the SQL query is formed.
//            Useful for removing extra permalink information the plugin has dealt with in some other manner.
add_filter('query_vars', 'cs_add_query_vars');
function cs_add_query_vars($aVars) {

	global $wpdb;
	global $cs_posts_table;
	$table_name = $wpdb->prefix . $cs_posts_table;

	//grab each parameter from the db and add it to list of query variables. Using GROUP BY
	//clause here since we want to eliminate the duplicate parameters
	$result = $wpdb->get_results("SELECT parameter FROM $table_name GROUP BY parameter" );
	foreach($result as $parameter){
		$aVars[] = $parameter->parameter;
	}

	return $aVars;
}

//hook to rewrite_rules_array. This filter is checked every time you save/re-save your permalink structure
add_filter('rewrite_rules_array', 'cs_add_rewrite_rules');
function cs_add_rewrite_rules($aRules) {
	global $wpdb;
	global $cs_posts_table;

	//get all posts we know are from ClickSold. Query wp_cs_posts and wp_posts.
	$cs_posts = $wpdb->get_results( "SELECT postid FROM " . $wpdb->prefix . $cs_posts_table . " GROUP BY postid" ); //gets unique post ids
	$wp_posts = $wpdb->get_results( "SELECT ID, post_title, post_name, post_parent FROM $wpdb->posts WHERE ID IN (" . cs_generate_list_from_wpdb_result( $cs_posts, 'postid', ', ' ) . ")" );

	// Add a rewrite rule for each CS post.
	foreach($wp_posts as $post){
		$parameters_array = array(); // array that will contain all the parameters associated with a postid
		$parameters = $wpdb->get_results("SELECT parameter FROM " . $wpdb->prefix . $cs_posts_table . " WHERE postid = $post->ID");
		$i = 0;
		foreach($parameters as $param){
			$parameters_array[$i]= $param->parameter; //store the parameter in an array
			$i = $i + 1;
		}

		//get all subpages that have this ClickSold page as its parent
		$sub_pages = $wpdb->get_results( "SELECT ID, post_name FROM $wpdb->posts WHERE post_parent = $post->ID AND post_type = 'page'" );

		//now we have list of parameters ($parameters_array)
		//and we have reference to the post_name ($post->post_name) -> create the rewrite rules
		$cs_rewrite = new CS_rewrite($post->post_name, $parameters_array, cs_get_page_name_with_parent_page_path( $post->ID ), $sub_pages, false);
		$aNewRules = $cs_rewrite->getRewriteRuleArray();
		$aRules = $aNewRules + $aRules;
	}

	//above lines actually generate the commented out code below, but, dynamically!
	/*$aNewRules = array('listings/?$' => 'index.php?pagename=listings',
               'listings/([^/]+)/?$' => 'index.php?pagename=listings&mlsnum=$matches[1]',
	       'neighbourhoods/?$' => 'index.php?pagename=neighbourhoods',
	       'neighbourhoods/([^/+]+)/?$' => 'index.php?pagename=neighbourhoods&neighbourhood=$matches[1]');

	$aRules = $aNewRules + $aRules;*/

	return $aRules;
}

/**
 * Init the session early (needed so the cs plugin server does not need to generate a new session for each request).
 *
 * If the cs_opt_use_cookies_instead_of_sessions option is set this will set a cookie as opposed to initializing the session.
 */
if(! function_exists('cs_init_session') ) {
	function cs_init_session() {

		if( !get_option( 'cs_opt_use_cookies_instead_of_sessions', 0 ) ) { // Use regular sessions.
			
			// Don't attempt to start the session if it will not work (thus preventing the warning from showing up).
			// 2015-07-06 EZ - had to add the headers_sent() check here cause some sites with warnings turned on send the output to the browser too soon and therefore generate a warning here. NOTE this won't work
			//                 100% correctly in these cases, it's just to prevent the error from showing up which gets blamed on us becasue our plugin appears in the error message.
			if( !session_id() && !headers_sent() ){
				session_start();
			}
		} else { // Use cookie based user tracking (for hosts that don't support php sessions).

			if(! isset($_COOKIE['cs_login'] ) ) {

				// Grab a new cookie value.
				$cs_login_cookie_val = "cs_login_" . time();

				// Set the cookie.
				setcookie( 'cs_login', $cs_login_cookie_val);

				// Store it in the COOKIE global so we don't have to worry about passing it along.
				$_COOKIE['cs_login'] = $cs_login_cookie_val;
			} else { // Else we already have a cookie, re-set it if it's too old.

				// Each cookie_value is in the format of cs_login_<timestamp>
				if( preg_match( '/cs_login_(\d+)/', $_COOKIE['cs_login'], $cookie_value_parts ) ) {

					// If the timestamp is more than 1 day behind now we remove the record.
					if( $cookie_value_parts[1] < ( $now - 24 * 60 * 60 ) ) {
						$cs_login_cookie_val = "cs_login_" . time();
						setcookie( 'cs_login', $cs_login_cookie_val);
						$_COOKIE['cs_login'] = $cs_login_cookie_val;
					}
				} else { // The cookie value does not match our format, re-set it.
					$cs_login_cookie_val = "cs_login_" . time();
					setcookie( 'cs_login', $cs_login_cookie_val);
					$_COOKIE['cs_login'] = $cs_login_cookie_val;
				}
			}
		}
	}
	add_action('init', 'cs_init_session', 1);
}

add_action("init", "check_product_update");

/**
 * Check for new update. If we have changes make request: which features are available.
 */
function check_product_update(){

	global $cs_change_products_request;
	global $wpdb;
	global $CS_SECTION_PARAM_CONSTANTS;
	global $CS_SECTION_ADMIN_PARAM_CONSTANT;
	global $cs_opt_brokerage;
	global $cs_opt_tier_name;
	global $cs_opt_plugin_key;
	global $cs_opt_plugin_num;
	global $cs_opt_acct_type;

	if ( get_option( $cs_change_products_request ) == "1" && !get_option( $cs_opt_plugin_key, "" ) == "" && !get_option( $cs_opt_plugin_num, "" ) == "" ) {

		// Make request to RPM server about allowed features
		// NOTE: This hits the front office although some of the files are in the back office dir structure, this is because it had to change as the plugin needs to do this before the user is logged in.
		$cs_request = new CS_request('pathway=566', $CS_SECTION_PARAM_CONSTANTS["listings_pname"]);
		$cs_response = new CS_response( $cs_request->request() );

		if( $cs_response->is_error() ) return;

		$vars = $cs_response->cs_set_vars(); // error_log( "vars: " . print_r( $vars, true ) );

		if(empty($vars)) {
			//Invalidate / Hide plugin pages
			$cs_pages = $wpdb->get_col("SELECT postid FROM " . $wpdb->prefix . "cs_posts");
			if(!empty($cs_pages)) {
				$wpdb->query('UPDATE ' . $wpdb->prefix . 'posts SET post_status = "private" WHERE ID IN(' . implode(", ", $cs_pages) . ')');
				$wpdb->query('UPDATE ' . $wpdb->prefix . 'cs_posts SET available = 0 WHERE 1;');
			}
			return;
		} else {

			// 2012-10-02 EZ - This has been replaced by a change in CS_ajax_request.php. If we are processing a call that could have changed the configuration
			// we set the $cs_change_products_request AFTER the call has completed. The next plugin request (and we don't care if it's back or front office) will
			// re-configure the plugin and clear the $cs_change_products_request flag.

			//// Compare Tier Name & Brokerage Flag with WordPress db values - if they match, exit without unsetting the cs_change_products_request flag.
			//// Note that this is to prevent the plugin from being given old configuration data when the site is hit during the upgrade process.
			//$brokerage = (bool) get_option("cs_opt_brokerage", 0);
			//if( $vars['tierName'] == get_option("cs_opt_tier_name") &&
			//  ($brokerage && $vars['brokerage'] == "true" ||
			//  !$brokerage && $vars['brokerage'] == "false") ) {
			//	return false;
			//}
		}

		$page_on_front = get_option( 'page_on_front' );
		$cs_posts_desired_statuses = get_option( "cs_posts_desired_statuses", array() ); // Note these are the desired statuses (if a feature is available a private here will override the publish that comes from the feature being available) the code treats missing entries as publish so this one gets a default empty array.

		// Toggling the brokerage needs to be done before the tier checks because when it saves / restores the state of the associates page which needs to happen before the feature availability calculations.
		$cs_config = new CS_config();
		$cs_config->cs_plugin_check_brokerage($vars["brokerage"]);

		// For each section (tier_feature ie: idx, associates), update the status / diaplay of the associated pages.
		foreach( $CS_SECTION_PARAM_CONSTANTS as $tier_feature ) {

			$feature_post_id = $wpdb->get_var('SELECT postid FROM ' . $wpdb->prefix . "cs_posts" . ' WHERE PREFIX = "' . $tier_feature . '"');

			if ( $feature_post_id > 0 && $vars[$tier_feature] != "" ) { // If this tier_feature has an associated post AND it's one of the tier_features reported by the cs server.

				$postStatus = ( $vars[$tier_feature] === "true" && ( !isset( $cs_posts_desired_statuses[$tier_feature] ) || $cs_posts_desired_statuses[$tier_feature] == "publish" ) )?"publish":"private";	// All posts associated with available features are set to publish, if not available set to private (this keeps them in or out of dynamically created menus) -- unless of course an available feature marked as do not show.
				$wpdb->update( $wpdb->posts, array( "post_status" => $postStatus ), array( "ID" => $feature_post_id ), array( "%s" ), array( "%d" ) );
				$wpdb->update( $wpdb->prefix . "cs_posts", array( "available" => ( $vars[$tier_feature] === "true" )?"1":"0" ), array( "postid" => $feature_post_id ), array( "%s" ), array( "%d" ) ); // The available field on the cs_posts table controls which sections of the cs back office are disabled.

				// If this is set as the front page but cannot be shown, set the front page as the WordPress default (latest posts)
				if($page_on_front == $feature_post_id && $vars[$tier_feature] !== "true") {
					update_option('page_on_front', 0);
					update_option('show_on_front', 'posts');
				}

				// If the post can't be shown we can't just set it as private, we also have to remove it from the custom menus.
				if( $vars[$tier_feature] !== "true" ) {

					// Find and remove all of the menu references to this feature's post.
					foreach( wp_get_associated_nav_menu_items( $feature_post_id, 'post_type', 'page' ) as $feature_menu_item_id ) {

						// Now we can use the wp_delete_post to delete the 'post' that is really the menu item.
						wp_delete_post( $feature_menu_item_id );
					}
				} else if( $vars[$tier_feature] === "true" && count( wp_get_associated_nav_menu_items( $feature_post_id ) ) == 0 ) { // If the feature of the page is available but has no associated menu items we have to add it to the custom menus.

					// Add the page to the menus.
					if( get_option("cs_allow_manage_menus", 1) ) {
						cs_add_post_to_custom_menus( $feature_post_id, 'page', $postStatus ); // We know that these are pages as we don't have feature dependant posts.
					}
				}
			}
		}

		if (($vars["isWaitingForUpdate"] != "") && ($vars["isWaitingForUpdate"] == "false")) {
			update_option($cs_change_products_request, "0");
		}

		if ( $vars["tierName"] != "" ) {
			update_option( $cs_opt_tier_name, $vars["tierName"] );
		}

		// Set the account type
		if ( $vars["csAccountType"] != "" ) {
			update_option( $cs_opt_acct_type, $vars['csAccountType'] );
		}
	}
}

$post_param = "";
$page_vars = array();  //Global Variable for holding page variables
$meta_config = array();

// Auto login processing
add_action('login_head', 'attempt_autologin_auth');
function attempt_autologin_auth(){
	if( !empty($_GET['name']) && !empty($_GET['pass']) ){
		$creds = array(
			'user_login' => $_GET['name'],
			'user_password' => $_GET['pass'],
			'remember' => false
		);
		$user = wp_signon($creds, false);
		if ( !is_wp_error($user) ) {
			wp_set_current_user($user->ID);
			wp_safe_redirect(admin_url());
		}
	}
}

/**
 * WP Login and Logout hooks. Used to report the fact that a user that can admin cs as defined by the 'cs_current_user_can_admin_cs' routine has logged in or out.
 *
 * The plugin server needs to know this as now we have some admin components that appear in the front office as opposed to being called from the back office. Before
 * we could just assume that if we are hitting the Admin controller then that we are autorized to do so.
 * 
 * 2014-10-31 NOTE: As of plugin 1.59 this is no longer required -- the plugin sends this info with each request now and the cs plugin server will respond accordingly.
 * This whole section is being left here as a reminder of this fact.
 */
//add_action('wp_login', 'cs_wp_login');
function cs_wp_login($user_login) {

	/**
	 * NOTE NOTE NOTE: This routine has everything disabled. During the login process none of this seems to work. Note this is not an issue. Each and any request from the
	 * wp_admin section to the admin controller will let it know that an authorized user is logged in. This works because plugins as of 1.27 are not capable of hitting the
	 * admin controller unless they are doing so from the back office and for plugins before 1.27 the controller will not set the logged in flag on hits to the back office.
	 */

//	// Only report if the user can cs_current_user_can_admin_cs
//
//	/** NOTE: We can't use cs_current_user_can_admin_cs() here as the current_user_can(xyz) does not work yet. */
//	/** NOTE: 2 supposidly we're supposed to get the user object as well but that does not seem to be happening, so all we have to play with is the user_login. **/
//	$user = get_userdatabylogin( $user_login );
//
//	/** WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING
//	    This here duplicates the functionality of cs_current_user_can_admin_cs if this is updated that has to be updated as well. **/
//	if( user_can( $user->ID, 'manage_options' ) ) {
//
//		// Needed to actually make the call.
//		require_once('cs_constants.php');
//
//		// Report that the user has logged in (This is done by performing any admin request, the response is never going to be used so it does not have to be a correct request)
//		$request = new CS_request( "", "wp_admin" ); // Note we use the bare 'wp_admin' instead of using the constant $CS_SECTION_ADMIN_PARAM_CONSTANT["wp_admin_pname"] as the constants have not been loaded yet!
//		$request->request();
//	}
}
//add_action('wp_logout', 'cs_wp_logout');
function cs_wp_logout() {

//	global $CS_SECTION_ADMIN_PARAM_CONSTANT;
//
//	// Only report if the user can cs_current_user_can_admin_cs
//	if( cs_current_user_can_admin_cs() ) {
//
//		// Report that the user has logged out.
//		$request = new CS_request( "pathway=407", $CS_SECTION_ADMIN_PARAM_CONSTANT["wp_admin_pname"] ); // 407 corresponds to SystemInfo.RPM_PLUGIN_ADMIN_REPORT_WP_ADMIN_LOGOUT
//		$request->request();
//	}
}

/**
 * This handler is currently here only so that we can refresh our rewrite rules when a CS page is saved as it's parent could have been assigned or cleared.
 */
add_action('save_post', 'cs_save_post');
function cs_save_post( $post_id ) {

	// http://codex.wordpress.org/Plugin_API/Action_Reference/save_post states that these could be revisions and instructs us to use wp_is_post_revision() - because this could just be a revision.
	if ( !wp_is_post_revision( $post_id ) ) {

		// NOTE: We have to refresh the permalinks even if we are updating a non CS post... this is because we could be updating the name or parent child relationship of a non cs post that is the parent of a cs post.
		global $wp_rewrite;
		$wp_rewrite->flush_rules(); // flush_rules calls the rewrite_rules_array hook for which we have a handler that adds the custom rewrite rules for our custom CS pages.
	}
}

/**
* Checks the server to see if this account's mobile site is disabled
*/
function cs_mobile_site_disabled() {
	$cs_request = new CS_request("pathway=611", "");
	$cs_response = new CS_response($cs_request->request());
	$val = $cs_response->get_body_contents();
	$val = trim($val);
	if($val == "false") return false;
	else return true;
}

// hijack the post action only if we are in the front of the website
if( !is_admin() ){

	global $wp_rewrite;

	// Canonical redirects need to be turned off or some of our custom urls will not work
	if(get_option("permalink_structure")) remove_filter('template_redirect', 'redirect_canonical');

	// Check if we need to blog listing updates
	add_action('pre_get_posts', 'cs_listing_auto_blog_update');

	// For mobile user agents, redirect to the mobile site, unless it's disabled and unless it's the CS_ajax_request.php file.
	if(isset($_SERVER['HTTP_USER_AGENT']) && !cs_str_ends_with( $_SERVER["PHP_SELF"], "CS_ajax_request.php" )) {
		if((stripos(basename($_SERVER['REQUEST_URI']), 'cs_mobile.php') === FALSE) && (!isset($_COOKIE["csFullSite"]) || $_COOKIE["csFullSite"] != "true") && (strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== FALSE || strpos($_SERVER['HTTP_USER_AGENT'], 'iPod') !== FALSE || strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== FALSE || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== FALSE || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== FALSE) ){
			
			// The cs_mobile_site_disabled() routine uses CS_request which eventually uses - wp_get_current_user() which comes from wp-inclues/pluggable.php. However pluggable has not yet been loaded at this stage. We include $
			// Note, we can't just include it at the top for all requests as that causes incompatabilities with other plugins. See the notes above where the pluggable include is disabled.
			require_once(ABSPATH. 'wp-includes/pluggable.php');
			
			if(cs_mobile_site_disabled() == false) {
				header('location:' . plugin_dir_url(__FILE__) . 'cs_mobile.php');
				die();
			}
		}
	}
	// For handling VIP confirmation links
	if(!empty($_GET["pathway"]) && !empty($_GET["email_addr"]) && !empty($_GET["confirmationCode"])){
		add_action('parse_query', 'cs_process_vip_confirmation', 5);

	// For handling VIP saved search links
	}else if(!empty($_GET["s_s"])){
		add_action('init', 'cs_saved_search_redirect', 5);

	// For handling VIP login via oauth
	}else if(isset($_GET["oauth_login"]) && isset($_GET["clientNumber"]) && isset($_GET["accessToken"]) && isset($_GET["op_id"])){
		add_action('init', 'cs_social_media_auto_login');
	
	// For handling VIP add account via facebook
	} else if(isset($_GET["oauth_signup"]) && isset($_GET["accessToken"]) && isset($_GET["op_id"])){
		add_action('parse_query', 'cs_social_media_login_add_account_tos', 5);
		
	// For handling email clicks
	}else if(isset($_GET["em"]) && isset($_GET["z"]) && isset($_GET["uid"]) && isset($_GET["out"])){
		add_action('init', 'cs_process_email_click', 5);

	// Normal page handling
	}else{
		// Adds inline javascript that changes masked domains to their original urls
		add_action('parse_query', 'cs_process_cs_section_posts', 5); 	// ClickSold section posts are processed in parse_query because they need to be able to set the title.
		add_action('wp', 'cs_process_cs_shortcode_posts'); 	// ClickSold shortcodes are processed when we are processing the post itself.
	}

	/**
	 * Checks options to see if we should run the listing auto blogger
	 */
	function cs_listing_auto_blog_update(){
		global $wpdb;
		global $cs_autoblog_new;
		global $cs_autoblog_sold;
		global $cs_autoblog_last_update;
		global $cs_autoblog_freq;

		//DEBUG - could possibly just leave it here and prevent these options from being added on plugin init
		if( get_option($cs_autoblog_new) === false ) { add_option($cs_autoblog_new, "0"); }
		if( get_option($cs_autoblog_sold) === false ) { add_option($cs_autoblog_sold, "0"); }

		if( get_option($cs_autoblog_new) == "1" || get_option($cs_autoblog_sold) == "1" ) {
			$last_update = get_option($cs_autoblog_last_update);
			if(!empty($last_update)){
				//Compare now with last update date
				$now = mktime(0, 0, 0);
				$last_update = intval($last_update);
				$freq = get_option($cs_autoblog_freq);
				$days = 0;

				// Get number of days since last update
				while($last_update < $now) {
					$last_update = strtotime(date('Y-m-d', $last_update) . " +1 day");
					$days++;
				}

				//Skip update if number of days is not past the frequency (days before next update)
				if( $days < $freq ) return;
			}
			//Run update
			$cs_utils = new CS_utilities();
			$cs_utils->listing_autoblog_get_listing_posts();
		}
	}

	function cs_saved_search_redirect(){
		global $wpdb;
		global $CS_SECTION_PARAM_CONSTANTS;
		global $wp_rewrite;

		// Get / construct MLS search URL
		$cs_posts = $wpdb->prefix . "cs_posts";
		$pageid = $wpdb->get_var('SELECT postid FROM ' . $cs_posts . ' WHERE prefix = "' . $CS_SECTION_PARAM_CONSTANTS['listings_pname']  . '"');

		if(is_null($pageid)) return;

		$vars = $_GET;
		unset($vars['s_s']);  // Safe to keep but let's remove it anyways
		//$link = get_page_uri($pageid);
		$link = get_permalink($pageid);

		if(empty($link)) return;

		if($wp_rewrite->using_permalinks()){
			$link .= "?" . http_build_query($vars);
		}else{
			$link .= "&" . http_build_query($vars);
		}

		//error_log("Listings Saved Search URI: " . $link);

		// Run redirect to site
		echo "<script type=\"text/javascript\">";
		echo "location.href=\"" . $link . "\";";
		echo "</script>";
	}

	function cs_social_media_auto_login(){
		global $wpdb;
		global $CS_SECTION_PARAM_CONSTANTS;
		global $wp_rewrite;
		global $cs_opt_acct_type;

		$vars = $_GET;
		$vars['pathway'] = '661';
		
		$cs_request = new CS_request(http_build_query($vars), "");
		$cs_response = new CS_response($cs_request->request());

		// On regular CS sites we just forward to the root of the site. However for the js api based ones this is not guaranteed to have any valid content on it.
		// So for the js api plugin we simply force the load of the listings/ page which is always there.
		if( get_option( $cs_opt_acct_type, 'unknown' ) == "10" || get_option( $cs_opt_acct_type, 'unknown' ) == "11" ) { // We are the js-api plugin type.

			// Run redirect to site
			echo '<script type="text/javascript">';
			echo 'location.href="' . home_url() . '/listings";'; // home_url uses is_ssl to determine http vs. https for us.
			echo '</script>';

		} else { // Regular clicksold.

			// Run redirect to site
			echo '<script type="text/javascript">';
			echo 'location.href="' . home_url() . '";'; // home_url uses is_ssl to determine http vs. https for us.
			echo '</script>';
		}
	}
	
	
	function cs_social_media_login_add_account_tos(){
		global $wpdb;
		global $CS_SECTION_PARAM_CONSTANTS;
		global $wp_rewrite;

		$vars = $_GET;
		$vars['pathway'] = '662';

		$cs_request = new CS_request(http_build_query($vars), "");
		$cs_response = new CS_response($cs_request->request());

		// This is needed if the home page of the site does not have any CS components -- in this case these will add the js includes required to display the tos.
		add_action("wp_head", array($cs_response, "cs_get_header_contents_linked_only"), 0);
		add_action("wp_head", array($cs_response, "cs_get_header_contents_inline_only"), 11); // Needs to be ran at a highier priority as it needs to go AFTER the enqueue stuff.
		
		$content_str = preg_replace( "/\r|\n/", "", $cs_response->get_body_contents());
		$content_str = str_replace("\"", "\\\"", $content_str);
		$content_str = str_replace("</script>", "</scr\" + \"ipt>", $content_str);
		
		// Display TOS
		echo '<script type="text/javascript">';
		echo '(function($){';
		echo '  $(document).ready(function(){';
		echo '    var csFBTOS = "' . $content_str . '";';
		echo '    $.clickSoldUtils("infoBoxCreate", { ';
		echo '      html : csFBTOS,';
		echo '      scrolling : true,';
		echo '      onComplete : function() {';
		echo '        $.clickSoldUtils("infoBoxResize");';
		echo '      }';
		echo '    });';
		echo '  });';
		echo '})(csJQ);';
		echo '</script>';
	}
	
	function cs_process_email_click(){
		global $wpdb;
		global $CS_SECTION_PARAM_CONSTANTS;
		global $wp_rewrite;

		$vars = $_GET;
		$vars['pathway'] = '640';

		$cs_request = new CS_request(http_build_query($vars), "");
		$cs_response = new CS_response($cs_request->request());

		// Run redirect to site
		echo '<script type="text/javascript">';
		echo 'location.href="' . home_url() . '";'; // home_url uses is_ssl to determine http vs. https for us.
		echo '</script>';
	}

	/**
	* Removes the edit link from the template itself if the current page is one generated from this plugin.
	*/
	add_filter('edit_post_link', 'remove_edit_post_link');
	function remove_edit_post_link( $link ){
		global $wpdb;
		global $wp_admin_bar;

		if(!in_the_loop()) wp_reset_query();

		$cs_posts = $wpdb->prefix . "cs_posts";
		$cs_page_ids = $wpdb->get_col('SELECT postid FROM ' . $cs_posts);

		if(is_page($cs_page_ids)){
			return '';
		}else{
			return $link;
		}
	}

	/**
	* Removes the "Edit Page" link from the admin bar if the current page is one generated from this plugin.
	*/
	add_action('wp_before_admin_bar_render', 'remove_admin_bar_edit');
	function remove_admin_bar_edit(){
		global $wpdb;
		global $wp_admin_bar;

		if(!in_the_loop()) wp_reset_query();

		$cs_posts = $wpdb->prefix . "cs_posts";
		$cs_page_ids = $wpdb->get_col('SELECT postid FROM ' . $cs_posts);

		if(is_page($cs_page_ids)) $wp_admin_bar->remove_menu('edit');
	}

	/**
	 * CS Shortcode delayed insert system. If enabled the cs shortcode handler just captures the output of the shortcodes
	 * but returns markers. This routine is then registered very late in the the_content filter so that no content formatting
	 * functions are ran on our shortcode output.
	 */
	if( get_option('cs_delayed_shortcodes', 0) ) {

		add_action('the_content', 'cs_delayed_shortcodes_insert_captured_values', 200); // At this priority we're all but sure that we'll run after the shortcodes filter.
		function cs_delayed_shortcodes_insert_captured_values ( $the_content ){
			global $cs_delayed_shortcodes_captured_values;

			// If the delayed shortcodes captured values is not set that means that this page does not have shortcodes.
			if( !isset( $cs_delayed_shortcodes_captured_values ) ) { return $the_content; }

			// Replace each captured shortcode with it's corresponding value.
			foreach($cs_delayed_shortcodes_captured_values as $shortcode_marker => $shortcode_value) {
				$the_content = str_replace($shortcode_marker, $shortcode_value, $the_content);
			}

			return $the_content;
		}
	}

	/**
	 * Surrounds the contents of the page with a div wrapper for use with our styles.
	 * Used in conjunction with add_filter - the_content
	 * @param unknown_type $content
	 */
	if( !function_exists( 'cs_styling_wrap' ) ) {
		function cs_styling_wrap ( $content ){
			return "<div id=\"cs-wrapper\">" . $content . "</div>";
		}
	}

	/**
	 * Processes VIP confirmation links
	 */
	function cs_process_vip_confirmation( $wp_query ){
		global $CS_SECTION_PARAM_CONSTANTS;

		remove_action('parse_query', 'cs_process_vip_confirmation', 5);
		$cs_request = new CS_request(http_build_query($_GET), $CS_SECTION_PARAM_CONSTANTS["listings_pname"]);
		$cs_response = new CS_response($cs_request->request());

		// Stop processing if connection was lost
		if( $cs_response->is_error() ) return;

		// make sure the_content hook calls our functions to load the response in the appropriate spot
		add_action("wp_head", array($cs_response, "cs_get_header_contents_linked_only"), 0);
		add_action("wp_head", array($cs_response, "cs_get_header_contents_inline_only"), 11); // Needs to be ran at a highier priority as it needs to go AFTER the enqueue stuff.
		add_action("wp_footer", array($cs_response, "cs_get_footer_contents"), 0);
	}

	/**
	 * Function that allows post queries on private pages to be added to the loop
	 */
	function cs_get_private_page($params) {
		global $wpdb;
		remove_filter('posts_fields_request', 'cs_get_private_page', 0);
		return str_replace(
            "$wpdb->posts.*", "$wpdb->posts.ID, $wpdb->posts.post_author, $wpdb->posts.post_date, " .
			"$wpdb->posts.post_date_gmt, $wpdb->posts.post_content, $wpdb->posts.post_title, $wpdb->posts.post_excerpt, " .
			"REPLACE( $wpdb->posts.post_status, 'private', 'publish' ) AS `post_status`, $wpdb->posts.comment_status, " .
			"$wpdb->posts.ping_status, $wpdb->posts.post_password, $wpdb->posts.post_name, $wpdb->posts.to_ping, " .
			"$wpdb->posts.pinged, $wpdb->posts.post_modified, $wpdb->posts.post_modified_gmt, $wpdb->posts.post_content_filtered, " .
			"$wpdb->posts.post_parent, $wpdb->posts.guid, $wpdb->posts.menu_order, $wpdb->posts.post_type, " .
            "$wpdb->posts.post_mime_type, $wpdb->posts.comment_count", $params
        );
	}

	/**
	 * process the request as an cs request if the post id matches
	 * one of the ClickSold Plugin sections.
	 */
	function cs_process_cs_section_posts( $wp_query ){

		/** 2015-02-03 EZ - Over the years we've had a bunch of obscure cases where the [cs_]get_queried_object_id calls below were not working or were confusing other plugins.
		 *                  I had always thought that it was because we were (or the other plugins were calling it too early). Turns out that we were attempting this process on
		 *                  non main queries which don't always have all of the information in wp_query. This is here to resolve these issues.
		 *                  NOTE: The code below to remove the action is no longer necessary because of this is_main_query check.
		 */
		if(! $wp_query->is_main_query() ) { return; }

		///** 2013-05-06 EZ - Note added this junk add action so that when we remove the cs_process_cs_section_posts hook the 5 level does not get empty. The Thesis theme
		// * framework freaks out if we remove this action from the '5' priority level therefore leaving that level blank. */
		//add_action("parse_query", "cs_null_function", 5);
		//remove_action('parse_query', 'cs_process_cs_section_posts', 5);

		global $wpdb;
		global $wp_rewrite;
		global $cs_response;
		global $cs_posts_table;
		global $cs_opt_plugin_key, $cs_opt_plugin_num;
		global $cs_opt_tier_name;
		global $cs_opt_acct_type;
		global $cs_opt_brokerage;
		global $CS_SECTION_PARAM_CONSTANTS;

		// Global vars needed for configuring meta tags
		global $post_param;
		global $page_vars;
		global $meta_config;

		/** Check for and process ClickSold Section pages (eg: listings/, communities/ or idx/). **/

		$table_name = $wpdb->prefix . $cs_posts_table;

		// We fetch the post id differently depending on if permalinks are enabled or not.
		if( $wp_rewrite->using_permalinks()) {

			// Note calling get_queried_object_id directly confuses some other plugins.
			$post_id = cs_get_queried_object_id($wp_query);

			//Check to see if this is one of our pages as the front page.
			//Note that we can't use is_front_page() as it is too early in the loop
			//to get the proper response.
			if(empty($post_id)) $post_id = $wp_query->query_vars["page_id"];

		} else $post_id = $wp_query->get( "page_id" ); // NOTE: calling $wp_query->get_queried_object_id() does NOT work here... likely too early in the processing.

		//error_log(print_r( $wp_query, true ));
		// print ( "<br>(" . $post_id . ")<br>" );

		if(!empty($post_id)){
			$result = $wpdb->get_row( "SELECT postid, defaultpage, prefix, parameter, header_title, header_desc, header_desc_char_limit FROM $table_name WHERE postid = $post_id", ARRAY_A );

			if($result != null){

				// The post matches one that cs added for the user
				// process the request using the cs plugin server.
				if($result['postid'] == $post_id){
					$cs_org_req = "";
					$post_param = $result['parameter'];
					$force_private_page_load = false;

					if(array_key_exists($result['parameter'], $wp_query->query_vars)) {
						$param = $wp_query->query_vars[$result['parameter']];
					} else {
						$param = "";
					}

					if(!empty($param)){
						$cs_org_req = $param;
						// If present, append GET query string to cs_org_req
						// Note: primarily used for featured listings view
						if(!empty($_GET)){
							$cs_org_req .= "?" . http_build_query($_GET);
						}

						// Set force page load flag to true if we're looking for listing details or community search results
						if($result['prefix'] == $CS_SECTION_PARAM_CONSTANTS['listings_pname'] ||
						   $result['prefix'] == $CS_SECTION_PARAM_CONSTANTS['community_pname'])
							$force_private_page_load = true;

					// If no parameters were returned from the database, give cs_org_req the value of the GET query string if available
					}else if(!empty($_GET)){
						$cs_org_req = http_build_query($_GET);

						// Set force page load flag to true if we're looking for listing details
						if($result['prefix'] == $CS_SECTION_PARAM_CONSTANTS['listings_pname'] &&
						   (stripos($cs_org_req, '&mlsnum=') !== false || stripos($cs_org_req, '&listnum=') !== false )) $force_private_page_load = true;

						// Set force page load flag to true if we're looking for listing details
						if($result['prefix'] == $CS_SECTION_PARAM_CONSTANTS['community_pname'] &&
						   (stripos($cs_org_req, '&city=') !== false && stripos($cs_org_req, '&neigh=') !== false )) $force_private_page_load = true;

					// If this page was set as a front page, we need to feed in the request manually
					}else if($post_id == get_option( "page_on_front" ) && !$wp_rewrite->using_permalinks()){
						$cs_org_req = "page_id=" . $post_id;
					}

					// K, here we skip calls to the plugin server if we're just requesting resource files.
					if( preg_match( '/\.png$|\.gif$/s', $cs_org_req ) ) { // If it's any of the known types.
						return;
					}

					$cs_request = new CS_request($cs_org_req, $result['prefix']);
					$cs_response = new CS_response($cs_request->request());

					if(!$cs_response->is_error()) {

						$page_vars = $cs_response->cs_set_vars();
						$meta_config = array('header_title' => $result['header_title'], 'header_desc' => $result['header_desc'], 'header_desc_char_limit' => $result['header_desc_char_limit']);

						// Configure the account type based on the account config value given
						/*
						if(!empty($page_vars)) {
							$cs_config = new CS_config();
							$cs_config->cs_plugin_check_brokerage($page_vars);
						}
						*/

						// Force the request to show a CS generated page if set to private
						if($force_private_page_load == true) add_filter('posts_fields_request', 'cs_get_private_page', 0);

						// make sure the_content hook calls our functions to load the response in the appropriate spot
						add_filter("wp_title", "cs_set_head_title", 0);
						add_action("wp_head", "cs_set_meta_desc", 1);
						add_action("wp_head", "cs_set_open_graph_meta_data", 1);
						add_action("wp_head", array($cs_response, "cs_get_header_contents_linked_only"), 0);
						add_action("wp_head", array($cs_response, "cs_get_header_contents_inline_only"), 11); // Needs to be ran at a highier priority as it needs to go AFTER the enqueue stuff.
						add_action("wp_footer", array($cs_response, "cs_get_footer_contents"), 0);

						// For CS page content we don't want it to get filtered by anything else. So we set the priority to a high number so our stuff gets ran LAST.
						// 2012-08-29 EZ - no longer required now that we've set our the_content filters to 101 and 102 --- remove_filter("the_content", "wpautop");  //This line prevents wordpress from replacing double line breaks with <br> tags i.e. messes up the pagination sections in listing results views
						add_filter("the_content", array($cs_response, "get_body_contents"), 101);
						add_filter("the_content", "cs_styling_wrap", 102); //This line wraps all content around a div so our styles can take precedence over the template styles
						
						// 2015-09-03 EZ - Our listing unavailable pages are getting picked up as Soft 404's by google -- today I updated upstream to send the correct code, in the case of 404
						// pages we need to tell wordpress to set the header correctly.
						if( $cs_response->cs_get_response_status_code() == 404 ) {
							
							/**
							 * NOTE: This whole section has some errors. (2015-09-03 EZ)
							 * 1 - we should echo all of the codes, I'm not doing that because all we need is the 404 and I don't have time to verify that it does not break anything.
							 * 2 - this does not handle multiple response type requests correctly -- but those are only really applicable to shortcode pages in practice.
							 * 3 - this is not supported for shortcodes.
							 */
							add_action('wp','cs_send_404');
							
							if( get_option( 'cs_opt_use_set_404_for_listing_unavailable' ) ) {
								$wp_query->set_404();
							}
						}
						
					} else {
						// Show connection timeout error message
						add_filter("the_content", array($cs_response, "get_body_contents"), 101);
					}
				}
			}
		}
	}

	/**
	 * Process the request as an ClickSold request if the content contains any cs_shortcodes.
	 */
	if( !function_exists( 'cs_process_cs_shortcode_posts' ) ) {
		function cs_process_cs_shortcode_posts( $wp ){
			$cs_shortcodes = new CS_shortcodes( "cs_listings" );
			$cs_shortcodes->cs_process_cs_shortcode_posts( $wp ); // Defer to the shortcodes class for this as the code is similar between our plugins.
		}
	} # End if function_exists

	/**
	 * Sets the meta title tag for ClickSold generated pages
	 */
	function cs_set_head_title($title){

		// 2013-07-05 EZ - Genesis does not do it's filter correctly on pages (eg: listings/) where we should not be changing the title. So this is moved below once we know that we are actually responsible for updating the title.
		//remove_filter("wp_title", "cs_set_head_title", 0);

		global $post;
		global $wp_query;
		global $CS_VARIABLE_LISTING_META_TITLE_VARS;
		global $CS_VARIABLE_COMMUNITY_META_TITLE_VARS;
		global $CS_VARIABLE_ASSOCIATE_META_TITLE_VARS;
		global $CS_GENERATED_PAGE_PARAM_CONSTANTS;
		global $CS_VARIABLE_LISTING_META_OG;

		global $page_vars;
		global $meta_config;
		global $post_param;

		$options = array();

		//Return the original title if any of the required config arrays are empty
		if(empty($page_vars) || empty($meta_config) || empty($post_param)) return $title;

		// Below the above line we know that we are reponsible for the title update.
		remove_filter("wp_title", "cs_set_head_title", 0); // 2013-07-05 EZ - I have no idea why this removes the Genesis filters but it appears to be doing just that.

		/* NOTE: Subject to change once we decide on keying pages for use with this *
		 * plugin                                                                   */
		if($post_param == $CS_GENERATED_PAGE_PARAM_CONSTANTS['listings']){
			$options = $CS_VARIABLE_LISTING_META_TITLE_VARS;
		}else if($post_param == $CS_GENERATED_PAGE_PARAM_CONSTANTS['community']){
			$options = $CS_VARIABLE_COMMUNITY_META_TITLE_VARS;
		}else if($post_param == $CS_GENERATED_PAGE_PARAM_CONSTANTS['associates']){
			$options = $CS_VARIABLE_ASSOCIATE_META_TITLE_VARS;
		}

		// This is the configured format.
		$cs_title = $meta_config['header_title'];

		// If the cs_title configured format is blank, we can just quit right here as there is nothing for us to do.
		if( $cs_title == '' ) return;

		//replace wild cards with content, if found
		foreach($options as $key => $value){
			if(strpos($cs_title, $value) !== false){
				$cs_title = str_replace($value, $page_vars[$key], $cs_title);
			}

			$offset = strlen($cs_title) - strlen($value);
			if(($offset >= strlen($cs_title) || $offset >= strlen($value)) && $offset < 1){
				if($cs_title == $value){ $cs_title = $page_vars[$key]; }
			}else if(substr_compare($cs_title, $value, strlen($cs_title) - strlen($value)) === false){
				//Do nothing, the check above should of fell through instead
			}else if(substr_compare($cs_title, $value, strlen($cs_title) - strlen($value)) == 0){
				$cs_title = substr_replace($cs_title, $page_vars[$key], $offset, strlen($value));
			}
		}

		return $cs_title . " ";
	}

	/**
	 * Sets the meta description tag for ClickSold generated pages
	 */
	function cs_set_meta_desc(){

		global $CS_VARIABLE_LISTING_META_DESC_VAR;
		global $CS_VARIABLE_ASSOCIATE_META_DESC_VAR;
		global $CS_VARIABLE_COMMUNITY_META_TITLE_VARS;  //Title vars are also available for description
		global $CS_GENERATED_PAGE_PARAM_CONSTANTS;
		global $CS_VARIABLE_LISTING_META_OG;

		global $post;
		global $wp_query;

		global $post_param;
		global $page_vars;
		global $meta_config;

		$options = array();

		if(empty($page_vars) || empty($meta_config) || empty($post_param)) return;

		$char_limit = (int) $meta_config['header_desc_char_limit'];

		if($char_limit <= 0){
			return;
		}else if($char_limit > 200){
			$char_limit = 200;
		}

		// This is the configured format.
		$content = $meta_config['header_desc'];

		// If the content configured format is blank, we can just quit right here as there is nothing for us to do.
		if( $content == '' ) { return; }

		/* NOTE: Subject to change once we decide on keying pages for use with this *
		 * plugin                                                                   */
		if($post_param == $CS_GENERATED_PAGE_PARAM_CONSTANTS['listings']){
			$options = $CS_VARIABLE_LISTING_META_DESC_VAR;
		}else if($post_param == $CS_GENERATED_PAGE_PARAM_CONSTANTS['community']){
			$options = $CS_VARIABLE_COMMUNITY_META_TITLE_VARS;
		}else if($post_param == $CS_GENERATED_PAGE_PARAM_CONSTANTS['associates']){
			$options = $CS_VARIABLE_ASSOCIATE_META_DESC_VAR;
		}

		//replace wild cards with content, if found
		foreach($options as $key => $value){
			$pv_val = "";
			if(array_key_exists($key, $page_vars)) $pv_val = $page_vars[$key];

			if(strpos($content, $value) !== false){
				$content = str_replace($value, $pv_val, $content);
			}

			$offset = strlen($content) - strlen($value);
		}

		// Cut it off to the desired maximum length.
		if(strlen($content) > $char_limit){
			$content = substr($content, 0, $char_limit - 3);
			$content .= "...";
		}

		echo "\n<meta name='description' content='$content' />";
	}

	/**
	 * Sets the Open Graph namespace on the HTML starting tag
	 */
	add_filter('language_attributes', 'cs_set_og_ns');
	function cs_set_og_ns( $output ) {
		return $output . ' xmlns:og="http://ogp.me/ns#"';
	}

	/**
	 * Function for setting some of the open graph meta tags - title and description are
	 * added by the above two functions.
	 */
	function cs_set_open_graph_meta_data(){

		global $CS_VARIABLE_LISTING_META_OG;
		global $CS_VARIABLE_LISTING_META_OG_ID;
		global $page_vars;

		if(empty($page_vars)) return;

		$og_props = $CS_VARIABLE_LISTING_META_OG;
		$og_ids = $CS_VARIABLE_LISTING_META_OG_ID;
		$og_meta_tags = "\n";

		foreach($og_props as $key => $value){
			// Skip title & desc as they've been set in cs_set_meta_title & cs_set_meta_desc
			if(array_key_exists($value, $page_vars)) {
				if($key == "_cs_listing_og_sitename") {
					$og_meta_tags .= '<meta id="' . $og_ids[$key] . '" property="' . $value . '" content="' . get_bloginfo('name') . '" />' . "\n";
				} else if($key == "_cs_listing_og_title") {
					$page_id = get_the_id();
					$title = get_the_title($page_id);
					$cs_title = cs_set_head_title($title);
					$og_meta_tags .= '<meta id="' . $og_ids[$key] . '" property="' . $value . '" content="' . $cs_title . '" />' . "\n";
				} else if($key == "_cs_listing_og_desc") {
					$og_meta_tags .= '<meta id="' . $og_ids[$key] . '" property="' . $value . '" content="' . $page_vars['_cs_listing_desc'] . '" />' . "\n";
				} else if(strpos($value, "alt_") === 0) {
					$og_meta_tags .= '<meta id="' . $og_ids[$key] . '" property="' . substr($value, 4) . '" content="' . $page_vars[$value] . '" />' . "\n";
				} else {
					$og_meta_tags .= '<meta id="' . $og_ids[$key] . '" property="' . $value . '" content="' . $page_vars[$value] . '" />' . "\n";
				}
			}
		}

		if(!empty($og_meta_tags)) echo $og_meta_tags;
	}

	// Canonical Header - Override for CS generated pages e.g. site.com/communities/city/neigh
	// Note: this section may need to be modified to accomodate other SEO-related plugins
	// We fetch the post id differently depending on if permalinks are enabled or not.
	if(get_template() === 'genesis') {

		// Genesis Framework - SEO
		add_action('pre_get_posts', 'debug_param_output');
		function debug_param_output($wp_query) {

			// Note calling get_queried_object_id directly confuses some plugins.
			$page_id = cs_get_queried_object_id($wp_query);

			if(empty($page_id)) $page_id = $wp_query->query_vars["page_id"];

			// 2012-12-10 EZ - I really don't think that this main query detection is working correctly it seems to always be 1, not a big deal as the corresponding page_id is blank for the other ones.

			$main_query = false;
			if(method_exists($wp_query, 'is_main_query')) {
				 $main_query = $wp_query->is_main_query();
			} else { // For WP < 3.3
				global $wp_the_query;
				if($wp_query === $wp_the_query) $main_query = true;
			}

			if($main_query && !empty($page_id)) {
				update_post_meta( (Int)$page_id, '_genesis_canonical_uri', home_url() . $_SERVER['REQUEST_URI'] );
			}
		}
	} else {

		// Normal (No other SEO Plugins used)
		add_action('template_redirect', 'cs_update_canonical_link');
		function cs_update_canonical_link() {
			remove_action('wp_head', 'rel_canonical');
			add_action('wp_head', 'cs_fix_canonical_link', 5);
		}

		function cs_fix_canonical_link() {
			echo '<link rel="canonical" href="' . home_url() . $_SERVER['REQUEST_URI'] . '" />';
		}
	}
}
/* Hooks for plugin activation/deactivation ****************************************************/
$cs_config = new CS_config();
register_activation_hook(__FILE__, array($cs_config, 'cs_activate'));
register_deactivation_hook(__FILE__, array($cs_config, 'cs_deactivate'));

/* Hooks for theme switching *******************************************************************/
add_action('switch_theme', 'cs_set_idx_to_full_width');		// Runs after theme is switched but before the next request.

/* Administration Section **********************************************************************/
/* Load the ClickSold widgets ******************************************************************/
function cs_register_cs_widgets() {
	global $wpdb;
	global $cs_posts_table;
	global $CS_SECTION_PARAM_CONSTANTS;
	global $pagenow;

	$load_widgets = true;

	if ( is_admin() ) {
		$cs_admin = new CS_admin();

		//Do server check to see if creds are valid - for widgets page only
		if( isset($pagenow) ) {
			$is_wpmu = cs_is_multsite();
			if( empty($is_wpmu) && 'widgets.php' == $pagenow ) {
				$cs_request = new CS_request("pathway=20", "wp_admin");
				$cs_response = new CS_response($cs_request->request());

				if(!$cs_response->is_error()) {
					$resp = $cs_response->get_body_contents();
					$resp = trim($resp);
					if( !empty($resp) ) $load_widgets = false;
				} else {
					// No connection was made so skip this section
					$load_widgets = false;
				}
			}
		}
	}

	if ( $load_widgets == true && get_option("cs_db_version", FALSE) != FALSE ) {  //Option check is to make sure the next query doesn't get executed on first setup
		if( !class_exists('Personal_Profile_Widget') && !class_exists('Brokerage_Info_Widget') &&
			!class_exists('Mobile_Site_Widget') && !class_exists('Buying_Info_Widget') &&
			!class_exists('Selling_Info_Widget') && !class_exists('Listing_QS_Widget') &&
			!class_exists('Feature_Listing_Widget') && !class_exists('Listing_Details_Page_Widget') ):
		include_once( plugin_dir_path(__FILE__) . 'widgets.php');
		endif;

		/* Load the widgets on widgets_init ************************************************************/
		// add_action('widgets_init', create_function('', 'register_widget("Personal_Profile_Widget");'));
		add_action('widgets_init', function() { register_widget("Personal_Profile_Widget"); });
		// add_action('widgets_init', create_function('', 'register_widget("Brokerage_Info_Widget");'));
		add_action('widgets_init', function() { register_widget("Brokerage_Info_Widget"); });
		// add_action('widgets_init', create_function('', 'register_widget("Mobile_Site_Widget");'));
		add_action('widgets_init', function() { register_widget("Mobile_Site_Widget"); });
		// add_action('widgets_init', create_function('', 'register_widget("Buying_Info_Widget");'));
		add_action('widgets_init', function() { register_widget("Buying_Info_Widget"); });
		// add_action('widgets_init', create_function('', 'register_widget("Selling_Info_Widget");'));
		add_action('widgets_init', function() { register_widget("Selling_Info_Widget"); });
		// add_action('widgets_init', create_function('', 'register_widget("Feature_Listing_Widget");'));
		add_action('widgets_init', function() { register_widget("Feature_Listing_Widget"); });
		// add_action('widgets_init', create_function('', 'register_widget("VIP_Widget");'));
		add_action('widgets_init', function() { register_widget("VIP_Widget"); });
		// add_action('widgets_init', create_function('', 'register_widget("Listing_Details_Page_Widget");'));
		add_action('widgets_init', function() { register_widget("Listing_Details_Page_Widget"); });

		/* Add these widgets if the IDX search page is available */
		if(!is_null($wpdb->get_var('SELECT postid FROM ' . $wpdb->prefix . $cs_posts_table . ' WHERE prefix = "' . $CS_SECTION_PARAM_CONSTANTS['idx_pname'] . '" AND available = 1'))){
			// add_action('widgets_init', create_function('', 'register_widget("IDX_Search_Widget");'));
			add_action('widgets_init', function() { register_widget("IDX_Search_Widget"); });
			// add_action('widgets_init', create_function('', 'register_widget("Listing_QS_Widget");'));
			add_action('widgets_init', function() { register_widget("Listing_QS_Widget"); });
			// add_action('widgets_init', create_function('', 'register_widget("IDX_QS_Widget");'));
			add_action('widgets_init', function() { register_widget("IDX_QS_Widget"); });
			// add_action('widgets_init', create_function('', 'register_widget("Community_Search_Widget");'));
			add_action('widgets_init', function() { register_widget("Community_Search_Widget"); });
		}
	}
}
add_action('widgets_init','cs_register_cs_widgets', 1); // Needs to be in widgets_init so functions.php can disable this.

/** Customize the footer on Genesis themes for Managed and Hosted packages */
if(cs_is_hosted() && cs_is_multsite()){
	remove_action( 'genesis_footer', 'genesis_do_footer' );
	add_action( 'genesis_footer', 'child_do_footer' );
	function child_do_footer() {
		echo '<div style="text-align:right"><a href="http://www.clicksold.com">Wordpress IDX</a> by <a href="http://www.clicksold.com"><img src="'.plugins_url('/images/cs-logo-footer.png', __FILE__).'" style="margin-left:4px;" title="Wordpress IDX" alt="Wordpress IDX"></a></div>';
	}
}

/** Redirect the user to the ClickSold "My Account" menu (with a welcome message) on the first login to the dashboard.
    If we're being hosted on the ClickSold webservers that is. **/
if( cs_is_hosted() && is_admin() ) {

	$first_login = get_option( $cs_opt_first_login, 1 ); // Set the default to TRUE as if the opt is not there then we know it's the first login.

	// If they have never logged into the back office.
	if( $first_login ) {
		// If we're not the cs plugin admin page make redirect to that page.
		if( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] != 'cs_plugin_admin' ) {
			add_action('admin_init', 'first_login_redirect');
			// NOTE: The cs_plugin_admin page will record that the page has been viewed.
		}
	}
}

/** Forwards the page to the plugin activation page on first activation **/
function first_login_redirect() {
	wp_redirect(admin_url()."admin.php?page=cs_plugin_admin");
	exit();
}

/** Add the fav icon for wp installs on ClickSold servers. **/
if( cs_is_hosted() && is_admin() ) {
	add_action( 'admin_head', 'set_cs_favicon_admin' );
}

function set_cs_favicon_admin() {
	echo '<link rel="Shortcut Icon" type="image/x-icon" href="' . plugins_url( "images/favicon.ico", __FILE__ ) . '" />';
}

/** Add live chat code to admin area (Only in the admin dashboard en even then only in the CS admin part) **/
if( is_admin() && cs_is_cs_admin_section() ) {
	add_action('admin_footer', 'cs_add_livechat_to_footer');
}
function cs_add_livechat_to_footer() {
	
	// First add a relatively tall div, so the clickdesk chat thing does not cover the "Update" button on some large pages.
	echo "<div style='height:100px;'>&nbsp;</div>";
	
	// The invitation widget.
	echo '<script type="text/javascript">';
	echo "(function(d, src, c) { var t=d.scripts[d.scripts.length - 1],s=d.createElement('script');s.id='la_x2s6df8d';s.async=true;s.src=src;s.onload=s.onreadystatechange=function(){var rs=this.readyState;if(rs&&(rs!='complete')&&(rs!='loaded')){return;}c(this);};t.parentElement.insertBefore(s,t.nextSibling);})(document,";
	echo "'//rpm-cs.ladesk.com/scripts/track.js',";
	echo "function(e){  });";
	echo "</script>";
	
	// This is the live chat - code (LA Desk).
	echo '<script type="text/javascript">';
	echo "(function(d, src, c) { var t=d.scripts[d.scripts.length - 1],s=d.createElement('script');s.id='la_x2s6df8d';s.async=true;s.src=src;s.onload=s.onreadystatechange=function(){var rs=this.readyState;if(rs&&(rs!='complete')&&(rs!='loaded')){return;}c(this);};t.parentElement.insertBefore(s,t.nextSibling);})(document,";
	echo "'//rpm-cs.ladesk.com/scripts/track.js',";
	echo "function(e){ LiveAgent.createButton('7fe53214', e); });";
	echo "</script>";
	
}

/** Add retrieve cs info link to login page. **/
if( cs_is_hosted() ) { // Only if it's hosed, if they have their own wp then they are on their own.
	add_action( 'login_footer', 'add_cs_info_retrieval_link_on_login_page' );
}
function add_cs_info_retrieval_link_on_login_page() {
	echo '<div style="margin: auto; width: 255px;">';
	echo '<br>';
	echo '  <a href="http://www.clicksold.com/wiki/index.php/Logging_in_to_Your_Website#Forgotten_Username" target="_blank">Forgot your ClickSold Username?</a>';
	echo '<br>';
	echo '</div>';
}

// Decide whether or not to show offers on login
add_action('wp_login', 'cs_offers_popup_init');
function cs_offers_popup_init(){
	$tier = get_option("cs_opt_tier_name", "Bronze");
	$adDisabled = get_option("cs_opt_disable_offers_popup", "0");
	if($tier == "Bronze" && $adDisabled == "0") update_option("cs_opt_show_offers_popup", "1");
}

/**
 * This filter adds the non wordpress pages for ClickSold to the Better Wordpress Google XML Sitemaps plugin's external pages section.
 * 
 * 2014-03-12 EZ - This is the initial simple implementation, the next extension of this should add all of the cities and communities as well.
 * NOTE: I checked this routine does not get called if the sitemap has been cached, so it's OK, performance wise to put a call to the community
 * section and get a list of the Cities and Neighbourhoods.
 */
add_filter('bwp_gxs_external_pages', 'cs_bwp_gxs_external_sitemap');
function cs_bwp_gxs_external_sitemap() {
	
	$external_pages = array();
	
	// Get all the post ids of the cs pages.
	global $wpdb;
	global $cs_posts_table;
	global $wp_rewrite;
	global $CS_GENERATED_PAGE_PARAM_CONSTANTS;
	
	$table_name = $wpdb->prefix . $cs_posts_table;
	$cs_page_post_ids = $wpdb->get_results( "SELECT postid FROM $table_name GROUP BY parameter" );
	
	// For each post id, grab the post and if applicable add it to the external pages list.
	foreach($cs_page_post_ids as $cs_page_post_id) {
		
		// Grab the post - Returns a WP_Post object on success.
		$post = get_post( $cs_page_post_id->postid );

		// If it's null then we could not load the post, which should not happen but still.
		if( $post == null ) { continue; }
		
		// If the post is not set to be published we skip it.
		if( $post->post_status != 'publish' ) { continue; }
		
		// Here we have to fake things out a bit, the cs posts are never updated, as far as wordpress is concerned anyways. The content on them is updated however by CS so, we set the last updated to a day ago.
		$yesterday = date( 'Y-m-d', ( time() - 1 * 24 * 60 * 60 ) ); // Current timestamp - 1 day's worth of seconds.
		
		// Generate location value based on whether or not permalinks are being used
		if( $wp_rewrite->using_permalinks()) {
			$location = home_url( $post->post_name );
		} else {
			$location = home_url() . '/?page_id=' . $post->ID;
		}
		
		// Now we can add the post to the sitemap.
		array_push ( $external_pages, array( 'location' => $location, 'lastmod' => $yesterday, 'priority' => '1.0' ) );
	}
	
	// Get the post id of the communities page
	$cs_comm_post_id = $wpdb->get_results( "SELECT postid FROM $table_name WHERE parameter = '" . $CS_GENERATED_PAGE_PARAM_CONSTANTS['community'] . "'" );
	
	if(!is_null($cs_comm_post_id) && count($cs_comm_post_id) == 1) {
		
		$post = get_post( $cs_comm_post_id['0']->postid );
		
		if( !is_null($post) && $post->post_status == 'publish') {
		
			// Get community data
			$cs_request = new CS_request( "pathway=527&sitemap=true", "" );
			$cs_response = new CS_response( $cs_request->request() );
			$comm_data = $cs_response->cs_get_json();
			
			if(!empty($comm_data)) {
				foreach($comm_data as $city => $neighs) {
					foreach($neighs as $neigh) {
						$yesterday = date( 'Y-m-d', ( time() - 1 * 24 * 60 * 60 ) );
						if( $wp_rewrite->using_permalinks()) {
							$location = home_url( $post->post_name . '/' . $city . '/' . $neigh . '/1/' );
						} else {
							$location = home_url() . '/?page_id=' . $post->ID . '&city=' . $city . '&neigh=' . $neigh . '&page=1';
						}
						array_push ( $external_pages, array( 'location' => $location, 'lastmod' => $yesterday, 'priority' => '1.0' ) );
					}
				}
			}
		
		}
	}
	
	return $external_pages;
}

/**
 * CloudFlare optionally uses rocketscript which breaks our stuff -- if we're in rocketscript compatability mode we change the way that OUR header / footer scripts are output.
 */
// Note, these two helper functions are defined because in php <= 5.2 we can't use anonymous functions (which is how this was implemented before).
function cs_no_rocketscript_handler_header() { cs_no_rocketscript_handler('header'); }
function cs_no_rocketscript_handler_footer() { cs_no_rocketscript_handler('footer'); }
if( get_option("cs_opt_cloudflare_script_compatability", 0) ) {
	add_action('wp_print_scripts', 'cs_no_rocketscript_handler_header', 1);
	add_action('wp_print_footer_scripts', 'cs_no_rocketscript_handler_footer', 1);
}

?>
