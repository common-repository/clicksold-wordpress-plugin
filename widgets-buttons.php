<?php
/**
* Various widgets for ClickSold -- Button type widgets.
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
 * IDX Search Widget Class
 * @author ClickSold
 */
class IDX_Search_Widget extends CS_Widget {
	
	private $default_img_url = null;
	private $PLUGIN_NAME = 'ClickSold MLS&reg; Search Widget';
	private $PLUGIN_SLUG = 'cs-widget-idx-search';
	private $PLUGIN_CLASSNAME = 'cs-widget-idx-search';
	private $PLUGIN_DOMAIN = 'cs-widget-idx-search';

	
	/**
	 * IDX Search Widget constructor
	 *
	 * @return void
	 * @author ClickSold
	 */
	function __construct() {
		
		global $default_img_url;
		global $PLUGIN_NAME;
		global $PLUGIN_SLUG;
		global $PLUGIN_CLASSNAME;
		global $PLUGIN_DOMAIN;
		
		$this->pluginDomain = $PLUGIN_DOMAIN;
		$this->default_img_url = plugins_url('images/widget-idx.png', __FILE__);
	
		$this->loadPluginTextDomain();
		$widget_ops = array( 
			'classname' => $this->PLUGIN_CLASSNAME, 
			'description' => 'Add a link to the MLS&reg; Search page in your website\'s widget bar.' 
		);
		
		parent::__construct($this->PLUGIN_SLUG, $this->PLUGIN_NAME, $widget_ops);

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
		include( $this->getTemplateHierarchy( 'cs_template_idx-search-widget_', 'idx-search-widget' ) );
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
		$instance['imagetype'] = $new_instance['imagetype'];
		$instance['image'] = $new_instance['image'];
		$instance['link'] = $new_instance['link'];
		$instance['alt_text'] = $new_instance['alt_text'];
		$instance['smallText'] = $new_instance['smallText'];
		$instance['largeText'] = $new_instance['largeText'];
		
		if($new_instance['imagetype'] == "custom") {
			$instance['imageurl'] = $this->get_image_url($new_instance['image'],$new_instance['width'],$new_instance['height']);  // image resizing not working right now
		} else { 
			$instance['imageurl'] = $new_instance['image'];
		}
		
		if( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) {
			$instance['imageurl'] = str_replace('http://', 'https://', $instance['imageurl']);
		}

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
		global $wpdb;
		global $wp_rewrite;
		
		$url = '#';
		
		//Get IDX page link, if exists...
		$idx_page = $wpdb->get_row("SELECT post_name, guid FROM " . $wpdb->posts . " WHERE post_name like '%mls%' AND post_name like '%search%' AND post_type = 'page' AND post_status != 'trash'");
		if(!is_null($idx_page)) {
			if($wp_rewrite->using_permalinks()) 
				$url = '/' . $idx_page->post_name;
			else 
				$url = $idx_page->guid;
		}

		global $default_img_url;
		$instance = wp_parse_args((array) $instance, array(
			'imagetype' => 'default', 
			'image' => $this->default_img_url, 
			'link' => $url,
			'alt_text' => 'MLS&reg; Search', 
			'smallText' => 'Find All Listings on a',
			'largeText' => 'Map-Based Search',
			'imageurl' => $this->default_img_url
		));
				
		include( $this->getTemplateHierarchy( 'cs_template_idx-search-widget_', 'idx-search-widget-admin' ) );
	}
	
}

/**
 * Mobile Site Widget Class
 * @author ClickSold
 */
class Mobile_Site_Widget extends CS_Widget {

	private $default_img_url = null;
	private $PLUGIN_NAME = 'ClickSold Mobile Site Widget';
	private $PLUGIN_SLUG = 'cs-widget-mobile-site';
	private $PLUGIN_CLASSNAME = 'cs-widget-mobile-site';
	private $PLUGIN_DOMAIN = 'cs-widget-mobile-site';
	
	/**
	 * Mobile Site Widget constructor
	 *
	 * @return void
	 * @author ClickSold
	 */
	function __construct() {
	
		global $default_img_url;
		global $PLUGIN_NAME;
		global $PLUGIN_SLUG;
		global $PLUGIN_CLASSNAME;
		global $PLUGIN_DOMAIN;
		
		$this->pluginDomain = $PLUGIN_DOMAIN;
		$this->mobileSiteUrl = plugins_url( "cs_mobile.php", __FILE__);
		$this->default_img_url = plugins_url('images/widget-mobile.png', __FILE__);
		
		$this->loadPluginTextDomain();
		$widget_ops = array( 
			'classname' => $this->PLUGIN_CLASSNAME, 
			'description' => 'Add a link to your ClickSold mobile site in your website\'s widget bar.' 
		);
		
		parent::__construct($this->PLUGIN_SLUG, $this->PLUGIN_NAME, $widget_ops);

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
		include( $this->getTemplateHierarchy( 'cs_template_mobile-site-widget_', 'mobile-site-widget' ) );
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
		$instance['imagetype'] = $new_instance['imagetype'];
		$instance['image'] = $new_instance['image'];
		$instance['alt_text'] = $new_instance['alt_text'];
		$instance['smallText'] = $new_instance['smallText'];
		$instance['largeText'] = $new_instance['largeText'];
		
		if($new_instance['imagetype'] == "custom") {
			$instance['imageurl'] = $this->get_image_url($new_instance['image'],$new_instance['width'],$new_instance['height']);  // image resizing not working right now
		} else {
			$instance['imageurl'] = $new_instance['image'];
		}
		
		if( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ) $instance['imageurl'] = str_replace('http://', 'https://', $instance['imageurl']);		
		
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
		$instance = wp_parse_args((array) $instance, array(
			'imagetype' => 'default', 
			'image' => $this->default_img_url, 
			'alt_text' => 'Mobile Version', 
			'smallText' => 'Search Real Estate on Your',
			'largeText' => 'Mobile Device',
			'imageurl' => $this->default_img_url
		));
		include( $this->getTemplateHierarchy( 'cs_template_mobile-site-widget_', 'mobile-site-widget-admin' ) );
	}
}

/**
 * Buying Information Widget Class
 * @author ClickSold
 */
class Buying_Info_Widget extends CS_Widget{

	private $default_img_url = null;
	private $PLUGIN_NAME = 'ClickSold Buying Info Widget';
	private $PLUGIN_SLUG = 'cs-widget-buying-info';
	private $PLUGIN_CLASSNAME = 'cs-widget-buying-info';
	private $PLUGIN_DOMAIN = 'cs-widget-buying-info';
	
	function __construct(){
	
		global $default_img_url;
		global $PLUGIN_NAME;
		global $PLUGIN_SLUG;
		global $PLUGIN_CLASSNAME;
		global $PLUGIN_DOMAIN;
		
		$this->pluginDomain = $PLUGIN_DOMAIN;
		$this->default_img_url = plugins_url('images/widget-house.png', __FILE__);
		
		$this->loadPluginTextDomain();
		$widget_opts = array(
			'classname' => $this->PLUGIN_CLASSNAME,
			'description' => 'Add a section for property buying information in your website\'s widget bar.'
		);
		
		parent::__construct( $this->PLUGIN_SLUG, $this->PLUGIN_NAME, $widget_opts );
		
		if ( defined("WP_ADMIN") && WP_ADMIN ) {
    		$this->init_media_upload();
		} else if( is_admin() === false && is_active_widget(false, false, $this->id_base, true) ) {
			$this->get_widget_scripts(false);
		}
	}
	
	function widget( $args, $instance ){
		extract( $args );
		extract( $instance );
		include( $this->getTemplateHierarchy( 'cs_template_buying-info-widget_', 'buying-info-widget' ) );
	}
	
	function update( $new_instance, $old_instance ){
		$instance['imagetype'] = $new_instance['imagetype'];
		$instance['image'] = $new_instance['image'];
		$instance['link'] = $new_instance['link'];
		$instance['alt_text'] = $new_instance['alt_text'];
		$instance['smallText'] = $new_instance['smallText'];
		$instance['largeText'] = $new_instance['largeText'];
		
		if($new_instance['imagetype'] == "custom") $instance['imageurl'] = $this->get_image_url($new_instance['image'],$new_instance['width'],$new_instance['height']);  // image resizing not working right now
		else $instance['imageurl'] = $new_instance['image'];
		
		if( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) {
			$instance['imageurl'] = str_replace('http://', 'https://', $instance['imageurl']);		
		}
		
		return $instance;
	}
	
	function form( $instance ){
		global $wpdb;
		global $wp_rewrite;
		
		$url = '#';
		
		//Get buying page link, if exists...
		$buying_page = $wpdb->get_row("SELECT post_name, guid FROM " . $wpdb->posts . " WHERE post_name = 'buying' AND post_type = 'page' AND post_status != 'trash'");
		if(!is_null($buying_page)) {
			if($wp_rewrite->using_permalinks()) 
				$url = '/' . $buying_page->post_name;
			else 
				$url = $buying_page->guid;
		}
	
		$instance = wp_parse_args((array) $instance, array(
			'imagetype' => 'default', 
			'image' => $this->default_img_url, 
			'link' => $url,
			'alt_text' => 'Buying Information', 
			'smallText' => 'Get Critical Information on',
			'largeText' => 'Buying Real Estate',
			'imageurl' => $this->default_img_url
		));
		include( $this->getTemplateHierarchy( 'cs_template_buying-info-widget_', 'buying-info-widget-admin' ) );
	}
	
}

/**
 * Selling Information Widget Class
 * @author ClickSold
 */
class Selling_Info_Widget extends CS_Widget{

	private $default_img_url = null;
	private $PLUGIN_NAME = 'ClickSold Selling Info Widget';
	private $PLUGIN_SLUG = 'cs-widget-selling-info';
	private $PLUGIN_CLASSNAME = 'cs-widget-selling-info';
	private $PLUGIN_DOMAIN = 'cs-widget-selling-info';

	function __construct(){
	
		global $default_img_url;
		global $PLUGIN_NAME;
		global $PLUGIN_SLUG;
		global $PLUGIN_CLASSNAME;
		global $PLUGIN_DOMAIN;
		
		$this->pluginDomain = $PLUGIN_DOMAIN;
		$this->default_img_url = plugins_url('images/widget-forsale.png', __FILE__);
		
		$this->loadPluginTextDomain();
		$widget_opts = array(
			'classname' => $this->PLUGIN_CLASSNAME,
			'description' => 'Add a section for property selling information in your website\'s widget bar.'
		);
		
		parent::__construct( $this->PLUGIN_SLUG, $this->PLUGIN_NAME, $widget_opts );
		
		if ( defined("WP_ADMIN") && WP_ADMIN ) {
    		$this->init_media_upload();
		} else if( is_admin() === false && is_active_widget(false, false, $this->id_base, true) ) {
			$this->get_widget_scripts(false);
		}
	}
	
	function widget( $args, $instance ){
		extract( $args );
		extract( $instance );
		include( $this->getTemplateHierarchy( 'cs_template_selling-info-widget_', 'selling-info-widget' ) );
	}
	
	function update( $new_instance, $old_instance ){
		$instance['imagetype'] = $new_instance['imagetype'];
		$instance['image'] = $new_instance['image'];
		$instance['link'] = $new_instance['link'];
		$instance['alt_text'] = $new_instance['alt_text'];
		$instance['smallText'] = $new_instance['smallText'];
		$instance['largeText'] = $new_instance['largeText'];
		
		if($new_instance['imagetype'] == "custom") $instance['imageurl'] = $this->get_image_url($new_instance['image'],$new_instance['width'],$new_instance['height']);  // image resizing not working right now
		else $instance['imageurl'] = $new_instance['image'];
		
		if( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) {
			$instance['imageurl'] = str_replace('http://', 'https://', $instance['imageurl']);		
		}
		
		return $instance;
	}
	
	function form( $instance ){
		global $wpdb;
		global $wp_rewrite;
		
		$url = '#';
		
		//Get selling page link, if exists...
		$selling_page = $wpdb->get_row("SELECT post_name, guid FROM " . $wpdb->posts . " WHERE post_name = 'selling' AND post_type = 'page' AND post_status != 'trash'");
		if(!is_null($selling_page)) {
			if($wp_rewrite->using_permalinks()) 
				$url = '/' . $selling_page->post_name;
			else 
				$url = $selling_page->guid;
		}
	
		$instance = wp_parse_args((array) $instance, array(
			'imagetype' => 'default', 
			'image' => $this->default_img_url, 
			'link' => $url,
			'alt_text' => 'Selling Information', 
			'smallText' => 'Get Critical Information on',
			'largeText' => 'Selling Real Estate',
			'imageurl' => $this->default_img_url
		));

		include( $this->getTemplateHierarchy( 'cs_template_selling-info-widget_', 'selling-info-widget-admin' ) );
	}
	
}



?>
