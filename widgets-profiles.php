<?php
/**
* Various widgets for ClickSold - Profile and Brokerage Profile widgets.
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
 * Personal Profile Widget class
 *
 * @author ClickSold
 **/
class Personal_Profile_Widget extends CS_Widget {
	
	/**
	 * Personal Profile Widget constructor
	 *
	 * @return void
	 * @author ClickSold
	 */
	function __construct() {
		$this->pluginDomain = 'personal_profile_widget';
		$this->loadPluginTextDomain();
		$widget_ops = array( 'classname' => 'cs-widget-personal-profile', 'description' => __( 'Add your profile photo and contact information to your website.', $this->pluginDomain ) );
		$control_ops = array( 'id_base' => 'cs-widget-personal-profile' );
		parent::__construct('cs-widget-personal-profile', __('ClickSold Profile Widget', $this->pluginDomain), $widget_ops, $control_ops);
		
		if ( defined("WP_ADMIN") && WP_ADMIN ) {
    		$this->init_media_upload();
		} else if( is_admin() === false && is_active_widget(false, false, $this->id_base, true) ) {
			$this->get_widget_scripts(false);
		}
	}

	/**
	 * Widget frontend output
	 *
	 * @param array $args 
	 * @param array $instance 
	 * @return void
	 * @author ClickSold
	 */
	function widget( $args, $instance ) {
		extract( $args );
		extract( $instance );
		$title = apply_filters( 'widget_title', empty( $title ) ? '' : $title );
		include( $this->getTemplateHierarchy( 'cs_template_personal-profile-widget_', 'personal-profile-widget' ) );
	}

	/**
	 * Update widget options
	 *
	 * @param object $new_instance Widget Instance
	 * @param object $old_instance Widget Instance 
	 * @return object
	 * @author ClickSold
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		if ( isset($new_instance['description']) ) {
			if ( current_user_can('unfiltered_html') ) {
				$instance['description'] = $new_instance['description'];
			} else {
				$instance['description'] = wp_filter_post_kses($new_instance['description']);
			}
		}
		$instance['link'] = $new_instance['link'];
		$instance['width'] = $new_instance['width'];
		$instance['height'] = $new_instance['height'];
		$instance['image'] = $new_instance['image'];
		$instance['imageurl'] = $this->get_image_url($new_instance['image'],$new_instance['width'],$new_instance['height']);  // image resizing not working right now
		if( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) {
			$instance['imageurl'] = str_replace('http://', 'https://', $instance['imageurl']);
		}
		$instance['phone'] = $new_instance['phone'];
		$instance['mobilePhone'] = $new_instance['mobilePhone'];
		$instance['fax'] = $new_instance['fax'];
		$instance['email'] = $new_instance['email'];
		
		$instance['showIcons'] = !empty($new_instance['showIcons']) ? 1 : 0;
		return $instance;
	}

	/**
	 * Form UI
	 *
	 * @param object $instance Widget Instance
	 * @return void
	 * @author ClickSold
	 */
	function form( $instance ) {

		$instance = wp_parse_args( (array) $instance, array( 
			'title' => '', 
			'description' => '', 
			'link' => '', 
			'width' => '', 
			'height' => '', 
			'image' => '',
			'imageurl' => '',
			'phone' => '',
			'mobilePhone' => '',
			'fax' => '',
			'email' => '',
			'showIcons' => ''
		) );
		$showIcons = isset( $instance['showIcons'] ) ? (bool) $instance['showIcons'] : false;
		include( $this->getTemplateHierarchy( 'cs_template_personal-profile-widget_', 'personal-profile-widget-admin' ) );
	}
	
}

/**
 * Brokerage Info Widget Class
 * @author ClickSold
 */
class Brokerage_Info_Widget extends CS_Widget {

	private $PLUGIN_NAME = 'ClickSold Brokerage Info Widget';
	private $PLUGIN_SLUG = 'cs-brokerage-info-widget';
	private $PLUGIN_CLASSNAME = 'widget_brokerage_info';
	private $PLUGIN_BROK_LOGOS = array();
	private $PLUGIN_DEFAULTS = array (
		'name' => '',
		'logo_src' => '',
		'upload_logo_src' => '',
		'addr' => '',
		'phone' => '',
		'fax' => '',
		'email' => '',
		'web' => '',
		'text' => ''
	);
	
	function __construct() {
	
		global $PLUGIN_NAME;
		global $PLUGIN_SLUG;
		global $PLUGIN_CLASSNAME;
		
		$widget_opts = array (
			'classname' => $this->PLUGIN_CLASSNAME, 
			'description' => 'Widget containing user\'s brokerage information'
		);	
		
		parent::__construct($this->PLUGIN_SLUG, $this->PLUGIN_NAME, $widget_opts);
		
		// Load JavaScript and Stylesheets
		if ( defined("WP_ADMIN") && WP_ADMIN ) {
			global $pagenow;
			if( 'widgets.php' == $pagenow ) $this->get_brokerage_logos();  //Load array of brokerage logos from server
    		$this->init_media_upload();
		} else if( is_admin() === false && is_active_widget(false, false, $this->id_base, true) ) {
			$this->get_widget_scripts(false);
		}
	}
	
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		echo $before_widget;
		
		include( $this->getTemplateHierarchy( 'cs_template_brokerage-info-widget_', 'brokerage-info-widget' ) );
		
		echo $after_widget;
	}
	
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		
		$instance['name'] = $new_instance['name'];
		$instance['logo_src'] = $new_instance['logo_src'];
		$instance['upload_logo_src'] = $new_instance['upload_logo_src'];
		$instance['addr'] = $new_instance['addr'];
		$instance['phone'] = $new_instance['phone'];
		$instance['fax'] = $new_instance['fax'];
		$instance['email'] = $new_instance['email'];
		$instance['web'] = $new_instance['web'];
		$instance['text'] = $new_instance['text'];
		
		return $instance;
	}
	
	function form($instance) {
		global $PLUGIN_DEFAULTS;
		global $PLUGIN_BROK_LOGOS;
		
		if(empty($PLUGIN_BROK_LOGOS)) $this->get_brokerage_logos();  //Will always run after form submit
		
		// Get list of brokerages and associated logos from server
		$brok_logos = $PLUGIN_BROK_LOGOS;
		
		$this->PLUGIN_DEFAULTS['logo_src'] = $brok_logos[1]["src"];		
		$instance = wp_parse_args((array) $instance, $this->PLUGIN_DEFAULTS);
		
		include( $this->getTemplateHierarchy( 'cs_template_brokerage-info-widget_', 'brokerage-info-widget-admin' ) );
		
	}
		
	/*--------------------------------------------------*/
	/* Private Functions
	/*--------------------------------------------------*/
	
	/**
	 *  Queries the server for a list of available brokerage logos
	 */
	private function get_brokerage_logos(){
		global $CS_SECTION_ADMIN_PARAM_CONSTANT;
		global $PLUGIN_BROK_LOGOS;
		
		$cs_request = new CS_request("pathway=562", $CS_SECTION_ADMIN_PARAM_CONSTANT["wp_admin_pname"]);
		$cs_response = new CS_response($cs_request->request());
		if($cs_response->is_error()) return;
		$json_response = $cs_response->cs_get_json();
		
		$PLUGIN_BROK_LOGOS = $json_response['brok_images'];
	}
	
} // end class

?>
