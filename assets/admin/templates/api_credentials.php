<div class="wrap">
    
    <h2><?php echo SBWP_PLUGIN_NAME; ?></h2>
    
    <form method="POST" action="options.php">
        
        <?php settings_fields('sbwp-api-credentials'); ?>
        
        <?php do_settings_sections('sbwp-api-credentials'); ?>
        
        <?php submit_button(); ?>
        
    </form>
    
</div>