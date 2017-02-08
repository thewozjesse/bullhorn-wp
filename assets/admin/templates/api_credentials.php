<div class="wrap">
    
    <h2><?php echo SBWP_PLUGIN_NAME; ?></h2>
    
    <form method="POST" action="options.php">
        
        <?php settings_fields('sbwp-api-credentials'); ?>
        
        <?php do_settings_sections('sbwp-api-credentials'); ?>
        
        <table class="form-table">
            <tr valign="top">
            <th scope="row">Bullhorn Client ID</th>
            <td><input type="text" name="sbwp_bullhorn_client_id" value="<?php echo esc_attr( get_option('sbwp_bullhorn_client_id') ); ?>" /></td>
            </tr>
             
            <tr valign="top">
            <th scope="row">Bullhorn Client Secret</th>
            <td><input type="text" name="sbwp_bullhorn_client_secret" value="<?php echo esc_attr( get_option('sbwp_bullhorn_client_secret') ); ?>" /></td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
        
    </form>
    
</div>