<?php
if ( ! defined( 'ABSPATH' ) ) {
    die( '<h3>Direct access to this file do not allow!</h3>' );
}


class us_override_download_template {

    public function __construct() {
		add_action( 'woocommerce_locate_template', array(&$this,'us_override_default_download_template'), 10, 3 );
}

	public function us_override_default_download_template( $template, $template_name, $template_path ) {

      global $woocommerce,$post,$product;

	   
	  
	   $goahead=1;



	 if (isset($_SERVER['HTTP_USER_AGENT'])){

         $agent = $_SERVER['HTTP_USER_AGENT'];

      }

	

	if (preg_match('/(?i)msie [5-8]/', $agent))  {

         $goahead=0;

     }

	
     if ( ($goahead == 1) && strstr($template, 'downloads.php')) {

       $template = UL_PLUGIN_DIR . '/woocommerce/myaccount/downloads.php';

      }
     return $template;
    }

 }
new us_override_download_template();
?>