<?php
  /**
  * Make call to the server based on the site domain name
  */
  $newResult = $this->getUUIDFromAPI();
?>

<p>
  <label for="<?php echo $this->get_field_id('embedUUID'); ?>"><?php _e('Embed List:', 'embedUUID'); ?></label>
  <select id="<?php echo $this->get_field_id('embedUUID'); ?>" name="<?php echo $this->get_field_name('embedUUID'); ?>" >
    <?php
      $embedList = json_decode(stripslashes(get_option('Insticator_embedList')), true);
      foreach($embedList as $embedUUID => $embedNAME) {
        ?>
        <option value= "<?php echo $embedUUID;?>" selected="selected" ><?php echo $embedNAME; ?></option>
        <?php
      }
    ?>
  </select>

</p>
