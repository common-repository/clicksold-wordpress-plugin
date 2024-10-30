<?php
/**
* Various widgets for ClickSold - Widgets that support searching.
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

/**
 * Listing Quick Search Widget Class
 * @author ClickSold
 */
class Listing_QS_Widget extends CS_Widget{
	
	private $PLUGIN_NAME = 'ClickSold Listing Quick Search Widget';
	private $PLUGIN_SLUG = 'cs-listing-quick-search-widget';
	private $PLUGIN_CLASSNAME = 'widget-listing-quick-search';

	function __construct(){
	
		global $PLUGIN_NAME;
		global $PLUGIN_SLUG;
		global $PLUGIN_CLASSNAME;
		
		$this->pluginDomain = 'listing_quick_search_widget';
		
		$this->loadPluginTextDomain();
		$widget_opts = array(
			'classname' => $this->PLUGIN_CLASSNAME,
			'description' => 'Add a section for a text-based listing search in your website\'s widget bar.'
		);
		
		parent::__construct( $this->PLUGIN_SLUG, $this->PLUGIN_NAME, $widget_opts );
		
		global $pagenow;
		
		// Add scripts for site usage
		if( is_admin() === false && is_active_widget(false, false, $this->id_base, true) && !wp_script_is($this->PLUGIN_SLUG . '-js') ) {
			
			//NOTE: If we avoid using base_header on our script calls, we will need to use the scripts below
			//wp_enqueue_script('jquery');
			//wp_enqueue_script('jquery-ui-core');
			//wp_enqueue_script('jquery-ui-position');
			//wp_enqueue_script('jquery-ui-autocomplete');
			
			$this->get_widget_scripts(false);
		}
	}
		
	function widget( $args, $instance ){
		global $wpdb;
		global $wp_rewrite;
		global $CS_GENERATED_PAGE_PARAM_CONSTANTS;

		// Get the urls for all of the required ClickSold sections.
		$idx_url = $this->get_cs_section_relative_url('idx');
		$listings_url = $this->get_cs_section_relative_url('listings');
		$comm_url = $this->get_cs_section_relative_url('community');
		if(is_null($idx_url) || is_null($listings_url) || is_null($comm_url)) return;
		
		if($wp_rewrite->using_permalinks()) {
			$idx_url .= '/?term=';
			$listings_url .= '/';
			$comm_url .= '/';
		} else {
			$idx_url .= '&term=';
			$listings_url .= '&mlsNum='; 
			$comm_url .= '&city=#&neigh=#'; //Note: the js will fill in the "neigh" query param
		}

		$using_permalinks = $wp_rewrite->using_permalinks();
		$widgetStyles = "";
		$formContainerStyles = "";
		$backgroundStyles = "";
		$vertStyles = "";
		
		if(!empty($instance['widgetHeight'])) { $widgetStyles = ' style="height:'.$instance['widgetHeight'].';"'; }
		
		if(!empty($instance['minWidth'])) { $formContainerStyles .= 'min-width:'.$instance['minWidth'].';'; }
		if(!empty($instance['maxWidth'])) { $formContainerStyles .= 'max-width:'.$instance['maxWidth'].';'; }
		if(!empty($instance['minHeight'])) { $formContainerStyles .= 'min-height:'.$instance['minHeight'].';'; }
		if(!empty($instance['maxHeight'])) { $formContainerStyles .= 'max-height:'.$instance['maxHeight'].';'; }
		
		if(!empty($instance['backgroundColor'])) { 
			if(substr_compare($instance['backgroundColor'], "rgba", 0, 4, true) === 0) {
				$backgroundStyles = $instance['backgroundColor'];
			} else if(substr_compare($instance['backgroundColor'], "rgb", 0, 3, true) === 0) {
				preg_match('/\((.*?)\)/', $instance['backgroundColor'], $rgb);
				if(!empty($instance['backgroundOpacity']) && count($rgb) == 3) {
					$backgroundStyles = 'background:rgba('.$rgb[0].', '.$rgb[1].', '.$rgb[2].', '.$instance['backgroundOpacity'].');'; 
				} else {
					$backgroundStyles = 'background:'.$instance['backgroundColor'].';';
				}
			} else if(substr_compare($instance['backgroundColor'], "#", 0, 1) === 0) {
				if(!empty($instance['backgroundOpacity'])) {
					$color = ltrim($instance['backgroundColor'], "#");
					if(strlen($color) == 6) {
						$color_parts = str_split($color, 2);
						$rgb = array_map('hexdec', $color_parts);
						$backgroundStyles = 'background:rgba('.$rgb[0].', '.$rgb[1].', '.$rgb[2].', '.$instance['backgroundOpacity'].');'; 
					} else if(strlen($color) == 3) {
						$color_parts = str_split($color);
						$hex = array($color_parts[0].$color_parts[0], $color_parts[1].$color_parts[1], $color_parts[2].$color_parts[2]);
						$rgb = array_map('hexdec', $hex);
						$backgroundStyles = 'background:rgba('.$rgb[0].', '.$rgb[1].', '.$rgb[2].', '.$instance['backgroundOpacity'].');'; 
					} else {
						$backgroundStyles = 'background-color:'.$instance['backgroundColor'].';';
					}
				} else {
					$backgroundStyles = 'background-color:'.$instance['backgroundColor'].';';
				}
			}
		}
		
		if(!empty($instance['top'])) {
			$vertStyles .= 'position:relative;top:'.$instance['top'].';';
		}
		
		if(!empty($instance['translateY'])) {
			$vertStyles .= 'transform:translateY('.$instance['translateY'].');-webkit-transform:translateY('.$instance['translateY'].');-ms-transform:translateY('.$instance['translateY'].');';
			//$vertStyles .= 'transform-style:preserve-3d;-webkit-transform-style:preserve-3d;-moz-transform-style:preserve-3d;';
		}
		
		if(!empty($formContainerStyles) || !empty($backgroundStyles) || !empty($vertStyles)) {
			$formContainerStyles = ' style="' . $vertStyles . $formContainerStyles . $backgroundStyles . '"'; 
		}
		
		extract( $args );
		extract( $instance );
		
		include( $this->getTemplateHierarchy( 'cs_template_listing-quick-search-widget_', 'listing-quick-search-widget' ) );
	}
	
	function update( $new_instance, $old_instance ){
		$instance['title'] = $new_instance['title'];
		$instance['showMLSSearchLogo'] = !empty($new_instance['showMLSSearchLogo']) ? 1 : 0;
		$instance['showSearchButton'] = !empty($new_instance['showSearchButton']) ? 1 : 0;
		$instance['backgroundColor'] = $new_instance['backgroundColor'];
		$instance['backgroundOpacity'] = $new_instance['backgroundOpacity'];
		$instance['minWidth'] = $new_instance['minWidth'];
		$instance['maxWidth'] = $new_instance['maxWidth'];
		$instance['minHeight'] = $new_instance['minHeight'];
		$instance['maxHeight'] = $new_instance['maxHeight'];
		$instance['translateY'] = $new_instance['translateY'];
		$instance['widgetHeight'] = $new_instance['widgetHeight'];
		$instance['top'] = $new_instance['top'];
		return $instance;
	}
	
	function form( $instance ){	
		$instance_opts = array( 
			'title' => '',
			'showMLSSearchLogo' => '',
			'showSearchButton' => '',
			'backgroundColor' => '',
			'backgroundOpacity' => '',
			'minWidth' => '',
			'maxWidth' => '',
			'minHeight' => '',
			'maxHeight' => '',
			'translateY' => '-50',
			'widgetHeight' => '',
			'top' => '50%'
		);
	
		$showMLSSearchLogo = isset( $instance['showMLSSearchLogo'] ) ? (bool) $instance['showMLSSearchLogo'] : true;
		$showSearchButton = isset( $instance['showSearchButton'] ) ? (bool) $instance['showSearchButton'] : false;
		
		$instance = wp_parse_args((array) $instance, $instance_opts);
		
		include( $this->getTemplateHierarchy( 'cs_template_listing-quick-search-widget_', 'listing-quick-search-widget-admin' ) );
	}
}

/**
 * IDX Quick Search Widget
 * @author ClickSold
 */
class IDX_QS_Widget extends CS_Widget {
	
	private $PLUGIN_NAME = 'ClickSold IDX Quick Search Widget';
	private $PLUGIN_SLUG = 'cs-idx-qs-widget';
	private $PLUGIN_CLASSNAME = 'widget-idx-qs';
	private $PLUGIN_PROP_TYPES = array();
	
	function __construct() {
		global $PLUGIN_NAME;
		global $PLUGIN_SLUG;
		global $PLUGIN_CLASSNAME;
		
		$this->pluginDomain = 'idx_qs_widget';
		
		$this->loadPluginTextDomain();
		$widget_opts = array(
			'classname' => $this->PLUGIN_CLASSNAME,
			'description' => 'Adds the ability to run a IDX search from the current page'
		);
		
		parent::__construct( $this->PLUGIN_SLUG, $this->PLUGIN_NAME, $widget_opts );
		
		global $pagenow;
		if( defined( "WP_ADMIN" ) && WP_ADMIN && 'widgets.php' == $pagenow ) {
			$this->get_widget_scripts(true);
		}else if( is_admin() === false && is_active_widget(false, false, $this->id_base, true) && !wp_script_is($this->PLUGIN_SLUG . '-js') ) {
			$this->get_widget_scripts(false);
		}
	}
	
	function widget( $args, $instance ) {
		global $wpdb;
		global $wp_rewrite;
		global $CS_GENERATED_PAGE_PARAM_CONSTANTS;
				
		$table_name = $wpdb->prefix . "cs_posts";
		
		// Defaults - front end of widget -- used when existing widgets are upgraded to include new options.
		if( !isset( $instance['include_community_search'] ) ) { $instance['include_community_search'] = 0; }
		if( !isset( $instance['search_goes_to'] ) ) { $instance['search_goes_to'] = 0; }
		
		//Get the relative url to the IDX search page
		$idx_url = $this->get_cs_section_relative_url('idx');
		if(is_null($idx_url)) return;
		
		// Update it such that the the js gets what it expects.
		if($wp_rewrite->using_permalinks()) {
			$idx_url .= '/?';
		} else {
			$idx_url .= '&';
		}
		
		// If the communities interface is set to be used... we process that too.
		if( $instance['include_community_search'] ) {
			
			//Get the section name of the community page
			$community_url = $this->get_cs_section_relative_url('community');
			if(is_null($community_url)) return;
	
			// Get the proper format that we then feed to the JavaScript.
			if($wp_rewrite->using_permalinks()) {

				// Note: get_cs_section_relative_url - adds the subdirectory for any subdir installs. Here (for some reason unknown to me EZ 2016-01-20) we use an absolute url therefore we must remove this part.
				// this does nothing for non subdir installs.
				$community_url = substr( $community_url, strlen( parse_url(site_url(), PHP_URL_PATH) ) );
				
				$community_url .= '/<city>/<neigh>/1';
				// Turn url absolute
				if(method_exists($this, 'is_multisite') && is_multisite()) $community_url = network_home_url($community_url);
				else $community_url = home_url($community_url);
			} else $community_url .= '&city=<city>&neigh=<neigh>'; 
			
			// Get the cities & neighbourhoods
			$regions_all = $this->get_community_search_regions($instance, $args['widget_id']);
			$regions = array();
			$city_list = array();
			
			foreach($regions_all as $city => $neigh) {
				if(!array_key_exists($city, $regions)) {
					$regions[$city] = $neigh;
					$city_list[] = $city;
				} else {
					$regions[$city] = array_push($regions[$city], $neigh);
				}
			}
		}

		$using_permalinks = $wp_rewrite->using_permalinks();

		extract( $args );
		extract( $instance );
		
		$widget_styles = '';
		if(is_numeric($min_width)) $widget_styles .= 'min-width:' . $min_width . 'px;';
		if(is_numeric($min_height)) $widget_styles .= 'min-height:' . $min_height . 'px;';
		if(is_numeric($max_width)) $widget_styles .= 'max-width:' . $max_width . 'px;';
		if(is_numeric($max_height)) $widget_styles .= 'max-height:' . $max_height . 'px;';
		
		include( $this->getTemplateHierarchy( 'cs_template_idx-qs-widget_', 'idx-quick-search-widget' ) );
	}
	
	function update( $new_instance, $old_instance ) {
		if(!is_numeric($new_instance['min_width'])) $new_instance['min_width'] = '';
		if(!is_numeric($new_instance['min_height'])) $new_instance['min_height'] = '';
		if(!is_numeric($new_instance['max_width'])) $new_instance['max_width'] = '';
		if(!is_numeric($new_instance['max_height'])) $new_instance['max_height'] = '';
		$new_instance['compact_vers'] = !empty($new_instance['compact_vers']) ? 1 : 0;
		$new_instance['wide_vers'] = !empty($new_instance['wide_vers']) ? 1 : 0;

		return $new_instance;
	}
	
	function form( $instance ) {
	    global $PLUGIN_PROP_TYPES;
		if(empty($PLUGIN_PROP_TYPES)) $this->get_property_types();
		$prop_types = $PLUGIN_PROP_TYPES;
		
		$instance_opts = array(
			'default_prop_type' => $PLUGIN_PROP_TYPES[0]['val'],
			'min_width' => '',
			'min_height' => '',
			'max_width' => '',
			'max_height' => '',
			'compact_vers' => '',
			'wide_vers' => '',
			'force_control_type' => '0',
			'include_community_search' => '0',
			'search_goes_to' => 'idx_map',
			'incOrExcSelected' => 1,
			'search_goes_to' => 'idx_map',
			'cities' => array()
		);
		
		// Defaults - Admin - defaults for variables used directly on the form.
		$compact_vers = isset( $instance['compact_vers'] ) ? (bool) $instance['compact_vers'] : false;
		$wide_vers = isset( $instance['wide_vers'] ) ? (bool) $instance['wide_vers'] : false;
		$force_control_type = isset( $instance['force_control_type'] ) ? $instance['force_control_type'] : '0';
		$include_community_search = isset( $instance['include_community_search'] ) ? $instance['include_community_search'] : '0';
		$search_goes_to = isset( $instance['search_goes_to'] ) ? $instance['search_goes_to'] : 'idx_map';

		$instance = wp_parse_args((array) $instance, $instance_opts);

		// This will not run correctly unless it's got a default for incOrExcSelected -- which is included from the defaults array above.
		$city_list_avail = $this->get_init_config_community_search_regions($instance);

		include( $this->getTemplateHierarchy( 'cs_template_idx-qs-widget_', 'idx-quick-search-widget-admin' ) );
	}
	
	/**
	*
	*/
	private function get_property_types() {
		global $PLUGIN_PROP_TYPES;
		global $CS_SECTION_ADMIN_PARAM_CONSTANT;
		
		$cs_request = new CS_request('pathway=640&idxQuickSearch_loadConfig=true', $CS_SECTION_ADMIN_PARAM_CONSTANT["wp_admin_pname"]);
		$cs_response = new CS_response($cs_request->request());
		if($cs_response->is_error()) return '';
		
		$response = $cs_response->cs_get_json();
		$PLUGIN_PROP_TYPES = $response['cs_idx_qs_prop_types'];
	}
	
}

/**
 * Community Search Widget
 * @author ClickSold
 */
class Community_Search_Widget extends CS_Widget{
	
	private $PLUGIN_NAME = 'ClickSold Community Search Widget';
	private $PLUGIN_SLUG = 'cs-community-search-widget';
	private $PLUGIN_CLASSNAME = 'widget-community-search';
	
	function __construct(){
		global $PLUGIN_NAME;
		global $PLUGIN_SLUG;
		global $PLUGIN_CLASSNAME;
		
		$this->pluginDomain = 'community_search_widget';
		
		$this->loadPluginTextDomain();
		$widget_opts = array(
			'classname' => $this->PLUGIN_CLASSNAME,
			'description' => 'Widget that allows you to run a community search'
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
		global $wpdb;
		global $wp_rewrite;
		global $CS_GENERATED_PAGE_PARAM_CONSTANTS;
		global $blog_id;
				
		$table_name = $wpdb->prefix . "cs_posts";
		
		//Get the section name of the community page
		$community_url = $this->get_cs_section_relative_url('community');
		if(is_null($community_url)) return;

		// Get the proper format that we then feed to the JavaScript.
		if($wp_rewrite->using_permalinks()) {

			// Note: get_cs_section_relative_url - adds the subdirectory for any subdir installs. Here (for some reason unknown to me EZ 2016-01-20) we use an absolute url therefore we must remove this part.
			// this does nothing for non subdir installs.
			$community_url = substr( $community_url, strlen( parse_url(site_url(), PHP_URL_PATH) ) );

			$community_url .= '/<city>/<neigh>/1';
			// Turn url absolute
			if(method_exists($this, 'is_multisite') && is_multisite()) $community_url = network_home_url($community_url);
			else $community_url = home_url($community_url);
		} else $community_url .= '&city=<city>&neigh=<neigh>'; 
				
		// Get the cities & neighbourhoods
		$regions_all = $this->get_community_search_regions($instance, $args['widget_id']);
		$regions = array();
		$city_list = array();
		
		foreach($regions_all as $city => $neigh) {
			if(!array_key_exists($city, $regions)) {
				$regions[$city] = $neigh;
				$city_list[] = $city;
			} else {
				$regions[$city] = array_push($regions[$city], $neigh);
			}
		}
		
		$using_permalinks = $wp_rewrite->using_permalinks();

		extract( $args );
		extract( $instance );
		include( $this->getTemplateHierarchy( 'cs_template_community-search-widget_', 'community-search-widget' ) );
	}
	
	function update( $new_instance, $old_instance ){
		if(empty($old_instance)) {
			$instance['title'] = 'Communities';
			$instance['incOrExcSelected'] = 1;
		} else {
			$instance['title'] = $new_instance['title'];
			$instance['incOrExcSelected'] = $new_instance['incOrExcSelected'];
			$instance['cities'] = $new_instance['cities'];
		}
				
		return $instance;
	}
	
	function form( $instance ){	
		$instance_opts = array(
			'title' => 'Communities',
			'incOrExcSelected' => 1,
			'cities' => array()
		);
		
		$instance = wp_parse_args((array) $instance, $instance_opts);
		$city_list_avail = $this->get_init_config_community_search_regions($instance);
		include( $this->getTemplateHierarchy( 'cs_template_community-search-widget_', 'community-search-widget-admin' ) );
	}
	
}

?>
