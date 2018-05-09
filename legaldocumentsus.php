<?php 
 /*
Plugin Name: Legal Documents US
Plugin URI: http://www.bytestechnolab.com/legaldocumentsus
description: a plugin to create awesomeness and spread joy
Version: 1.3.71
Author: BytesTeam
Author URI: http://bytestechnolab.com
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    die( '<h3>Direct access to this file do not allow!</h3>' );
}

if ( ! function_exists( 'get_plugin_data' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

$plugin = get_plugin_data( __FILE__ );

define( 'UL_VERSION', $plugin['Version'] );
define( 'UL_FILE', __FILE__ );
define ("UL_PLUGIN_URL",plugin_dir_url( __FILE__ ));
define( 'UL_PLUGIN_DIR', untrailingslashit( plugin_dir_path( UL_FILE ) ) );

require_once UL_PLUGIN_DIR . '/config.php';

require_once UL_PLUGIN_DIR .'/TGMPA-TGM-Plugin-Activation/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'abc_register_required_plugins' );

function abc_register_required_plugins() {
	
	$plugins = array(
		// This is an example of how to include a plugin from the WordPress Plugin Repository.
		array(
			'name'      => 'Woocommerce',
			'slug'      => 'woocommerce',
			'required'  => true,
			'force_activation'   => true,
		),
		array(
			'name'               => 'Gravity Forms', // The plugin name.
			'slug'               => 'gravityforms', // The plugin slug (typically the folder name).
			'source'             => UL_PLUGIN_DIR . '/TGMPA-TGM-Plugin-Activation/plugins/gravityforms.zip', // The plugin source.
			'required'           => true, // If false, the plugin is only 'recommended' instead of required.
			'version'            => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher. If the plugin version is higher than the plugin version installed, the user will be notified to update the plugin.
			'force_activation'   => true, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
			'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
			'external_url'       => '', // If set, overrides default API URL and points to an external URL.
			'is_callable'        => '', // If set, this callable will be be checked for availability to determine if a plugin is active.
		),
		array(
		'name'      => 'WooCommerce - Gravity Forms Product Add-Ons',
		'slug'      => 'woocommerce-gravityforms-product-addons',
		'source'    => 'https://github.com/wp-premium/woocommerce-gravityforms-product-addons/archive/master.zip',
		'force_activation'   => true,
		),
	);

	$config = array(
		'id'           => 'legaldocumentsus',                 // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug'  => 'plugins.php',            // Parent menu slug.
		'capability'   => 'manage_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,                   // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.

	);
	tgmpa( $plugins, $config );
}
### end 


/**
*  Activation Class 
**/
if ( ! class_exists( 'WC_CPInstallCheck' ) ) {
  class WC_CPInstallCheck {
		static function install() {
	
			
			$BasePath = ABSPATH . '/s3_data/';
			if (!is_dir($BasePath)) {
				$error_message = __('This plugin requires Amazon S3 server Files Data in root', 'woocommerce');
				die($error_message);
			add_action( 'admin_notices', array('WC_CPInstallCheck', 'my_plugin_activation_notice') );
			}	
			
			
		}
		
		
		public function  my_plugin_activation_notice(){?>
		 <div class="updated notice is-dismissible">
				<p>This plugin requires Amazon S3 server Files Data at your webroot! .</p>
			</div>

		<?php } 
	}
}

register_activation_hook( __FILE__, array('WC_CPInstallCheck', 'install') );

add_action('plugins_loaded', 'my_late_loader',99);
function my_late_loader()
{
	
	
  if ( class_exists( 'WooCommerce' ) ) 
  {

 	 require 'classes/class_override_woocommerce_download_tamplate.php';
   	// define the woocommerce_checkout_process callback 
	/* Add custom menu item and endpoint to WooCommerce My-Account page */

	/*function my_custom_endpoints() {
		add_rewrite_endpoint( 's3_data', EP_ROOT | EP_PAGES );
	}

	add_action( 'init', 'my_custom_endpoints' );

	function my_custom_query_vars( $vars ) {
		$vars[] = 's3_data';

		return $vars;
	}

	add_filter( 'query_vars', 'my_custom_query_vars', 0 );

	function my_custom_flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	add_action( 'after_switch_theme', 'my_custom_flush_rewrite_rules' );

	function my_custom_my_account_menu_items( $items ) {
		$items = array(
			'dashboard'         => __( 'Dashboard', 'woocommerce' ),
			'orders'            => __( 'Orders', 'woocommerce' ),
			'downloads'         => __( 'Downloads', 'woocommerce' ),
			'edit-address'      => __( 'Addresses', 'woocommerce' ),
			//'payment-methods' => __( 'Payment Methods', 'woocommerce' ),
			'edit-account'      => __( 'Edit Account', 'woocommerce' ),
			's3_data'       	=> 'S3 Downloads files',
			'customer-logout'   => __( 'Logout', 'woocommerce' ),
		);

		return $items;
	}

	add_filter( 'woocommerce_account_menu_items', 'my_custom_my_account_menu_items' );

	function my_custom_endpoint_content() {
		include 'woocommerce/myaccount/s3_data.php'; 
	}

	add_action( 'woocommerce_account_s3_data_endpoint', 'my_custom_endpoint_content' );*/


  } 
  if(class_exists('WC_GFPA_Admin_Controller'))
  {
	 
	 
	require_once 'classes/class_override_WC_GFPA_Admin_Controller.php';
	
	remove_filter( 'woocommerce_order_item_get_formatted_meta_data', array('WC_GFPA_Order','on_get_woocommerce_order_item_get_formatted_meta_data'), 10, 2 );
		

	function  get_order_items($order_id,$product_id,$order_product_ids){
	  if(isset($order_product_ids[$order_id][$product_id])){
		 $groupmeta = array();
		 $groupmeta[] = $order_product_ids[$order_id][$product_id][0]['bundled_by_marital_status'];
		 $groupmeta[] = $order_product_ids[$order_id][$product_id][0]['bundled_ID'];
		array_shift($order_product_ids[$order_id][$product_id]);
		return [$groupmeta,$order_product_ids];
	  }
	  return ['',$order_product_ids];
	}
	function  get_download_details($order_id,$order_list){
	   if(isset($order_list[$order_id])){ //return $order_list;
	   }
	   $order = wc_get_order( $order_id);
			$order_items    = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
			$gravity_data= array();
			$a = 0;
			 foreach ( $order_items as $item_id => $item ) {
			  $a++;
			 $product_id_item = wc_get_order_item_meta($item_id,'_product_id',true);
			 $is_bundleProduct = get_post_meta($product_id_item,'_yith_wcpb_bundle_data',true);
			 if($is_bundleProduct){
			  $_bundled_by_marital_status = wc_get_order_item_meta($item_id,'Are you?*',true); 
			   foreach($is_bundleProduct as $bunduleIds){
				$bunduleProductIds[$bunduleIds['product_id'] ]['bundled_by_marital_status'] = $_bundled_by_marital_status;
				$bunduleProductIds[$bunduleIds['product_id'] ]['bundled_ID'] = $product_id_item;
				$bunduleProductIds[$bunduleIds['product_id'] ]['product_id'] = $product_id_item;
			   }
			}else{
				if(!empty($bunduleProductIds)){
				
				if(isset($bunduleProductIds[$product_id_item ])){
				  $order_list[$order_id][$product_id_item][0]['bundled_by_marital_status'] = $bunduleProductIds[$product_id_item]['bundled_by_marital_status'];
				  $order_list[$order_id][$product_id_item][0]['bundled_ID'] = $bunduleProductIds[$product_id_item]['bundled_ID'];
				  unset($bunduleProductIds[$product_id_item ]);
				}else{
				  
				  $order_list[$order_id][$product_id_item][0]['bundled_by_marital_status'] = $_bundled_by_marital_status;
				  $order_list[$order_id][$product_id_item][0]['bundled_ID'] = $product_id_item;
				}
			  }else{
				$_bundled_by_marital_status = wc_get_order_item_meta($item_id,'Are you?*',true);  
				$order_list[$order_id][$product_id_item][0]['bundled_by_marital_status'] = $_bundled_by_marital_status;
			  


			  }   
			}
		}
		return $order_list;
	}

	
	function get_rules_by_ID($product_idCustom,$orderID,$bundled_ID = null){
	  $outarray =  array();
		$product_MainID =   $product_idCustom;
		  if($bundled_ID != null)
			{
				$product_idCustom = $bundled_ID;
			}
	   $gravity_rules_controls  = get_post_meta( $product_idCustom,'_gravity_form_rules',true);
	   $order = wc_get_order($orderID);
		 $order_items = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
		 $userSelectedName = array();

	   if(isset($order_items) && is_array($order_items)){
			  foreach ( $order_items as $item_id => $item ) {
				  $productItem_id = wc_get_order_item_meta($item_id,'_product_id',true);
				  foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) 
					{ 
						if($product_idCustom  == $productItem_id )
						{
						  $userSelectedName[$meta->key] = $meta->value;
						  $_gravity_forms_history = wc_get_order_item_meta($item_id,'_gravity_forms_history',true);
						}
					}       
				  }
		}

		if(!empty($gravity_rules_controls))
		{
				   
			$sortedcontroler_priority = array_orderby(unserialize($gravity_rules_controls), 'controler_priority', SORT_ASC, 'controler_priority', SORT_DESC);
			$adminCreateRules = array();  
			$totalRules = 0;
			foreach($sortedcontroler_priority as $gravity_rules)
			{
			   
			  $selected_fields_rules =    json_decode($gravity_rules['fields'],true);
			  if(!empty($selected_fields_rules)){
				  foreach($selected_fields_rules as $k=>$f){
					if($f[1] == '**')
					{
					  $removepipe = explode("|",$_gravity_forms_history['_gravity_form_lead'][$selected_fields_rules[$k][2]]);
					  $selected_fields_rules[$k][1] = current($removepipe);
					  
					}
				  }
			  }
			 $matches_rules =    $gravity_rules['control_number'];
			if(!empty($_gravity_forms_history['_gravity_form_lead'])){
				$mapped_fields  = array_diff_assoc_recursive($selected_fields_rules,$_gravity_forms_history['_gravity_form_lead']);
				$mapped_fieldsnew  = explode("_",$mapped_fields);
				if(!empty($gravity_rules['generate_controllerfilestext'])){
					foreach($gravity_rules['generate_controllerfilestext'] as $generate_controllerfilestext)
					{
						$system_generateexp =  explode("_",$generate_controllerfilestext['system_generate']);
				 
						  foreach($generate_controllerfilestext['usermodify'] as $key=>$usermodifyval)
						  {
							$usermodify[$key] =  explode("-",$usermodifyval);
									
						  }
				  
				  
						foreach($system_generateexp as $key=>$checkdata)
						{
						  if($checkdata == "**")
						  {
							$system_generateexp[$key] = $mapped_fieldsnew[$key];
						   
							foreach($usermodify as $keyinner=>$val)
							{
							  if($val[$key] == "**")
							  {
								$usermodify[$keyinner][$key] = $mapped_fieldsnew[$key];
							  }
							}
						  }
						}
					
						$adminCreateRules[$totalRules][implode("_",$system_generateexp)] = implode("-",$usermodify[intval($product_MainID)]);
					}
				}
			}  
			//  if($gravity_rules['gfuser_settings'] == 'state_specific' || $gravity_rules['gfuser_settings'] == 'traverse')
			  {
				$control_number = array();
				$control_number  = explode("_",$gravity_rules['control_number']);
				$endwithSku = "_".end($control_number);
			  }
			  
			   $mapped_fieldsGet[] = $mapped_fields .$endwithSku;
			   if(isset($matches_rules) && @preg_match( "/$mapped_fields*/s",$matches_rules )){
				 $mapped_fieldsGet[] = $matches_rules;
			   }else{
				 $userSelectFile[] = $mapped_fields;
			   }
			   $totalRules++;
			}
			$countadminKey = 0;
		
			foreach($adminCreateRules as $mainKey)
			{
			  
			  foreach($mainKey as $key => $val)
			  {
				if(array_search($key, $mapped_fieldsGet) === false)
				{
				  unset($adminCreateRules[$countadminKey][$key]);
				}else{
				  $fileName[$countadminKey] = $val;
				}
				
				
			  }
			  $countadminKey++;
			}
		  $outarray['filename'] = $fileName;
		  $outarray['userSelectedName'] = $userSelectedName;

	   } 
	return $outarray;
	die();
	}

    function array_orderby()
	{
		$args = func_get_args();
		$data = array_shift($args);
		foreach ($args as $n => $field) {
			if (is_string($field)) {
				$tmp = array();
				foreach ($data as $key => $row)
					$tmp[$key] = $row[$field];
				$args[$n] = $tmp;
				}
		}
		$args[] = &$data;
		call_user_func_array('array_multisort', $args);
		return array_pop($args);
	}
   function array_diff_assoc_recursive($selected_fields_rules,$gravity_forms_history){
	$gravity_forms_history = array_filter($gravity_forms_history, function($value) { return $value !== ''; });
   
	$mpped_fields = array();
	array_map(function ($id,$sf ) use (&$mapped_fields,$gravity_forms_history){
		foreach($gravity_forms_history as $gr_id => $gr_value){
			if($sf[2]==$gr_id){
				list($gr_valueName) =  explode("|",$gr_value);
				$mapped_fields[] = $gr_valueName;
				break;
			}
		}
	   
	},range(1, count($selected_fields_rules)), $selected_fields_rules);
	
	return implode("_",$mapped_fields);
	}

	function getFileURLByName($fileArray){
		
		$BasePath = ABSPATH . '/s3_data/';
		//$BasePath = "/home/uslegalwww/usl_staging/staging/s3_data/"; 
		if (!is_dir($BasePath)) {
			return false;
		}	
		//print_r($BasePath);
		include ($BasePath . 'config.php'); //HELPS US MAKE THE PLATFORM PORTABLE


		//*********************************************************//
		//THIS FILE KEEPS THE BUCKET AND THE FLATBASE DB UP-TO-DATE
		//*********************************************************//
		include( S3DATA . '_loader.php'); //BRINGS IN OUR COMPOSER LIBRARIES JUST FOR THIS SYSTEM

		//  Your AWS secret key and access key
		$secretKey = SECRETKEY;
		$awsAccessKey = AWSACCESSKEY;
		$bucket = BUCKET;
	 
	  $filebys3 = array();
	  $FilesArray = array();
	  if($fileArray)
		{
		  foreach ($fileArray as $key => $value) 
		  {
			$originalSKU = $value;
			$completedSample = $value.'-SP';
			$form_state = substr($originalSKU, 0, 2);
			$dbpath = $BasePath.'_storage/'.$form_state.'/';
		//echo "<br>". $dbpath;
			$conoDoesExist = $dbpath.''.$originalSKU;
			$sampleDoesExist = $dbpath.''.$completedSample;
			//echo "<br>";
			//echo $BasePath.'_storage/'.$form_state;

			$storage = new Flatbase\Storage\Filesystem($BasePath.'_storage/'.$form_state);
			$flatbase = new Flatbase\Flatbase($storage);

			//echo "hii";
			$currentSKU = $form_state.'/'.$originalSKU; //THIS IS YOUR MASTER VARIABLE
			
			if(file_exists($conoDoesExist))
			{
				$FilesArray[$key]['fetchControlNo'] =   fetchControlNo($conoDoesExist, $flatbase, $originalSKU, $secretKey, $awsAccessKey, $bucket);
			
			}

			if(file_exists($sampleDoesExist)) $FilesArray[$key]['fetchSample'] = fetchSample($sampleDoesExist, $flatbase, $completedSample, $secretKey, $awsAccessKey, $bucket);
		  }
	  }
	  
	  /*echo "<pre>";
	  print_r($FilesArray);*/


	  return $FilesArray;
	}
	function el_crypto_hmacSHA1($key, $data, $blocksize = 64)
	{
		if (strlen($key) > $blocksize) $key = pack('H*', sha1($key));
		$key = str_pad($key, $blocksize, chr(0x00));
		$ipad = str_repeat(chr(0x36), $blocksize);
		$opad = str_repeat(chr(0x5c), $blocksize);
		$hmac = pack('H*', sha1(
			($key ^ $opad) . pack('H*', sha1(
				($key ^ $ipad) . $data
			))
		));
		return base64_encode($hmac);
	}

	/**
	 * Create signed URLs to your protected Amazon S3 files.
	 *
	 * @param string $awsAccessKey Your Amazon S3 access key
	 * @param string $secretKey Your Amazon S3 secret key
	 * @param string $bucket The bucket (mybucket.s3.amazonaws.com)
	 * @param string $objectPath The target file path
	 * @param int $expires In minutes
	 * @param array $customParams Key value pairs of custom parameters
	 * @return string Temporary signed Amazon S3 URL
	 * @see http://awsdocs.s3.amazonaws.com/S3/20060301/s3-dg-20060301.pdf
	 */

	function getSignedUrl($awsAccessKey, $secretKey, $bucket, $objectPath, $expires = 5, $customParams = array())
	{

		# Calculate the expire time.
		$expires = time() + intval(floatval($expires) * 60);

		# Clean and url-encode the object path.
		$objectPath = str_replace(array('%2F', '%2B'), array('/', '+'), rawurlencode(ltrim($objectPath, '/')));

		# Create the object path for use in the signature.
		$objectPathForSignature = '/' . $bucket . '/' . $objectPath;

		# Create the S3 friendly string to sign.
		$stringToSign = implode("\n", $pieces = array('GET', null, null, $expires, $objectPathForSignature));

		# Create the URL friendly string to use.
		$url = 'https://' . $bucket . '.s3.amazonaws.com/' . $objectPath;

		# Custom parameters.
		$appendCharacter = '?'; // Default append character.

		# Loop through the custom query parameters (if any) and append them to the string-to-sign, and to the URL strings.
		if (!empty($customParams)) {
			foreach ($customParams as $paramKey => $paramValue) {
				$stringToSign .= $appendCharacter . $paramKey . '=' . $paramValue;
				$url .= $appendCharacter . $paramKey . '=' . str_replace(array('%2F', '%2B'), array('/', '+'), rawurlencode(ltrim($paramValue, '/')));
				$appendCharacter = '&';
			}
		}

		# Hash the string-to-sign to create the signature.
		$signature = el_crypto_hmacSHA1($secretKey, $stringToSign);

		# Append generated AWS parameters to the URL.
		$queries = http_build_query($pieces = array(
			'AWSAccessKeyId' => $awsAccessKey,
			'Expires' => $expires,
			'Signature' => $signature,
		));
		$url .= $appendCharacter . $queries;

		# Return the URL.
		return $url;

	}

	function fetchControlNo($conoDoesExist, $flatbase, $originalSKU, $secretKey, $awsAccessKey, $bucket)
	{
		$fetchControlNo = array();
		if (file_exists($conoDoesExist)) {
			$record = $flatbase->read()->in($originalSKU)->get();
		}
		$displayCono = array();
		foreach ($record as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $key => $value) {
					$outputDownload = $displayCono[] = $value;
					$ahref = getSignedUrl($secretKey, $awsAccessKey, $bucket, $outputDownload, '15');
					$formEXT = substr($outputDownload, -3); //STORE FORMAT
					$fetchControlNo[$key]['href'] = $ahref;
					$fetchControlNo[$key]['formEXT'] = $formEXT;
				}

			}
		}
		return $fetchControlNo;
	}
	function fetchSample($sampleDoesExist, $flatbase, $completedSample, $secretKey, $awsAccessKey, $bucket)
	{
		$fetchSample = array();
		if (file_exists($sampleDoesExist)){
			$sample = $flatbase->read()->in($completedSample)->get();
		}
		$displaySample = array();
		foreach ($sample as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $key => $value) {
					$outputSample = $displaySample[] = $value;

					$ahref = getSignedUrl($secretKey, $awsAccessKey, $bucket, $outputSample, '15');

					$formEXT = substr($outputSample, -3); //STORE FORMAT

					$fetchSample[$key]['href'] = $ahref;
					$fetchSample[$key]['formEXT'] = $formEXT;	
				}

			}
		}
	return $fetchSample;
	}

	/*add_action( 'uslegal_quick_download_links', 'woo_new_product_tab_content', 10);
	//add_action( 'woocommerce_after_main_content', 'woo_new_product_tab_content', 10);
	if(!function_exists('woo_new_product_tab_content')){	
	function woo_new_product_tab_content() 
	{
	  if(is_product()):
		// wc_get_template('woocommerce/myaccount/s3_data.php');
	  		try{
	  			class_exists('RCP_Member');
				$member = new RCP_Member( get_current_user_id());
				$status = $member->get_status();
				$get_subscription_name = $member->get_subscription_name();
				
				if(strtolower($get_subscription_name) != RCP_SUBSCRIPTION_LEVAL_NAME && $status == 'active') {
					include 'woocommerce/myaccount/s3_data.php'; 
				}

				} catch (Exception $e)
				{
				    echo 'Caught exception: ',  $e->getMessage(), "\n";
				}



	  	
	  endif;
	}

	}*/
	
	
	
	
  }else{
	  //echo "<pre>";
	//  print_r(get_declared_classes());
	 // add_action( 'admin_notices', array(&$this, 'my_plugin_activation_notice') );
	  
	 die('WC_GFPA_Admin_Controller Class Not Exists'); 
  }
}