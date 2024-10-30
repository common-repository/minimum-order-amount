<span align="center">
<hr>
<h3 align="center"><?php _e ('Here are additional plugins that will give you more ad features', ECPM_MOA);?></h3>
<table align="center" cellspacing="0" cellpadding="10" width="534"><tr>
  <td align="center"><a href="http://www.easycpmods.com/plugin-paid-ad-extender/" target="_blank"><img style="opacity:0.8" src="<?php echo esc_url(plugins_url('images/paid-ad-extender.png', __FILE__));?>" border="0" width="267px" title="Paid Ad Extender" alt="Paid Ad Extender"></a>
  <br>
  <?php
  if ( !is_plugin_active('paid-ad-extender/ecpm-pae.php') )
    echo "<div class='moa-not-installed'>". __('Install this plugin to get extra features', ECPM_MOA);
  else
    echo "<div class='moa-installed'>". __('Plugin already installed. Thank you.', ECPM_MOA);
    
  ?>
 
  </td>
  <td align="center"><a href="http://www.easycpmods.com/plugin-paid-ad-features/" target="_blank"><img style="opacity:0.8" src="<?php echo esc_url(plugins_url('images/paid-ad-features.png', __FILE__));?>" border="0" width="267px" title="Paid Ad Features" alt="Paid Ad Features"></a>
  <br>
   <?php 
  
  if ( !is_plugin_active('paid-ad-features/ecpm-paf.php') )
    echo "<div class='moa-not-installed'>". __('Install this plugin to get extra features', ECPM_MOA);
  else
    echo "<div class='moa-installed'>". __('Plugin already installed. Thank you.', ECPM_MOA);

  ?>
  </td>
</tr></table> 
</span> 