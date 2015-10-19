<?php
$setting = get_option( 'aggregator_file_location', 'theme' );
?>
<select name="aggregator_file_location">
	<option value="theme" <?php echo ( $setting == 'theme' ? 'selected' : '' );?>>Theme</option>
	<option value="uploads" <?php echo ( $setting == 'uploads' ? 'selected' : '' );?>>Uploads</option>
</select>