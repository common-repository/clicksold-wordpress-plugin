<div class="<?php echo $this->id; ?>">
  <p>
    <label for="<?php echo $this->get_field_id('default_prop_type'); ?>">Default Property Type</label><br/>
    <select class="widefat" id="<?php echo $this->get_field_id('default_prop_type'); ?>" name="<?php echo $this->get_field_name('default_prop_type'); ?>">
<?php foreach($prop_types as $prop_type) { 
        if($instance['default_prop_type'] == $prop_type['val']) { ?>
      <option selected value="<?php echo $prop_type['val']; ?>"><?php echo $prop_type['name']; ?></option>
 <?php  } else { ?>
      <option value="<?php echo $prop_type['val']; ?>"><?php echo $prop_type['name']; ?></option>
<?php   }
      } ?>
    </select>
  </p>
  
  <p>
    <label for="<?php echo $this->get_field_id('min_width'); ?>">Minimum Width (px)</label><br/>
    <input class="widefat" type="text" id="<?php echo $this->get_field_id('min_width'); ?>" name="<?php echo $this->get_field_name('min_width'); ?>" value="<?php echo $instance['min_width']; ?>">
  </p>
  
  <p>
    <label for="<?php echo $this->get_field_id('min_height'); ?>">Minimum Height (px)</label><br/>
    <input class="widefat" type="text" id="<?php echo $this->get_field_id('min_height'); ?>" name="<?php echo $this->get_field_name('min_height'); ?>" value="<?php echo $instance['min_height']; ?>">
  </p>
  
  <p>
    <label for="<?php echo $this->get_field_id('max_width'); ?>">Maximum Width (px)</label><br/>
    <input class="widefat" type="text" id="<?php echo $this->get_field_id('max_width'); ?>" name="<?php echo $this->get_field_name('max_width'); ?>" value="<?php echo $instance['max_width']; ?>">
  </p>
  
  <p>
    <label for="<?php echo $this->get_field_id('max_height'); ?>">Maximum Height (px)</label><br/>
    <input class="widefat" type="text" id="<?php echo $this->get_field_id('max_height'); ?>" name="<?php echo $this->get_field_name('max_height'); ?>" value="<?php echo $instance['max_height']; ?>">
  </p>

  <p>
    <label for="<?php echo $this->get_field_id('compact_vers'); ?>">Compact</label><br/>
    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('compact_vers'); ?>" name="<?php echo $this->get_field_name('compact_vers'); ?>"<?php checked( $compact_vers ); ?> />
  </p>

  <p>
    <label for="<?php echo $this->get_field_id('wide_vers'); ?>">Wide</label><br/>
    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('wide_vers'); ?>" name="<?php echo $this->get_field_name('wide_vers'); ?>"<?php checked( $wide_vers ); ?> />
  </p>
  
  <p>
    <label for="<?php echo $this->get_field_id('force_control_type'); ?>">Force a specific type of controls?</label><br/>
	  <select class="widefat" id="<?php echo $this->get_field_id('force_control_type'); ?>" name="<?php echo $this->get_field_name('force_control_type'); ?>">
      <option<?php if($instance['force_control_type']=='0') echo ' selected' ?> value="0">Account Default</option>
      <option<?php if($instance['force_control_type']=='1') echo ' selected' ?> value="1">Force Sliders</option>
      <option<?php if($instance['force_control_type']=='2') echo ' selected' ?> value="2">Force Dropdowns</option>
    </select>
  </p>
  
  <p>
    <label for="<?php echo $this->get_field_id('include_community_search'); ?>">Include Community Search Interface?</label><br/>
	  <select class="widefat" id="<?php echo $this->get_field_id('include_community_search'); ?>" name="<?php echo $this->get_field_name('include_community_search'); ?>">
      <option<?php if($instance['include_community_search']=='0') echo ' selected' ?> value="0">No</option>
      <option<?php if($instance['include_community_search']=='1') echo ' selected' ?> value="1">Yes</option>
    </select>
  </p>

  <div id="community_search_config" style="display: none">
    <div id="cities_select">
      <p>
        <div style="width:225px;overflow:auto;">
          <div style="width:90px;float:left;">
	        <span style="text-align:center;">Available</span>
	        <select id="cities_avail" size="7" multiple style="width:90px;">
<?php echo $city_list_avail ?>	
		    </select>
	      </div>
          <div style="width:30px;float:left;padding-left:6px;padding-right:6px;padding-top:40px;">
	        <input id="remFromSelected" type="button" value="<<" />
	        <input id="addToSelected" type="button" value=">>" />
	      </div>
	      <div style="width:90px;float:left;">
	        <span style="text-align:center;">Selected</span>
	        <select id="<?php echo $this->get_field_id('cities'); ?>" name="<?php echo $this->get_field_name('cities'); ?>[]" size="7" multiple style="width:90px;">
<?php   if(!empty($instance['cities'])) {
          foreach($instance['cities'] as $city) { ?>
              <option title="<?php echo $city ?>" value="<?php echo $city ?>"><?php echo $city ?></option>
<?php     } 
        }?>
            </select>
          </div>
        </div>
      </p>
    </div>
  
    <p>
      <label for="<?php echo $this->get_field_id('incOrExcSelected'); ?>">Include / Exclude Selected Cities</label><br/>
      <input id="widget-<?php echo $this->id; ?>-inc" class="radio" type="radio" name="<?php echo $this->get_field_name('incOrExcSelected'); ?>" value="0" <?php if($instance['incOrExcSelected'] == 0) { ?>checked<?php } ?> /> <span>Include</span><br/>
      <input id="widget-<?php echo $this->id; ?>-exc" class="radio" type="radio" name="<?php echo $this->get_field_name('incOrExcSelected'); ?>" value="1" <?php if($instance['incOrExcSelected'] == 1) { ?>checked<?php } ?> /> <span>Exclude</span>
    </p>
    
    <p>
      <label for="<?php echo $this->get_field_id('search_goes_to'); ?>">Search Goes To?</label><br/>
	  <select class="widefat" id="<?php echo $this->get_field_id('search_goes_to'); ?>" name="<?php echo $this->get_field_name('search_goes_to'); ?>">
        <option<?php if($instance['search_goes_to']=='idx_map') echo ' selected' ?> value="idx_map">IDX Map</option>
        <option<?php if($instance['search_goes_to']=='communities') echo ' selected' ?> value="communities">Communities Section</option>
      </select>
    </p>
    
  </div>

  
</div>
<script type="text/javascript">
(function($){
	$(document).ready(function() {
		
		// Initialize the JavaScript -- This has to be done differently based on if this is the first adding of the widget or if the widget is already present in a sidebar. NOTE: if we're on the first add case then none of the $this->xyz values will be correct.
		if("<?php echo $this->id; ?>".match("__i__$") == null) { // Widget is already initialized, aka it has NOT just been added.
		
			$('.<?php echo $this->id; ?>:not(div[class$="__i__"])').csCommunitySearchWidgetAdmin({
				ajaxTarget : "<?php echo plugins_url( 'CS_ajax_request.php', dirname(__FILE__) ) ?>",
				cities_id : "<?php echo $this->get_field_id('cities'); ?>",
				widget_id : "<?php echo $this->id; ?>"
			});
			
			// Show hide the communities config interface depending on the user option.
			$('#widget-<?php echo $this->id; ?>-include_community_search').change(function (){
				if( $(this).val() == 0 ) { // Hide the interface.
					$('#community_search_config', '.<?php echo $this->id; ?>').slideUp();
				} else { // Show the interface.
					$('#community_search_config', '.<?php echo $this->id; ?>').slideDown();
				}
			});
			// If the option is already set to yes then we show the interface (it's hidden by default).
			if( $('#widget-<?php echo $this->id; ?>-include_community_search').val() == 1 ) {
				$('#community_search_config', '.<?php echo $this->id; ?>').slideDown();
			}
			
		} else { // Widget has just now been added to a sidebar.

			// Here we register an ajaxSuccess callback so that once the widget has been added we can initalize it properly once it has been added.
			// NOTE / WARNING - This will be registered once on the widgets page load and then again each time this widget is added -- so the initialization routine for the widget MUST be able to deal with being called more than once. (I know of no way to remove these callback functions once they have been added - EZ).
			jQuery(document).ajaxSuccess(function(e, xhr, settings) {
				var widget_id_base = 'cs-idx-qs-widget';
				if(	settings.data.search('action=save-widget') != -1 &&				// Present on each widget save.
					settings.data.search('id_base=' + widget_id_base) != -1 &&		// Only proceed if the widget save is for one of *these* widgets (a random widget won't do).
					settings.data.search('add_new=multi') != -1) {					// This parameter is present when a widget is added to a sidebar but is not present when you click the save button.

					// Get the class name for this widget (which is used as it's unique identifier) eg: cs-community-search-widget-6
					var widget_id = settings.data.match(/widget-id=[^&]*/g);
					widget_id = widget_id[0].replace(/widget-id=/g, ''); // Clear the parameter name from the above match.

					// Initialize the widget based on the widget-id (the class name) that we were provided by the ajaxSuccess subsystem.
					$('.'+widget_id+':not(div[class$="__i__"])').csCommunitySearchWidgetAdmin({
						ajaxTarget : "<?php echo plugins_url( 'CS_ajax_request.php', dirname(__FILE__) ) ?>",
						cities_id : "widget-" + widget_id + "-cities",
						widget_id : widget_id
					});
					
					// Show hide the communities config interface depending on the user option.
					$('#widget-'+widget_id+'-include_community_search').change(function (){
						if( $(this).val() == 0 ) { // Hide the interface.
							$('#community_search_config', '.'+widget_id+':not(div[class$="__i__"])').slideUp();
						} else { // Show the interface.
							$('#community_search_config', '.'+widget_id+':not(div[class$="__i__"])').slideDown();
						}
					});

				}
			});
			
		}
	});
})(csJQ);
</script>
