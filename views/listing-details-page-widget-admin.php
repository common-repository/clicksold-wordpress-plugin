<div class="widget_listing_quick_search">
  <p>
    <label for="<?php echo $this->get_field_id('title'); ?>">Title</label><br/>
    <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" class="widefat" value="<?php echo $instance['title']; ?>"><br/>

    <label for="<?php echo $this->get_field_id('widget_height'); ?>">Widget Height (in pixels)</label><br/>
    <input type="text" id="<?php echo $this->get_field_id('widget_height'); ?>" name="<?php echo $this->get_field_name('widget_height'); ?>" class="wide" value="<?php echo $instance['widget_height']; ?>"><br/>

    <!-- The Type of additional information that this widget will display -->
    <label for="<?php echo $this->get_field_id('widget_info_type'); ?>">Information Type</label><br/>
    
    <select class="widefat" id="<?php echo $this->get_field_id('widget_info_type'); ?>" name="<?php echo $this->get_field_name('widget_info_type'); ?>">
      <option <?php if($instance['widget_info_type'] == "map-loc")       { echo("selected"); } ?> value="map-loc"      >Map Position</option>
      <!--<option <?php if($instance['widget_info_type'] == "walkscore")     { echo("selected"); } ?> value="walkscore"    >Walkscore</option>-->
      <option <?php if($instance['widget_info_type'] == "streetview")    { echo("selected"); } ?> value="streetview"   >Streetview</option>
      <!--<option <?php if($instance['widget_info_type'] == "featured_info") { echo("selected"); } ?> value="featured_info">Featured Info</option>-->
    </select>

  </p>
</div>
