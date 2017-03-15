<div class="wrap">
    <?php screen_icon(); ?>
    <h2><?php echo __( "Aggregator Settings" ); ?></h2>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'aggregator_settings' );
        do_settings_sections( 'aggregator_settings' );
        ?>
        <?php submit_button(); ?>
    </form>
</div>
