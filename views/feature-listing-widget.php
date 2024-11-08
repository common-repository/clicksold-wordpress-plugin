<?php 
echo $before_widget; 
if ( !empty( $title ) ) { echo $before_title . $title . $after_title; }
?>
<div id="<?php echo $this->get_field_id("") ?>container" class="<?php echo $this->widget_options['classname'] ?>-container widget cs-widget">
  <div class="csFeatureListingModule" class="widgetContent">
    <div class="csFeatureListingWidget">
	  Please Wait...
    </div>
  </div>
</div>
<script>
(function($){
	$(document).ready(function(){
		var w_width = $("#<?php echo $this->get_field_id("") ?>container").children(".csFeatureListingModule").width();
		$("#<?php echo $this->get_field_id("") ?>container").children(".csFeatureListingModule").css("min-height", (w_width * 1.036) + "px");
	
		$("#<?php echo $this->get_field_id("") ?>container").FeatureListingWidget({
			plugin : true,
			ajaxTarget : "<?php echo plugins_url( 'CS_ajax_request.php', dirname(__FILE__) ) ?>",
			listingSection : "<?php echo $instance['listing_section']; ?>",
<?php if(!empty($instance['user_defined_listings'])) { ?>
			userDefinedListings : [
<?php   foreach($instance['user_defined_listings'] as $index => $mlsNum) {
          if($index == count($instance['user_defined_listings']) - 1) { ?>
				"<?php echo $mlsNum ?>"
<?php     } else { ?>
				"<?php echo $mlsNum ?>",
<?php     } ?>
<?php   } ?>
			],
<?php } ?>
			listingType : <?php echo $instance['listing_type']; ?>,
			listingStatus : "<?php echo $instance['listing_status']; ?>",
			listingUrl : "<?php echo $listings_url; ?>",
			listingExclUrl : "<?php echo $listings_excl_url; ?>",
			cycleFrequency : "<?php echo $instance['freq']; ?>",
			numDisp : <?php echo $instance['numDisp']; ?>,
			vertical : <?php echo $instance['vertical']; ?>,
			//fade : <?php echo $instance['fade']; ?>, // DOES NOT WORK CORRECTLY WHEN FADING.
			widget_type : "<?php echo $instance['widget_type']; ?>",
			listing_set_shift : <?php echo $instance['listing_set_shift']; ?>,
			minCntWidth : "<?php echo $instance['minCntWidth']; ?>",
			maxCntWidth : "<?php echo $instance['maxCntWidth']; ?>",
			minCntHeight : "<?php echo $instance['minCntHeight']; ?>",
			maxCntHeight : "<?php echo $instance['maxCntHeight']; ?>",
			listingPhotoContainer_style : "<?php echo $instance['listingPhotoContainer_style']; ?>",
			break_points_for_slick_slider : "<?php echo $instance['break_points_for_slick_slider']; ?>",
			arrows_on_slick_slider : <?php echo $instance['arrows_on_slick_slider']; ?>
		});
	});
})(csJQ);
</script>
<?php echo $after_widget; ?>
