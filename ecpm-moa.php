<?php
/*
Plugin Name: Minimum Order Amount
Plugin URI: http://www.easycpmods.com
Description: Minimum Order Amount is a lightweight plugin that will not allow users to make purchases smaller then the amount you specify. Classipress theme is required for this plugin.
Author: EasyCPMods
Version: 1.1.0
Author URI: http://www.easycpmods.com
Text Domain: ecpm-moa
*/

define('ECPM_MOA', 'ecpm-moa');
define('ECPM_MOA_NAME', 'Minimum Order Amount');
define('ECPM_MOA_VERSION', '1.1.0');

register_activation_hook( __FILE__, 'ecpm_moa_activate');
register_deactivation_hook( __FILE__, 'ecpm_moa_deactivate');
register_uninstall_hook( __FILE__, 'ecpm_moa_uninstall');

add_action( 'plugins_loaded', 'ecpm_moa_plugins_loaded' );
add_action( 'admin_init', 'ecpm_moa_requires_version' );
add_action( 'admin_menu', 'ecpm_moa_create_menu_set', 11 );
add_action( 'admin_enqueue_scripts', 'ecpm_moa_admin_css' );

if (ecpm_moa_get_settings('alt_hook') == 'on') {
  add_filter( 'appthemes_notices', 'ecpm_moa_total_cost' );
} else {
  add_filter( 'appthemes_form_progress_steps', 'ecpm_moa_total_cost');
}    

function ecpm_moa_get_settings($ret_value){
  $moa_settings = get_option('ecpm_moa_settings');
  return $moa_settings[$ret_value];
} 

function ecpm_moa_requires_version() {
  $allowed_apps = array('classipress');
  
  if ( defined(APP_TD) && !in_array(APP_TD, $allowed_apps ) ) { 
	  $plugin = plugin_basename( __FILE__ );
    $plugin_data = get_plugin_data( __FILE__, false );
		
    if( is_plugin_active($plugin) ) {
			deactivate_plugins( $plugin );
			wp_die( "<strong>".$plugin_data['Name']."</strong> requires a AppThemes Classipress theme to be installed. Your Wordpress installation does not appear to have that installed. The plugin has been deactivated!<br />If this is a mistake, please contact plugin developer!<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );
		}
	}
}

function ecpm_moa_deactivate() {
  wp_clear_scheduled_hook('ecpm_moa_expire_event');
} 


function ecpm_moa_activate() {
  $ecpm_moa_settings = get_option('ecpm_moa_settings');
  if ( empty($ecpm_moa_settings) ) {
    $ecpm_moa_settings = array(
      'min_amount' => '1',
      'hide_warning' => '',
      'warning_text' => 'Minimum amount for additional features is [minamt]',
      'warning_class' => 'warning',
      'hide_error' => '',
      'error_text' => 'Minimum amount for your order is [minamt]. Please select additional features.',
      'alt_hook' => '',
    );
    update_option( 'ecpm_moa_settings', $ecpm_moa_settings );
  }
}

function ecpm_moa_uninstall() {                                   
  delete_option( 'ecpm_moa_settings' );
}

function ecpm_moa_admin_css( $hook ) {
  if( is_admin() ) { 
    wp_enqueue_style('ecpm_moa_style', plugins_url('ecpm-moa.css', __FILE__), array(), null);
  }
} 

function ecpm_moa_plugins_loaded() {
  $dir = dirname(plugin_basename(__FILE__)).DIRECTORY_SEPARATOR.'languages'.DIRECTORY_SEPARATOR;
	load_plugin_textdomain(ECPM_MOA, false, $dir);
}

function ecpm_moa_total_cost( $steps ) {

  $checkout = appthemes_get_checkout();
  if (empty($checkout))
    return;
    
  $step_id = $checkout->get_current_step();
  
  $ecpm_moa_settings = get_option('ecpm_moa_settings');
  $min_amount = $ecpm_moa_settings['min_amount'];
  
  $checkout = APP_Current_Checkout::get_checkout();
  $posted_fields = $checkout->get_data( 'posted_fields' );
  
  if ( isset($posted_fields['cp_sys_total_ad_cost']) )
    $total_cost = $posted_fields['cp_sys_total_ad_cost'];
  else
    $total_cost = '';  
    
  $show_notice = false;  

  switch ($step_id) {
  case 'listing-details':
    if ( $ecpm_moa_settings['hide_warning'] != 'on' ) {
      $err_notices = appthemes_get_notices('error');
      if (empty($err_notices)) {
        $show_notice = true;
      } else {
        foreach ($err_notices as $err_notice) {
          if ( array_key_exists( 'moa-error-amount', $err_notice ) ) {
            $show_notice = false;
          }
        }
      }      
    }
    
    if ($show_notice && $total_cost < $min_amount ) {
      $warning_text = str_replace('[minamt]', appthemes_get_price($min_amount), $ecpm_moa_settings['warning_text']);
      appthemes_add_notice( 'moa-warning-amount', $warning_text, $ecpm_moa_settings['warning_class'] );
    }  
    break;

  case 'listing-preview':
    if ( $total_cost > 0 && $total_cost < $min_amount ) {
      $redirect_url = appthemes_get_step_url( appthemes_get_previous_step() );
      
     ?>
        <script language="JavaScript">
        window.location.replace('<?php echo $redirect_url;?>');
        //self.location = '<?php echo $redirect_url;?>';
        </script>
      <?php
  
      if ( $ecpm_moa_settings['hide_error'] != 'on' ) {
        $error_text = str_replace('[minamt]', appthemes_get_price($min_amount), $ecpm_moa_settings['error_text']);
        appthemes_add_notice( 'moa-error-amount', $error_text, 'error' );
      }  
      
      wp_redirect( $redirect_url );
  	  exit();
    }
    break;
  }
  
  return $steps;
}

function ecpm_moa_create_menu_set() {
  if ( is_plugin_active('easycpmods-toolbox/ecpm-toolbox.php') ) {
    $ecpm_etb_settings = get_option('ecpm_etb_settings');
    if ($ecpm_etb_settings['group_settings'] == 'on') {
      add_submenu_page( 'ecpm-menu', ECPM_MOA_NAME, ECPM_MOA_NAME, 'manage_options', 'ecpm_moa_settings_page', 'ecpm_moa_settings_page_callback' );
      return;
    }
  }
  add_options_page(ECPM_MOA_NAME, ECPM_MOA_NAME, 'manage_options', 'ecpm_moa_settings_page', 'ecpm_moa_settings_page_callback');
}    
  
function ecpm_moa_settings_page_callback() {
  global $cp_options;
  
  $ecpm_moa_settings = get_option('ecpm_moa_settings');
  
  if ( current_user_can( 'manage_options' ) ) {
    
    if ( isset( $_POST['ecpm_moa_submit'] ) ) {
    
      $avail_classes = array('success', 'error', 'warning', 'checkout-error');

      if ( isset($_POST[ 'ecpm_moa_min_amount' ]) && is_numeric (intval( $_POST[ 'ecpm_moa_min_amount' ] ) ) )
        $ecpm_moa_settings['min_amount'] = sanitize_text_field($_POST[ 'ecpm_moa_min_amount' ]);
      else
        $ecpm_moa_settings['min_amount'] = '';
        
      if ( isset($_POST[ 'ecpm_moa_hide_warning' ]) && $_POST[ 'ecpm_moa_hide_warning' ] == 'on' )
        $ecpm_moa_settings['hide_warning'] = sanitize_text_field( $_POST[ 'ecpm_moa_hide_warning' ] );
      else
        $ecpm_moa_settings['hide_warning'] = '';    
        
      if ( isset($_POST[ 'ecpm_moa_warning_text' ]) )
        $ecpm_moa_settings['warning_text'] = sanitize_text_field( $_POST[ 'ecpm_moa_warning_text' ] );
      else
        $ecpm_moa_settings['warning_text'] = '';
        
      if ( isset($_POST[ 'ecpm_moa_warning_class' ]) && in_array($_POST[ 'ecpm_moa_warning_class' ], $avail_classes) )
        $ecpm_moa_settings['warning_class'] = sanitize_text_field( $_POST[ 'ecpm_moa_warning_class' ] );
      else
        $ecpm_moa_settings['warning_class'] = 'success';  
          
      if ( isset($_POST[ 'ecpm_moa_hide_error' ]) && $_POST[ 'ecpm_moa_hide_error' ] == 'on' )
        $ecpm_moa_settings['hide_error'] = sanitize_text_field( $_POST[ 'ecpm_moa_hide_error' ] );
      else
        $ecpm_moa_settings['hide_error'] = '';  
        
      if ( isset($_POST[ 'ecpm_moa_error_text' ]) )
        $ecpm_moa_settings['error_text'] = sanitize_text_field( $_POST[ 'ecpm_moa_error_text' ] );
      else
        $ecpm_moa_settings['error_text'] = '';
        
      if ( isset($_POST[ 'ecpm_moa_alt_hook' ]) && $_POST[ 'ecpm_moa_alt_hook' ] == 'on' )
        $ecpm_moa_settings['alt_hook'] = sanitize_text_field( $_POST[ 'ecpm_moa_alt_hook' ] );
      else
        $ecpm_moa_settings['alt_hook'] = '';    
      
         
      update_option( 'ecpm_moa_settings', $ecpm_moa_settings );
      
      echo scb_admin_notice( __( 'Settings saved.', APP_TD ), 'updated' );
      
  	}
  }
  
  $form_url = remove_query_arg(array('moa_delid', 'moa_addid'));
  ?>
  
		<div id="moasetting">
		  <div class="wrap">
			<h1><?php echo ECPM_MOA_NAME; ?></h1>
      <?php
        echo "<i>Plugin version: <u>".ECPM_MOA_VERSION."</u>";
        echo "<br>Plugin language file: <u>ecpm-moa-".get_locale().".mo</u></i>";
        ?>
  			<hr>
        <div id='moa-container-left' style='float: left; margin-right: 285px;'>
        <form id='moasettingform' method="post" action="<?php echo $form_url;?>">
  				<table width="100%" cellspacing="0" cellpadding="10" border="0">
            <tr>
    					<th align="left" valign="top">
    						<label for="ecpm_moa_min_amount"><?php echo _e('Minimum amount', ECPM_MOA); ?></label>
    					</th>
    					<td valign="top">
                <Input type='text' size='2' id='ecpm_moa_min_amount' Name='ecpm_moa_min_amount' value='<?php echo esc_html($ecpm_moa_settings['min_amount']);?>'>
                <span class="description"><?php _e( 'Input minimum amount for orders' , ECPM_MOA ); ?></span>
    					</td>
    				</tr> 
            
            <tr><td colspan="2"><hr></td></tr>
            <tr>
    					<th align="left" valign="top">
    						<label for="ecpm_moa_hide_warning"><?php echo _e('Hide warning message', ECPM_MOA); ?></label>
    					</th>
    					<td valign="top">
                <Input type='checkbox' Name='ecpm_moa_hide_warning' id="ecpm_moa_hide_warning" <?php echo ( $ecpm_moa_settings['hide_warning'] == 'on' ? 'checked':'') ;?> >
                <span class="description"><?php _e( 'Would you like to hide warning message?' , ECPM_MOA ); ?></span>
    					</td>
    				</tr>
            
            <tr>
    					<th align="left" valign="top">
    						<label for="ecpm_moa_warning_text"><?php echo _e('Warning text', ECPM_MOA); ?></label>
    					</th>
    					<td valign="top">
                <Input type='text' size='70' id='ecpm_moa_warning_text' Name='ecpm_moa_warning_text' value='<?php echo esc_html($ecpm_moa_settings['warning_text']);?>'>
                <br><span class="description"><?php _e( 'Warning text will be shown to the user on ad details page before entering ad data.' , ECPM_MOA ); ?>
                <br><?php _e( 'Use [minamt] to display minimum amount specified.' , ECPM_MOA ); ?></span>
    					</td>
    				</tr>
            
            <tr>
    					<th align="left" valign="top">
    						<label for="ecpm_moa_warning_class"><?php echo _e('Warning style', ECPM_MOA); ?></label>
    					</th>
              <td align="left">
                <select id="ecpm_moa_warning_class" name="ecpm_moa_warning_class" >
	 							  <option value="success" <?php echo ($ecpm_moa_settings['warning_class'] == 'success' ? 'selected':'') ;?>><?php echo _e('Success', ECPM_MOA); ?></option>
                  <option value="error" <?php echo ($ecpm_moa_settings['warning_class'] == 'error' ? 'selected':'') ;?>><?php echo _e('Error', ECPM_MOA); ?></option>
                  <option value="warning" <?php echo ($ecpm_moa_settings['warning_class'] == 'warning' ? 'selected':'') ;?>><?php echo _e('Warning', ECPM_MOA); ?></option>
                </select>
              </td>
            </tr>  
            
            <tr><td colspan="2"><hr></td></tr>
            <tr>
    					<th align="left" valign="top">
    						<label for="ecpm_moa_hide_error"><?php echo _e('Hide error message', ECPM_MOA); ?></label>
    					</th>
    					<td valign="top">
                <Input type='checkbox' Name='ecpm_moa_hide_error' id="ecpm_moa_hide_error" <?php echo ( $ecpm_moa_settings['hide_error'] == 'on' ? 'checked':'') ;?> >
                <span class="description"><?php _e( 'Would you like to hide error message? (not recommended)' , ECPM_MOA ); ?></span>
    					</td>
    				</tr>
            
            <tr>
    					<th align="left" valign="top">
    						<label for="ecpm_moa_error_text"><?php echo _e('Error text', ECPM_MOA); ?></label>
    					</th>
    					<td valign="top">
                <Input type='text' size='70' id='ecpm_moa_error_text' Name='ecpm_moa_error_text' value='<?php echo esc_html($ecpm_moa_settings['error_text']);?>'>
                <br><span class="description"><?php _e( 'Error text will be shown to the user if the amount does not exceed minimum amount specified.' , ECPM_MOA ); ?>
                <br><?php _e( 'Use [minamt] to display minimum amount specified.' , ECPM_MOA ); ?></span>
    					</td>
    				</tr>
            
            <tr><td colspan="2"><hr></td></tr>
            <tr>
    					<th align="left" valign="top">
    						<label for="ecpm_moa_alt_hook"><?php echo _e('No form progress', ECPM_MOA); ?></label>
    					</th>
    					<td valign="top">
                <Input type='checkbox' Name='ecpm_moa_alt_hook' id="ecpm_moa_alt_hook" <?php echo ( $ecpm_moa_settings['alt_hook'] == 'on' ? 'checked':'') ;?> >
                <span class="description"><?php _e( 'If your child theme is not showing order progess bar or you are not getting messages from this plugin, select this option.' , ECPM_MOA ); ?></span>
    					</td>
    				</tr>
            
          </table>
          <hr>
          <p>  
  				<input type="submit" id="ecpm_moa_submit" name="ecpm_moa_submit" class="button-primary" value="<?php _e('Save settings', ECPM_MOA); ?>" />
  				</p>
  			</form>
        
        <?php include_once(  plugin_dir_path(__FILE__)."/paid_plugin_inc.php" );?>
        
        </div>
        
        <div id='moa-container-right' class='nocloud' style='border: 1px solid #e5e5e5; float: right; margin-left: -275px; padding: 0em 1.5em 1em; background-color: #fff; box-shadow: 10px 10px 5px #888888; display: inline-block; width: 234px;'>
          <h3>Thank you for using</h3>
          <h2><?php echo ECPM_MOA_NAME;?></h2>
          <hr>
          <?php include_once(  plugin_dir_path(__FILE__)."/plugin_inc_local.php" );?>
        </div>
		</div>
	</div>
<?php
}
?>