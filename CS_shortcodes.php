<?php
/*
* This class defines the handler functions of all of shortcode handling functions
* defined by the ClickSold plugin. (and registered in this file also).
*
* It also defines a list of ClickSold created shortcodes, these are needed so that we know
* if one of our shortcodes is on a page. Once we know which ClickSold shortcodes are on a page
* we can compile and send an cs_request for all of them at the same time. Therefore
* most of the ClickSold shortcodes are simply handled by grabbing the correct section of
* the resulting cs_response.
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

class CS_shortcodes {

	/**------------------------------------- Instance Var Defs / Contructors ------------------------------------------**/
	private $registered_shortcodes = array(); // List of registered ClickSold shortcodes keyed on shortcode name, just so we know which ones are ours really.

	/**
	 * Shortcode meta data keyed on shortcode name, contains an array of default param=>value and parameter mask list (param => '').
	 * eg: cs_featured_listings
	 *     - default params = 'pathway' => '1'
	 *     - parameter mask = 'pathway' => ''. NOTE: we put the params as keys so we can quickly check if a given string is contained in the mask.
	 *     Meaning that any cs_featured_listings shortcode will automatically get the pathway=1 param which CAN'T
	 *     be overriden using params in a shortcode. Therefore [cs_featured_listings pahtway=xzy] === [cs_featured_listings]
	 */
	private $shortcode_meta_data = array();

	private $plugin_type = 'cs_listings';

	/**
	 * Build the CS_shortcodes object... This also registers all known ClickSold shortcodes with this instance
	 * as well as with wp itself (if not already registered).
	 */
	public function __construct( $plugin_type ){

		# The default plugin type is 'cs_listings' (set or re-set it if it's not specified / incorrect).
		if( !isset( $plugin_type ) || ( $plugin_type != "cs_listings" && $plugin_type != "cs_admin" ) ) {
			$this->plugin_type = "cs_listings";
		} else {
			$this->plugin_type = $plugin_type;
		}

		/** register_cs_shortcode checks to see if the short code is alreay registered with wp and ourselves. */
		if( $this->plugin_type == "cs_listings" ) { // shortcodes for the listings plugin.
			$this->register_cs_shortcode( 'cs_listing_details',		'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_featured_listings',	'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_community_list',		'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_community_results',	'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_idx_search',			'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_advanced_search',		'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_associate_list',		'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_associate_profile',	'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_contact_page',		'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_home_eval_form',		'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_mortgage_calc',		'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_mortgage_calc_link',	'cs_response_shortcode_handler' );
			
		} elseif ( $this->plugin_type == "cs_admin" ) { // shortcodes for the admin plugin
			$this->register_cs_shortcode( 'cs_admin_cs_signup_form',					'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_admin_cs_account_info_retrieval_form',	'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_admin_cs_affiliate_signup_form',			'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_admin_cs_mls_integration',				'cs_response_shortcode_handler' );
			$this->register_cs_shortcode( 'cs_admin_cs_generic',						'cs_response_shortcode_handler' );
		}

		/*
		 * NOTE: All ClickSold shortcodes MUST use the cs_response_shortcode_handler or one that handles the
		 * response apropriately... That is advances the response to the next step. This is because all
		 * ClickSold shortcodes create a request section, they must then in the correct order take their
		 * responses or the two lists (requests and responses) will get out of synch.
		 */

		/*
		 * WARNING: WARNING: Any new short code or changes to this must also be reflected in the plugin's
		 * /js/cs_shortcodes_tinymce_handler.js file. So that the content editor has the correct quick insert
		 * code.
		 */

		/** register the meta data for our shortcodes. **/
		if( $this->plugin_type == "cs_listings" ) { // shortcodes for the listings plugin.
			$this->shortcode_meta_data['cs_listing_details'] = array(
										array( 'pathway' => '6' ),	// Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);

			$this->shortcode_meta_data['cs_featured_listings'] = array(
										array( 'pathway' => '1', 'fullAjaxMode' => '1' ),	// Default parameters.
										array( 'pathway' => '' ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);

			$this->shortcode_meta_data['cs_community_list'] = array(
										array( 'pathway' => '527' ),	// Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);

			$this->shortcode_meta_data['cs_community_results'] = array(
										array( 'pathway' => '528', 'fullAjaxMode' => '1' ),	// Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);

			$this->shortcode_meta_data['cs_idx_search'] = array(
										array( 'pathway' => '5' ),	// Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);

			$this->shortcode_meta_data['cs_advanced_search'] = array(
										array( 'pathway' => '714' ), // Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);

			$this->shortcode_meta_data['cs_associate_list'] = array(
										array( 'pathway' => '408' ), // Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);

			$this->shortcode_meta_data['cs_associate_profile'] = array(
										array( 'pathway' => '409' ), // Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);

			$this->shortcode_meta_data['cs_contact_page'] = array(
										array( 'pathway' => '21' ), // Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);
									
			$this->shortcode_meta_data['cs_home_eval_form'] = array(
										array( 'pathway' => '10' ), // Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);
									
			$this->shortcode_meta_data['cs_mortgage_calc'] = array(
										array( 'pathway' => '537' ), // Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);
									
			$this->shortcode_meta_data['cs_mortgage_calc_link'] = array(
										array( 'pathway' => '725' ), // Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);
									
		} elseif ( $this->plugin_type == "cs_admin" ) { // shortcodes for the admin plugin

			$this->shortcode_meta_data['cs_admin_cs_signup_form'] = array(
										array( 'pathway' => '563' ),	// Default parameters.
										array( 'pathway' => '' ),	// Params that can't be overriden.
										true						// Introspect on the query string
									);

			$this->shortcode_meta_data['cs_admin_cs_account_info_retrieval_form'] = array(
										array( 'pathway' => '568' ),	// Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);
									
			$this->shortcode_meta_data['cs_admin_cs_affiliate_signup_form'] = array(
										array( 'pathway' => '599' ),	// Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);

			$this->shortcode_meta_data['cs_admin_cs_mls_integration'] = array(
										array( 'pathway' => '615' ),	// Default parameters.
										array( 'pathway' => ''  ),	// Params that can't be overriden.
										false						// Introspect on the query string
									);
									
			$this->shortcode_meta_data['cs_admin_cs_generic'] = array(
										array(),			// Default parameters.
										array(),			// Params that can't be overriden.
										false				// Introspect on the query string
									);

		}
	}


	/**------------------------------------------- Short Code Handlers ------------------------------------------------**/

	/**
	 * Very simple handler used by most ClickSold short codes that get their content from the plugin server.
	 * basically grabs the value from the current section of the response and increments the section
	 * counter so the next call gets the next value and so forth.
	 * 
	 * $atts - array of all of the shortcodes's attributes.
	 * $content - the content of the shortcode if the shortcode is being used in the enclosing form.
	 * $tag - the shortcode name.
	 */
	public static function cs_response_shortcode_handler($atts, $content, $tag) {
		global $cs_response;
		global $cs_delayed_shortcodes_captured_values; // Will capture our shortcode output if the cs_delayed_shortcodes option is set.
		
		// If the $cs_response is null and the sc does not have the 'do_sc_direct' attribute this means that the shortcode is being called
		// directly by a do_shortcode function but the caller did not specify the do_sc_direct parameter as required.
		if( !isset( $cs_response ) && !isset( $atts['do_sc_direct'] ) ) {
//			return "<br>Error: Likely calling cs shortcode via do_shortcode() but you need to specify do_sc_direct in this case.<br>";
			return "<br>Error: Likely calling cs shortcode via do_shortcode() this is not currently supported.<br>";
		}
		
		// If the shortcode is marked as being called directly via the do_shortcode function we need to create and issue an independent request.
		if( isset( $atts['do_sc_direct'] ) ) {
			
			/***
			 * DISABLED! DISABLED! DISABLED! DISABLED! DISABLED! DISABLED! DISABLED! DISABLED! DISABLED!
			 * Doing this in this way has two problems. First off by the time we get here it is too late to 
			 * include the css / js. Also because these are independent cs plugin server requests the
			 * modlet id's are non unique. To support do_shortcode() function calls directly we would have
			 * to invent some way of registering the calls (in order) and then issuing the requests directly.
			 */
			return "ERROR: Calling cs shortcodes via do_shortcode directly is not currently supported.";
			
			$cs_shortcodes = new CS_shortcodes("cs_listings");
			
			$local_cs_request = new CS_request("", "");
			$local_cs_request->del_req_sec(); // Deletes the current request section, the constructor creates the first one which we need to get rid of as we'll be adding them in the loop below shortly.

			// Add a new section to the request for this shortcode.
			$cs_org_req = $cs_shortcodes->cs_create_shortcode_request( $tag, $atts, false );
			$local_cs_request->add_req_section( $cs_org_req );

			$local_cs_response = new CS_response($local_cs_request->request());
			if($local_cs_response->is_error()) return "";
			
			add_action("wp_head", array($local_cs_response, "cs_get_header_contents_linked_only"), 0);
			add_action("wp_head", array($local_cs_response, "cs_get_header_contents_inline_only"), 11); // Needs to be ran at a highier priority as it needs to go AFTER the encueue stuff.
			add_action("wp_footer", array($local_cs_response, "cs_get_footer_contents"), 0);
			add_filter("the_content", "cs_styling_wrap", 2); //This line wraps all content around a div so our styles can take precedence over the template styles
			
			return $local_cs_response->get_body_contents();
		}
		
		// The result is the current response sections value.
		$return_value = $cs_response->get_body_contents();
		$return_value_section_num = $cs_response->get_response_section_num();
		
		// If the return value, contains the special marker where we wish to place the content of the shortcode then it's placed in here.
		$return_value = str_replace('<!-- ~~~~ CS ShortCode Content Placed Here ~~~~ -->', $content, $return_value);
		
		// Advance to the next response section.
		$cs_response->next_response_section();

		// If we are NOT delaying the insertion of the cs shortcode output just return it as per the normal shortcode processing.
		if( !get_option('cs_delayed_shortcodes', 0) ) {
			return $return_value;
		} else { // If we're delaying the insertion of cs shortcode output we capture the shortcode output and replace the shortcode with a marker. The marker will be replaced by the captured output in a very late running filter on "the_content", this allows us to get around formatting filters that screw up our inline js.
			
			// Generate the marker.
			$marker = "~~cs-sc-delay-" . time() . "~~" . $return_value_section_num . "~~";
			
			// Save the output under the marker.
			$cs_delayed_shortcodes_captured_values[$marker] = $return_value;
			
			// Return the marker instead of the actual shortcode output.
			return $marker;
		}
		
	}

	/**------------------------------------------- TinyMCE Integration ------------------------------------------------**/

	/**
	 * This routine starts off the process required to add the buttons necessary to
	 * support quick-adding of cs shortcodes.
	 *
	 * Adds the filters required to register the cs shortcodes plugin (js) as well as registering the
	 * actual buttons themselves.
	 */
	function cs_add_tinymce_buttons() {

		// If the user can edit posts we modify the buttons on the TinyMCE editor.
		if ( current_user_can('edit_posts') &&  current_user_can('edit_pages') ) {  
			add_filter('mce_external_plugins', array( $this, 'cs_register_mce_buttons_handler_plugin' ) );
			add_filter('mce_buttons', array( $this, 'cs_register_mce_buttons' ) );
		}
	}

	/**
	 * Registeres the js TinyMCE plugin that handles our buttons.
	 */
	function cs_register_mce_buttons_handler_plugin($plugin_array) {
		
		// Figure out the version of TinyMCE that we're using.
		global $tinymce_version;

		// The version number above is in a <vers#>-<date> format -- we just need the version.
		$tinymce_version_components = explode( "-", $tinymce_version);

		if( $tinymce_version_components[0] > 359 ) { // vers 359 was shipped with wp 3.8.3 (latest before they moved to the new TinyMCE version).
		
			// Use the new version.
			$plugin_array['cs_shortcodes'] = plugins_url( '/js/cs_shortcodes_tinymce_handler_since_wp39.js', __FILE__ );
		} else {
			
			// Use the old version.
			$plugin_array['cs_shortcodes'] = plugins_url( '/js/cs_shortcodes_tinymce_handler.js', __FILE__ );
		}
		
		return $plugin_array;
	}  

	/**
	 * Register the actual buttons with TinyMCE.
	 */
	function cs_register_mce_buttons($buttons) {
		array_push($buttons, "cs_shortcodes");
		return $buttons;
	}

	/**---------------------------------------- Helper / Access Functions ---------------------------------------------**/

	/**
	 * Registers the given shortcode with the given handler with wp
	 * as well as in the list of ClickSold shortcodes contained in this class.
	 * 
	 * NOTE: the handler function MUST be a function contained in the
	 * CS_shortcodes class.
	 */
	private function register_cs_shortcode( $shortcode_name, $handler_func ) {

		/** Register the shortcode with wordpress **/

		// Note: the array for the handler, this specifies the containing class of the function (in this case CS_shortcodes).
		add_shortcode( $shortcode_name , array( 'CS_shortcodes', $handler_func ) );

		/** Also register the shortcode with the current class. **/
		$this->registered_shortcodes[ $shortcode_name ] = 1;
	}

	/**
	 * Returns a regex that will match all ClickSold shortcodes. This is exactly the same
	 * as the regular get_shortcode_regex() function but only matches shortcodes
	 * registered in the current instance of this class. Aka just matches ClickSold shortcodes.
	 *
	 * NOTE: This implementation is just the regular get_shortcode_regex() xept grabs our
	 *       registered shortcode names instead of all of them.
	 */
	public function get_shortcode_regex() {
		$tagnames = array_keys($this->registered_shortcodes);
		$tagregexp = join( '|', array_map('preg_quote', $tagnames) );
		return '(.?)\[('.$tagregexp.')\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)';
	}

	/**
	 * Scans the given input and returns true if it finds any ClickSold shortcodes
	 * aka shortcodes that are defined in this class.
	 */
	public function contains_cs_shortcodes( $input ) {

		// Get RegEx that matches just ClickSold shortcodes.
		$shortcode_pattern = $this->get_shortcode_regex();

		// Process the regex, capture the matches and the count of matches.
		$match_count = preg_match_all('/'.$shortcode_pattern.'/s', $input, $matches);

		// If there were no matches it's easy, no CS shortcodes here.
		if($match_count == 0) { return false; }

		// So there are matches, but this is a bit more difficult, some or all of them could be escaped using double square brackets ie: [[shortcode_name]]
		$contains_cs_shortcodes = false;

		for( $i = 0; $i < count( $matches[2] ); $i++) { // We're trying to find at least one that's not escaped.
			if( $matches[ 1 ][ $i ] == "[" && $matches[ 6 ][ $i ] == "]" ) {
				// Escaped shortcode, our default is already false so we do nothing.
			} else {
				// Found a real shortcode.
				$contains_cs_shortcodes = true;
			}
		}

		return $contains_cs_shortcodes;
	}

	/**
	 * Fetch an array with all the ClickSold short codes extracted and parsed.
	 * format is 
	 * [
	 *   [name, [param1 => value1...],
	 *   ...
	 * ]
	 * - in the order that they appear in the input.
	 */
	public function extract_cs_shortcodes( $input ) {

		// Get RegEx that matches just ClickSold shortcodes.
		$shortcode_pattern = $this->get_shortcode_regex();

		// Process the regex, capture the matches and the count of matches.
		$match_count = preg_match_all('/'.$shortcode_pattern.'/s', $input, $matches);

		if($match_count == 0) { return array(); } // No matches were found so there is not much left for us to do.

		// Our processed results go here.
		$result = array();
				
		// Add all of the data out from the execution of the above regex into our format.
		foreach( $matches[2] as $index => $shortcode_name) {

			// $matches[0][$index] holds the original shortcode, if it starts with [[ and ends with a ]] we skip it as that's the shortcode escaping syntax.
			if(preg_match('/^\[\[/s', $matches[0][$index]) === 1 && preg_match('/\]\]$/s', $matches[0][$index]) === 1) { continue; }

			// Get an array of all of the parameters NOTE: we can't just use shortcode_parse_atts because it will lowercase all of our stuff.
			$params = array();
			$pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
			if ( preg_match_all($pattern, $matches[3][$index], $match, PREG_SET_ORDER) ) {
			
				$temp = "";
				foreach ($match as $m) {
					if (!empty($m[1])){
						if(array_key_exists($m[1], $params)) {
							if(is_array($params[$m[1]])) {  //Push to array
								$params[$m[1]][count($params[$m[1]])] = stripcslashes($m[2]);
							}else{  //Create array
								$temp = $params[$m[1]];
								$params[$m[1]] = array($temp, stripcslashes($m[2]));
							}
						} else {
							$params[$m[1]] = stripcslashes($m[2]);
						}
					}
					//Note: This block is commented out (deprecated?) for now as the first if check above always passes
					/*
					elseif (!empty($m[3]))
						$params[$m[3]] = stripcslashes($m[4]);
					elseif (!empty($m[5]))
						$params[$m[5]] = stripcslashes($m[6]);
					elseif (isset($m[7]) and strlen($m[7]))
						$params[] = stripcslashes($m[7]);
					elseif (isset($m[8]))
						$params[] = stripcslashes($m[8]);
					*/
				}
			}

			$result[ $index ] = array( $shortcode_name, $params, $this->shortcode_meta_data[ $shortcode_name ][ 2 ] );
		}
		
		return $result;
	}

	/**
	 * Returns the array of default parameters for the given shortcode or an empty array if
	 * the shortcode name given does not have it's meta data filled out.
	 */
	public function get_default_param_arr( $shortcode_name ) {
		$default_param_array = $this->shortcode_meta_data[ $shortcode_name ][ 0 ];

		if($default_param_array === NULL) { return array(); }
	
		return $default_param_array;
	}

	/**
	 * Returns true or false if a given parameter name is masked for a given shortcode.
	 * 
	 * Masked parameters can not be overriden by the parameters specified on the shortcode in the content.
	 * this is so it does not allow you to specify stuff accidentally that would not make sense.
	 */
	public function is_param_name_masked( $shortcode_name, $param_name ) {

		$masked_param_names_list = $this->shortcode_meta_data[ $shortcode_name ][ 1 ];

		if($masked_param_names_list === NULL) { return FALSE; } // Short code does not have any masked params defined.
	
		if(!isset($masked_param_names_list[ $param_name ])) { return FALSE; } // The given param name for the given short code is NOT masked.
		
		return TRUE;
	}


	/**
	 * Process the request as an ClickSold request if the content contains any cs_shortcodes (for the correct plugin_type of course).
	 */
	function cs_process_cs_shortcode_posts( $wp ){
		global $wp_query;

		$cs_request = null;
		if( $this->plugin_type == "cs_admin" ) {
			$cs_request = new CS_request("", "wp_utils" ); // NO cs_org_req yet, we'll build that shortly. We set the wp_utils section even though we're not really in that section just so it selects the correct controller.
		} else { // Default.
			$cs_request = new CS_request("", ""); // NO cs_org_req yet, we'll build that shortly. Also NO section as we're not in a section.
		}

		$cs_request->del_req_sec(); // Deletes the current request section, the constructor creates the first one which we need to get rid of as we'll be adding them in the loop below shortly.
		global $cs_response; // CS_response object available everywhere.

		if(is_null(get_queried_object())) return;
		
		/** Check for and process any post that may have ClickSold shortcodes on it. **/
		if(property_exists($wp_query->get_queried_object(), "post_content") && $this->contains_cs_shortcodes( $wp_query->get_queried_object()->post_content )) { // Assuming the post has content at all.
		
			// WARNING: Can't remove these filters as they are needed to format any surrounding html correctly, futher the filter for the Shortcodes Ultimate plugin
			//          can't be removed in this manner because it Shortcodes Ultimate removes the wpautop and wptexturize ones itself. If using Shortcodes Ultimate use [raw] shortcode around cs ones to get them to work correctly.
//			// NOTE: Here we have to disable the filters because we can't control the priority of when our content gets inserted as we can with the special pages.
//			// Disable WordPress native formatters
//			remove_filter( 'the_content', 'wpautop' );
//			remove_filter( 'the_content', 'wptexturize' );
//			// Disable other known formatters that cause issues with ClickSold.
//			remove_filter( 'the_content', 'su_custom_formatter', 99 ); // this one belongs to the Shortcodes Ultimate plugin.
		
			// Fetch an array with all the cs short codes extracted and parsed. format is [[name, [param1 => value1...], ...] - in the order that they appear on the page.
			$shortcode_records = $this->extract_cs_shortcodes( $wp_query->queried_object->post_content );
			
			/**
			 * For each CS shortcode on this page we...
			 * 1 - fetch the shortcode's base params and base param mask.
			 * 2 - serialize the base params.
			 * 3 - serialize any get params but respect the base param mask (aka, don't add the ones that are in the mask).
			 * 4 - serialize supplied params but respect the base param mask (aka, don't add the ones that are in the mask).
			 * 5 - set a new request section.
			 * 
			 * 2 to 3 are done by the cs_create_shortcode_request function.
			 * 
			 */
			foreach($shortcode_records as $index => $shortcode_record) {

				$cs_org_req = ""; // This will hold our serialized request to the plugin server.

				// For ease of use...
				$shortcode_name = $shortcode_record[0];
				$shortcode_param_arr = $shortcode_record[1];
				$shortcode_introspect = $shortcode_record[2];

				$cs_org_req = $this->cs_create_shortcode_request( $shortcode_name, $shortcode_param_arr, $shortcode_introspect );
				
				//DEBUG
				//error_log("\n" . $shortcode_name . "\n" . print_r($shortcode_param_arr, true) . "\n" . $shortcode_introspect . "\n" . $cs_org_req);
				
				// Add a new section to the request.
				$cs_request->add_req_section( $cs_org_req );
			}

			/** Send it to the plugin server -- (Assuming we have at least one section (if not something is messed badly)). **/
			if($cs_request->get_req_section_size() >= 1) {

				$cs_response = new CS_response($cs_request->request()); // Response var is global remember.
				if($cs_response->is_error()) return; // Stop processing if we couldn't access the server
			}

			/** While the results are going to be set for each shortcode when it's handler is called these need to be set here so we have our proper included js and css. **/
			add_action("wp_head", array($cs_response, "cs_get_header_contents_linked_only"), 0);
			add_action("wp_head", array($cs_response, "cs_get_header_contents_inline_only"), 11); // Needs to be ran at a highier priority as it needs to go AFTER the encueue stuff.
			add_action("wp_footer", array($cs_response, "cs_get_footer_contents"), 0);
			add_filter("the_content", "cs_styling_wrap", 2); //This line wraps all content around a div so our styles can take precedence over the template styles
			
			// Make double sure that the response is set to it's initial section.
			$cs_response->reset_response_section();
		}

	}

	/**
	 * Creates a cs_org_req query string for the given shortcode information.
	 *
	 * @param shortcode_name - the name of the shortcode.
	 * @param shortcode_param_arr - the parameters of the shortcode itself.
	 * @param shortcode_introspect - boolean indicating if we are to introspect the GET params too.
	 */
	function cs_create_shortcode_request( $shortcode_name, $shortcode_param_arr, $shortcode_introspect ) {

		$cs_org_req = "";

		// Add all the default params ( to the request )
		$delim = "";
		foreach($this->get_default_param_arr( $shortcode_name ) as $name => $value) {
			$cs_org_req .= $delim . $name . '=' . $value;
			$delim = '&';
		}

		// Introspect the GET superglobal if "introspect" flag is true
		if($shortcode_introspect === true && !empty($_GET)) {
			foreach($_GET as $param_name => $param_value){
				if( !$this->is_param_name_masked( $shortcode_name, $param_name ) ) {
					$cs_org_req .= $delim . $param_name . '=' . $param_value;
					$delim = '&';
				}
			}
		}
				
		// Add all the params specified on the shortcode as long as they are not masked.
		foreach($shortcode_param_arr as $name => $value) {
			if( !$this->is_param_name_masked( $shortcode_name, $name ) ) {
				if( is_array($value) ) {
					foreach( $value as $v ) {
						$cs_org_req .= $delim . $name . '=' . $v;
						$delim = '&';
					}
				} else {
					$cs_org_req .= $delim . $name . '=' . $value;
					$delim = '&';
				}
			}
		}

		return $cs_org_req;
	}

}

?>
