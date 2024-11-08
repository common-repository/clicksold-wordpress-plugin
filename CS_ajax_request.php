<?php
/*
* Class used to process response from an AJAX call to the ClickSold Server for a specific view
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

/** We start and then clean the output buffer while running the wp load in between to make our ajax requests not include
    any error text that the actual loading of wordpress may produce. This error text messes up json responses and image requests (captcha). */
ob_start();

/** wp-settings.php - uses a SHORTINIT constant... this is used by the wp file uploader, we can use it here too to speed up our ajax queries.
 *  that said, we have to be very careful as we're fiddling with the way that wp loads, so we have to know which version we're working with
 *  then we can use the correct (but modified) wp-settings.php to load whatever we want. */
$use_short_init = false;
require_once('../../../wp-includes/version.php');	// We need to load this manually so we can get the version out.
if( version_compare( $wp_version, '3.9', '>=' ) ) { // Currently only versions newer than this are supported.
	$use_short_init = true;
}

/** If however we are the clicksold_utils plugin we never do a short init... this is cause it includes cs_listings_plugin.php which is not present in the clicksold_utils plugin.
 *  Note: Have to do this old school here as none of our libs have been included yet. */
$doing_cs_utils_plugin = false;
if(($temp = strlen(__DIR__) - strlen("clicksold_utils")) >= 0 && strpos(__DIR__, "clicksold_utils", $temp) !== FALSE) { # Code taken from the cs_str_ends_with function.
	$use_short_init = false;
	$doing_cs_utils_plugin = true;
}

// Let wp-settings.php know that we want to do a short init and not a full one.
// We do this here cause we can't redefine the constant and the two if statements above are clearer if they are not in the same if.
if( $use_short_init ) {
	define('SHORTINIT', true);
}

// 2013-03-27 - this was deemed necessary for wp-control.php at some point in time... so I'm including it here as it appears correct.
// 2013-07-26 - this is normally done in wp-admin/admin-ajax.php and may make the loading wp slightly faster.
define('DOING_AJAX', true);
// 2014-02-24 - EZ - Added this as it was described as a best practice on: http://codex.wordpress.org/Integrating_WordPress_with_Your_Website
define('WP_USE_THEMES', false);

// Note if we are doing a SHORTINIT this process stops pretty high up in the wp-settings.php file.
require_once('../../../wp-load.php');

// If we instructed wp to do a short init, we need to *finish* the initalization. Note, the custom version of wp-settings.php is simply the regular version with all of the stuff up to the SHORTINIT exit point stripped out as well as all of the stuff that is useless for our ajax requests.
if( $use_short_init ) {
	
	// Select the correct include based on the wp version being used.
	if( version_compare( $wp_version, '5.1', '>=' ) ) {
		require_once('wp-settings-cs_short_init-5.1.php');
	} else if( version_compare( $wp_version, '4.7', '>=' ) ) {
		require_once('wp-settings-cs_short_init-4.7.php');
	} else if( version_compare( $wp_version, '4.6', '>=' ) ) {
		require_once('wp-settings-cs_short_init-4.6.php');
	} else if( version_compare( $wp_version, '4.4', '>=' ) ) {
		require_once('wp-settings-cs_short_init-4.4.php');
	} else if( version_compare( $wp_version, '4.0', '>=' ) ) {
		require_once('wp-settings-cs_short_init-4.0.php');
	} else if( version_compare( $wp_version, '3.9', '>=' ) ) {
		require_once('wp-settings-cs_short_init-3.9.2.php');
	}
}

// The utilities plugin does not need admin.php and it causes issues.
if( !$doing_cs_utils_plugin ) {
	require_once('../../../wp-admin/includes/admin.php'); // Will setup wp correctly if the user is logged in so we can use for example the is_admin() function and expect to get a sane response.
}

// Sometimes plugins loaded by wordpress call ob_start() as well, this get's stacked so cleaning the buffer once is sometimes not enough. eg: NextGEN Gallery does this. We flush the buffer until it's no longer enabled.
$ob_clear_counter = 0;
while( ob_get_length() !== FALSE ) {

	// 2014-02-24 EZ - Some hosts have php configured weird or wrong which causes the above call to ob_get_lenght to not work, we must therefore have a hard limit for this as it will crash this page load on these hosts (as it gets into an infinite loop).
	if($ob_clear_counter > 10) { break; }

	// 2013-12-12 EZ - We used to do this 5 times blindly, but today I noticed that with WP_DEBUG this issues warnings. So now we just flush the buffer until we have no more buffers.
	ob_end_clean();
	$ob_clear_counter++;
}

// Here we start buffering the ajax output once more. This script needs to be able to set the headers so we can't allow ANY output till that's done.
ob_start();

require_once('CS_request.php');
require_once('CS_response.php');
require_once('cs_constants.php');
require_once('cs_functions.php');

// If we're doing a short init - the cs plugin's cs_init_session is never defined, never hooked up and never ran -- we do this here so we can support sessions in ajax calls (think VIP login).
if( $use_short_init ) {
	require_once('cs_listings_plugin.php');
	cs_init_session();
}

class CS_ajax_request{
	protected $request_vars;
	protected $content_type;
	protected $response_status_code;
	
	public $captcha;
	
	function __construct(){
		$this->request_vars = $_SERVER['QUERY_STRING'];
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			/**
			*  WARNING!!!
			*  If your post request has duplicate parameters and they don't have [] 
			*  after the parameter name then the duplicates will be parsed out.
			*/
			
			// Have to manually sanitize as array_map doesn't recognize post names with square brackets appended to them
			array_walk_recursive($_POST, array($this, 'sanitize_escaped_quotes'));
			
			$this->request_vars .= "&" . http_build_query($_POST);
		}
	}
	
	private function sanitize_escaped_quotes(&$item, $key){
		$item = stripslashes($item);
	}	
	
	/**
	 * Constructs/sends request to ClickSold server, outputs response
	 */
	public function get_response(){	
		global $CS_SECTION_CAPTCHA_IMG_PARAM_CONSTANT;
		global $CS_SECTION_ADMIN_PARAM_CONSTANT;
		global $CS_SECTION_VIP_PARAM_CONSTANT;
		global $cs_change_products_request;
		
		$this->captcha = $this->is_captcha_request();
		
		if( $this->captcha == true ) { // A captcha request is setup differently.
			//Remove the string "captcha&" from the query string
			$this->request_vars = substr($this->request_vars , 8);
			$cs_request = new CS_request( $this->request_vars, $CS_SECTION_CAPTCHA_IMG_PARAM_CONSTANT );
			$cs_response = new CS_response( $cs_request->request( 'GET' ), $CS_SECTION_CAPTCHA_IMG_PARAM_CONSTANT );
		} else if( $this->is_admin_request() == true ) {	
			// *** SPECIAL CASE ***
			//If this is a listing details request, we need to have the request send in all the sections to construct the url
			// Also if the caller has specifically requested that that all section names be sent (used to send debug info)
			if(stristr($this->request_vars, "loadListing=true") != false || stristr($this->request_vars, "loadFavoriteListings=true") != false || stristr($this->request_vars, "request_all_section_names=true") != false){
				$cs_request = new CS_request( $this->request_vars, $CS_SECTION_ADMIN_PARAM_CONSTANT["wp_admin_pname"], true);
			}else{
				$cs_request = new CS_request( $this->request_vars, $CS_SECTION_ADMIN_PARAM_CONSTANT["wp_admin_pname"]);
			}
			
			$cs_response = new CS_response($cs_request->request());
		} else if( $this->is_vip_request() ) {
			$cs_request = new CS_request( $this->request_vars, $CS_SECTION_VIP_PARAM_CONSTANT["wp_vip_pname"]);
			$cs_response = new CS_response($cs_request->request());
		} else if( $this->is_utils_request() ) {
			$cs_request = new CS_request( $this->request_vars, "wp_utils");
			$cs_response = new CS_response($cs_request->request());
		} else {
			$cs_request = new CS_request( $this->request_vars, null);
			$cs_response = new CS_response($cs_request->request());
		}

		// If timeout error, show nothing instead of the timeout error message (2013-03-27 EZ Not sure why we're hiding the timeout message but the original code hid ALL error messages which is not what we wanted).
		if($cs_response->is_error() && $cs_response->get_body_contents() == $cs_request->req_timeout_err_msg) return "";
		
		// If this request was one that could have changed the configuration ask the plugin to check for new settings.
		if( $this->is_products_change_request() || $this->is_plugin_activation_request() ) {
			update_option($cs_change_products_request, "1");
		}
	
		// Save the meta data that we care about.
		$this->content_type = $cs_response->cs_get_response_content_type();
		$this->response_status_code = $cs_response->cs_get_response_status_code();
		
		return $cs_response->get_body_contents();
	}

	/**
	 * Returns true if this request is for a captcha resource (img file).
	 */
	public function is_captcha_request() {
		if(strpos($this->request_vars, 'captcha&t=') !== false) return true;
		else return false;
	}
	
	public function get_content_type(){
		return $this->content_type;
	}
	
	public function get_status_code(){
		if( !isset($this->response_status_code) || $this->response_status_code == "" ) { return 200; }
		return $this->response_status_code;
	}

	/**
	 * Returns true if this is a request from the admin panel (ClickSold)
	 */
	private function is_admin_request() {
		if(strpos($this->request_vars, 'wp_admin_pname') !== false) return true;
		else return false;
	}
	
	private function is_vip_request() {
		if(strpos($this->request_vars, 'wp_vip_pname') !== false) return true;
		else return false;
	}
	
	private function is_products_change_request(){
		return (strpos($this->request_vars, 'wp_products_change') !== false);
	}

	private function is_utils_request() {
		if(strpos($this->request_vars, 'wp_utils_pname') !== false) return true;
		else return false;
	}
	
	private function is_plugin_activation_request(){
		return (strpos($this->request_vars, 'wp_plugin_activate') !== false);
	}

}

	$ajax_request = new CS_ajax_request;
	$response_body = $ajax_request->get_response();
	$response_body = trim($response_body);
	
	if( $ajax_request->captcha == true ) { // Make sure that it reports itself as an image if it's an image request.
		header('Content-Type: image/jpeg');
		
		if( function_exists( 'imagecreatefromstring' ) ) { // If we have the gd module installed (provides the imagecreatefromstring function) we use that and do things properly.
			$img = imagecreatefromstring($response_body);
			imagejpeg($img, null, 100); // Also outputs the image to the browser.
			imagedestroy($img);
		} else { // Otherwise just output the image content and hope that the browser is able to make sense of it.
			echo $response_body;
		}
	} else {
		
		// Set the content type that the plugin server sent us.
		header('Content-Type: ' . $ajax_request->get_content_type());
		
		// Grab the status of the response that the plugin server sent us.
		// NOTE: php < 5.4 does not have this function, it is optionally implemented in cs_functions.php.
		http_response_code( $ajax_request->get_status_code() );
		
		echo $response_body;
	}
	
	// Finally now that we've had a chance to set the headers, we can flush the buffer.
	ob_end_flush();
?>
