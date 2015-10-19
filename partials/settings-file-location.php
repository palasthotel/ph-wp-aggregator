<?php
$setting = get_option( 'aggregator_file_location', 'uploads' );
?>
<select name="aggregator_file_location">
	<option value="uploads" <?php echo ( $setting == 'uploads' ? 'selected' : '' );?>>Uploads</option>
	<option value="theme" <?php echo ( $setting == 'theme' ? 'selected' : '' );?>>Theme</option>
</select>