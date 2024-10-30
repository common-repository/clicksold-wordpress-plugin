<?php 
echo $before_widget; 
if ( !empty( $title ) ) { echo $before_title . $title . $after_title; }
?>

<?php
/**
 * Main Widget Display Portion.
 * 
 * NOTE: For the ones that need latitude and longitude we never even get here if all of the data is not present. Our routine in widgets.php checks for this.
 */
?>


<?php	if( $instance['widget_info_type'] == "map-loc" ) { // Map Location - Displays Map with listing location. ?>

	<!-- The map will go here -->
	<div class="cs-listing-details-widget-gmap-container"><div id="cs-listing-widget-gmap" style="height: <?php echo $instance['widget_height']?>px;"></div></div>

	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				
				var lat = '<?php echo $listing_details_json['latitude'] ?>';
				var lng = '<?php echo $listing_details_json['longitude'] ?>';
				
				// do the map magic
				var map = new google.maps.Map($("#cs-listing-widget-gmap").get(0), {
					center : new google.maps.LatLng(lat, lng),
					mapTypeId : google.maps.MapTypeId.ROADMAP,
					zoom : 15,
					mapTypeControl : true,
					zoomControl : true,
					streetViewControl : false
				});
				
				var point = new google.maps.Marker({
					clickable : false,
					map : map,
					position : new google.maps.LatLng(lat, lng)
				});
				
			});
		})(csJQ);
	</script>

<?php	} else if( $instance['widget_info_type'] == "walkscore" ) { ?>

	<!-- walkscore widget has been defered -->

<?php	} else if( $instance['widget_info_type'] == "streetview" ) { ?>

	<!-- The streetview will go here -->
	<div class="cs-listing-details-widget-streetview-container"><div id="cs-listing-widget-streetview" style="height: <?php echo $instance['widget_height']?>px"></div></div>

	<script type="text/javascript">
		(function($){
			$(document).ready(function(){
				
				var lat = '<?php echo $listing_details_json['latitude'] ?>';
				var lng = '<?php echo $listing_details_json['longitude'] ?>';
				
				// Check if steet view is available for this location.
				var latLng = new google.maps.LatLng(lat, lng);
				var sv = new google.maps.StreetViewService();
				var dataLocationPano = undefined;
				sv.getPanoramaByLocation(latLng, 30, function(data, status){
					
					// Assuming that the pano actually exists.
					if(status == google.maps.StreetViewStatus.OK){
						
						var pano = new google.maps.StreetViewPanorama($("#cs-listing-widget-streetview").get(0), {
								linksControl: false,
								panControl: false,
								zoomControlOptions: {
									style: google.maps.ZoomControlStyle.SMALL
								},
								enableCloseButton: false
							});
							
						pano.setPano(data.location.pano);
						pano.setPov({
							heading: 270,
							pitch : 0,
							zoom : 1
						});
						pano.setVisible(true);

					} else { // No pano exists for this location.
						
						/**
						 * Here we have a bit of an issue. Normally for listings that don't have stView we'd just not show the widget but this one has coords it's just that google does not have stView for this location.
						 * In these cases we must remove the widget here, so it does not show up all blank and ugly.
						 */
						$('#cs-listing-widget-streetview').parents('.widget-listing-details-page').hide();
						
					}
				});
				
			});
		})(csJQ);
	</script>

<?php	} else if( $instance['widget_info_type'] == "featured_info" ) { ?>

	<!-- featured info widget has been defered -->

<?php	} ?>






<?php
echo $after_widget;
?>
