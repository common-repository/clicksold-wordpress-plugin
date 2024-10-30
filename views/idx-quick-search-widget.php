<?php echo $before_widget; ?>

<?php

	// Some defaults for older widget versions -- note as of 2016-01-19 EZ these go into the widget() function call.
	if( !isset( $wide_vers ) ) { $wide_vers = false; }
	if( !isset( $force_control_type ) ) { $force_control_type = 0; }
	if( !isset( $include_community_search ) ) { $include_community_search = 0; }
	if( !isset( $city_list ) ) { $city_list = array(); }
	if( !isset( $community_url ) ) { $community_url = ''; }

	// Compute the extra styles that we need for the wide version.
	$extra_styles = '';
	
	if( $wide_vers ) { 
		$extra_styles .= ' csIDXQuickSearchWidget-wide';

		if( $compact_vers ) {
			$extra_styles .= ' csIDXQuickSearchWidget-wide-compact';
		} else {
			$extra_styles .= ' csIDXQuickSearchWidget-wide-non-compact';
		}
	} else {
		$extra_styles .= ' csIDXQuickSearchWidget-narrow';
	}
?>

<div id="<?php echo $this->get_field_id("") ?>container" class="<?php echo $this->widget_options['classname'] ?>-container widget cs-widget" style="<?php echo $widget_styles ?>">
  <div class="csIDXQuickSearchWidgetContainer" class="widgetContent">

<?php
		//
		// Optionally add the City / Community search interface.
		//
?>
<?php	if( $include_community_search ) { // If we are to include the community search interface. ?>

    <div class="csIDXQuickSearchWidget_communities_interface <?php echo $extra_styles ?>">
	  <form id="<?php echo $this->get_field_id("") ?>_Form">
	    <div class="comm-search-option-container">
	      <div class="cs-label-container">
		    <label for="cities">City: </label>
		  </div>
		  <div class="cs-input-container">
		    <select id="cities" style="width:100%">
<?php 
	  foreach($city_list as $city) { ?>
              <option value="<?php echo $city ?>"><?php echo $city ?></option>
<?php } ?>
		    </select>
		  </div>
		</div>
		
	    <div class="comm-search-option-container">
		  <div class="cs-label-container">
		    <label for="Neighborhood">Neighborhood</label>
		  </div>
		  <div class="cs-input-container">
		    <select id="neighbourhoods" style="width:100%"></select>
		  </div>
        </div>
      </form>
    </div>

<?php	} ?>


    <div class="csIDXQuickSearchWidget <?php echo $extra_styles ?>">
	  Please Wait...
    </div>
  </div>
</div>
<script type="text/javascript">
(function($){
	$(document).ready(function(){
		$("#<?php echo $this->get_field_id("") ?>container").IDXQuickSearchWidget({
			ajaxTarget : "<?php echo plugins_url( 'CS_ajax_request.php', dirname(__FILE__) ) ?>",
			plugin : true,
			idxSearchUrl : "<?php echo $idx_url ?>",
			commSearchUrl : "<?php echo $community_url ?>",
			include_community_search : <?php echo $include_community_search ?>, // Has to be a number.
			csInitPropType : <?php echo $default_prop_type ?>,
			compactVers : <?php echo $compact_vers ?>,
<?php if(!empty($regions)) { ?>
			regions : {
<?php   $index = 0;
        foreach($regions as $city => $neigh) {
          if($index == count($regions) - 1) { ?>
				"<?php echo $city ?>" : ["<?php echo implode("\", \"", $neigh) ?>"]
<?php     } else { ?>
				"<?php echo $city ?>" : ["<?php echo implode("\", \"", $neigh) ?>"],
<?php     } 
          $index++; ?>
<?php   } ?>
			},
<?php } ?>
			search_goes_to : '<?php echo $search_goes_to ?>',
			force_control_type : <?php echo $force_control_type ?> // Has to be a number.

		});
	});
})(csJQ);
</script>
<?php echo $after_widget; ?>
