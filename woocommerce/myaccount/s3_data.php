<?php
/**
 * S3-Data
 *
 * Shows S3-Data downloads on the account page.
 *
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


define("PRODUCT_TITLE_LIMIT",35);
define("RULES_TEXT_LIMIT",20);
global  $product;


function retrieve_orders_ids_from_a_product_id( $product_id ) {
    global $wpdb;
    // Define HERE the orders status to include in  <==  <==  <==  <==  <==  <==  <==
    $orders_statuses = "'wc-completed', 'wc-processing', 'wc-on-hold'";

    # Requesting All defined statuses Orders IDs for a defined product ID
    $orders_ids = $wpdb->get_col( "
        SELECT DISTINCT woi.order_id
        FROM {$wpdb->prefix}woocommerce_order_itemmeta as woim, 
             {$wpdb->prefix}woocommerce_order_items as woi, 
             {$wpdb->prefix}posts as p
        WHERE  woi.order_item_id = woim.order_item_id
        AND woi.order_id = p.ID
        AND p.post_status IN ( $orders_statuses )
        AND woim.meta_key LIKE '_product_id'
        AND woim.meta_value LIKE '$product_id'
        ORDER BY woi.order_item_id DESC"
    );
    // Return an array of Orders IDs for the given product ID
    return $orders_ids;
}





function break_text($x, $length)
{
  if(strlen($x)<=$length)
  {
    echo $x;
  }
  else
  {
    $y=substr($x,0,$length) . '...';
    echo $y;
  }
}



$orderArray = array();
if(is_product())$orderArray = retrieve_orders_ids_from_a_product_id(get_the_ID());
if(empty($orderArray) && is_product()) return;


$order_statuses = array('wc-completed');

## ==> Define HERE the customer ID
$customer_user_id = get_current_user_id(); // current user ID here for example

if(class_exists('RCP_Member'))
{
	$member = new RCP_Member( $customer_user_id);
}else{
	die('RCP_Member class not exists');
}

/**
 * Get customer available downloads.
 *
 * @param int $customer_id Customer/User ID
 * @return array
 */
function wc_get_customer_available_downloads_newBykey( $customer_id,$orderkeydata,$productID) {
	$downloads   = array();
	$_product    = null;
	$order       = null;
	$file_number = 0;

	// Get results from valid orders only
	$results = wc_get_customer_download_permissions( $customer_id );

	if ( $results ) {
		foreach ( $results as $result ) {
			if ( ! $order || $order->get_id() != $result->order_id ) {
				// new order
				$order    = wc_get_order( $result->order_id );
				$_product = null;
			}

			// Make sure the order exists for this download
			if ( ! $order ) {
				continue;
			}

			// Downloads permitted?
			if ( ! $order->is_download_permitted() ) {
				continue;
			}

			$product_id = intval( $result->product_id );

			if ( ! $_product || $_product->get_id() != $product_id ) {
				// new product
				$file_number = 0;
				$_product    = wc_get_product( $product_id );
			}

			// Check product exists and has the file
			if ( ! $_product || ! $_product->exists() || ! $_product->has_file( $result->download_id ) ) {
				continue;
			}

			$download_file = $_product->get_file( $result->download_id );

			// Download name will be 'Product Name' for products with a single downloadable file, and 'Product Name - File X' for products with multiple files.
			$download_name = apply_filters(
				'woocommerce_downloadable_product_name',
				$download_file['name'],
				$_product,
				$result->download_id,
				$file_number
			);

			$downloads[$result->order_key][$product_id] = array(
				'download_url'          => add_query_arg(
					array(
						'download_file' => $product_id,
						'order'         => $result->order_key,
						'email'         => urlencode( $result->user_email ),
						'key'           => $result->download_id,
					),
					home_url( '/' )
				),
				'download_id'           => $result->download_id,
				'product_id'            => $_product->get_id(),
				'product_name'          => $_product->get_name(),
				'product_url'           => $_product->is_visible() ? $_product->get_permalink() : '', // Since 3.3.0.
				'download_name'         => $download_name,
				'order_id'              => $order->get_id(),
				'order_key'             => $order->get_order_key(),
				'downloads_remaining'   => $result->downloads_remaining,
				'access_expires'        => $result->access_expires,
				'file'                  => array(
					'name' => $download_file->get_name(),
					'file' => $download_file->get_file(),
				),
			);

			
			$file_number++;
		}
	}
	//echo "<pre>";
	return $downloads[$orderkeydata][$productID];

	//return apply_filters( 'woocommerce_customer_available_downloads', $downloads, $customer_id );
}



// Getting current customer orders
$customer_orders = wc_get_orders( array(
    'meta_key' => '_customer_user',
    'meta_value' => $customer_user_id,
    'post_status' => $order_statuses,
    'numberposts' => -1,
    'post__in' => $orderArray,
) );


if ( $customer_orders ) :?>

<style>
.woocommerce-account .woocommerce-MyAccount-navigation{
	width: 16% !important;	
}
.woocommerce-MyAccount-content {
	width: 83% !important;
}
</style>
<table class="woocommerce-MyAccount-downloads shop_table shop_table_responsive">
		<thead>
			<tr>
				<th class="download-product"><span class="nobr"><?php echo esc_html("Order #.") ?></span></th>
				<th class="download-product"><span class="nobr"><?php echo esc_html("Form name") ?></span></th>
				<th class="mainfiles-product"><span class="nobr"><?php echo esc_html("Controller #") ?></span></th>
				<th class="sample-product"><span class="nobr"><?php echo esc_html("Downloads") ?></span></th>
				
			</tr>
		</thead>
		
		
<?php 

// Loop through each customer WC_Order objects
foreach($customer_orders as $order ){

    // Order ID (added WooCommerce 3+ compatibility)
    $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
	$order = new WC_Order($order_id);
	//print_r($order->order_key);
	$order_status = $order->get_status();
	/*if( $order_status == 'completed' )
		continue;*/
	
    // Iterating through current orders items
    
	$order_items    = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
	
	$column_name = 1;
	$mainProductID = NULL;
	foreach ( $order_items as $item_id => $item_values ) {
		$fileName = array();
		$productItem_id = wc_get_order_item_meta($item_id,'_product_id',true);
		$item_data = $item_values->get_data();
		$is_bundleProduct = get_post_meta($productItem_id,'_yith_wcpb_bundle_data',true);
		  
		   if($is_bundleProduct){
			
			}else{
			}
			$userSelectedName = array();
			foreach( $item_values->get_formatted_meta_data() as $meta_id => $meta ) 
			{ 
				//if($product_idCustom  == $productItem_id )
				{
				  $userSelectedName[$meta->key] = $meta->value;
				 
				}
			}
			
			
			
			$_gravity_forms_history = wc_get_order_item_meta($item_id,'_gravity_forms_history',true);			
			$gravity_rules_controls  = get_post_meta( $productItem_id,'_gravity_form_rules',true);
			
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
					  @$removepipe = explode("|",$_gravity_forms_history['_gravity_form_lead'][$selected_fields_rules[$k][2]]);
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
						 if(!empty($generate_controllerfilestext['usermodify'])){	
						  foreach($generate_controllerfilestext['usermodify'] as $key=>$usermodifyval)
						  {
							$usermodify[$key] =  explode("-",$usermodifyval);
									
						  }
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
						if(isset($system_generateexp) && isset($usermodify[intval($productItem_id)]))
						$adminCreateRules[$totalRules][implode("_",$system_generateexp)] = implode("-",$usermodify[intval($productItem_id)]);
					}
				}
			}  
			//  if($gravity_rules['gfuser_settings'] == 'state_specific' || $gravity_rules['gfuser_settings'] == 'traverse')
			  {
				$control_number = array();
				$control_number  = explode("_",$gravity_rules['control_number']);
				$endwithSku = "_".end($control_number);
			  }
				$mapped_fieldsGet = array();
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
		  

	   } 
?>
	<tr>
				<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>" width='6%'>
					<a href="<?php echo esc_url(  $order->get_view_order_url() ); ?>"><span style='word-wrap: break-word;'>#<?php echo $order->get_id()?>
					</span></a>
				</td>
				<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>" >
					<a href="<?php echo esc_url( get_permalink( $item_data['product_id'])); ?>" data-toggle="tooltip" data-html="true" title="<?php echo $item_data['name'];?>"> <span style='word-wrap: break-word;padding-left:10px;'><?php echo break_text($item_data['name'],PRODUCT_TITLE_LIMIT);?>


					</span></a>


				
				</td>
				<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>" style='text-align:center;'>
					<span data-toggle="tooltip" data-html="true" title="<?php echo $fileName[0];?>">
					<?php if(isset($fileName[0])){ 
				        #<?php //echo implode(",", array_values($userSelectdData));
					    echo break_text($fileName[0],RULES_TEXT_LIMIT);
					    
					  }else{
					    echo "-";
					  }
				 ?></span>&nbsp;
				</td>
				<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
				<?php 
				$arrayfile = array();
				if(isset($fileName[0]))
				{
					/*print_r($fileName);
					die();*/
					$arrayfile = getFileURLByName($fileName);
				}
				?>
				<div class="main_files files-download" style="padding: 5px;text-align: center;">
				<?php if(isset($arrayfile[0]['fetchControlNo'])):?>				
				<?php 
						 
						 
						foreach($arrayfile[0]['fetchControlNo'] as $fetchControlNo)
						{
							
//							echo "<a class='woocommerce-MyAccount-downloads-file button alt ".$fetchControlNo["formEXT"]." ' href=".$fetchControlNo["href"]." style='margin: 5px;' >".ucfirst($fetchControlNo["formEXT"])."</a>";
							echo "<a class='woocommerce-MyAccount-downloads-file' href=".$fetchControlNo["href"]." style='padding: 5px;' >";
							switch(strtolower($fetchControlNo["formEXT"])){
								case 'doc': echo "<image width='24px' height='24px' src='".UL_PLUGIN_URL."images/doc_icon.png' alt='Download doc' title='Download Doc'>";
									       break;
                                case 'rtf': echo "<image width='24px' height='24px' src='".UL_PLUGIN_URL."images/rtf_icon.png' alt='Download RTF' title='Download RTF'>";
									       //echo ucfirst($fetchSample["formEXT"]);
									       break;
                                case 'pdf': echo "<image width='24px' height='24px' src='".UL_PLUGIN_URL."images/pdf_icon.png' alt='Download PDF' title='Download PDF'>";
									       //echo ucfirst($fetchSample["formEXT"]);
									       break;
								default: echo ucfirst($fetchControlNo["formEXT"]);break;
							}
							echo "</a>";

						}?>
						</span>
				<?php else: 
				
					$data = wc_get_customer_available_downloads_newBykey($customer_user_id,$order->order_key,$item_data['product_id']);
					if($data)			
						{
							echo '<a href="' . esc_url( $data['download_url'] ) . '" class="woocommerce-MyAccount-downloads-file button alt">' . esc_html( $data['download_name'] ) . '</a>';
	
						}

				endif;
				
				?>
				</div>
				<?php 
				
				$status = $member->get_status(); 
				//print_r($status);
				$get_subscription_name = $member->get_subscription_name();
				if((!empty($arrayfile[0]['fetchSample'])) && (strtolower($get_subscription_name) != RCP_SUBSCRIPTION_LEVAL_NAME) && $status == 'active'):
					
				?>
				<!--<div style='max-height:8px;'>&nbsp;</div>-->
				<div class="sample_files files-download" style="border-top: 1px solid #ccc;padding: 5px;text-align: center;">
				<div >Completed Samples</div>
				<div style='padding-top: 5px;'>
				<?php 
						foreach($arrayfile[0]['fetchSample'] as $fetchSample)
						{
							//echo "<a class='woocommerce-MyAccount-downloads-file button alt ".$fetchSample["formEXT"]."' href=".$fetchSample["href"]." style='margin: 5px;' >".ucfirst($fetchSample["formEXT"])."</a>";

							echo "<a class='woocommerce-MyAccount-downloads-file' href=".$fetchSample["href"]." ' style='padding: 5px;'>";
							switch(strtolower($fetchSample["formEXT"])){
								case 'doc': echo "<image width='24px' height='24px' src='".UL_PLUGIN_URL."images/doc_icon.png' alt='Download doc' title='Download Doc'>";
									       break;
                                case 'rtf': echo "<image width='24px' height='24px' src='".UL_PLUGIN_URL."images/rtf_icon.png' alt='Download RTF' title='Download RTF'>";
									       //echo ucfirst($fetchSample["formEXT"]);
									       break;
                                case 'pdf': echo "<image width='24px' height='24px' src='".UL_PLUGIN_URL."images/pdf_icon.png' alt='Download PDF' title='Download PDF'>";
									       //echo ucfirst($fetchSample["formEXT"]);
									       break;
								default: echo ucfirst($fetchSample["formEXT"]);break;
							}
							echo "</a>";

						}	
				 ?>
				</div></div>
				<?php endif; 
				/*}*/?>
				</td>	
				
	</tr>
	<?php 
		
	   $column_name ++;
	   
    }





}?>
</table>
<?php  else :

 ?>

	<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
		<a class="woocommerce-Button btn btn-normal pull-right" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php esc_html_e( 'Go Shop', 'woocommerce' ) ?>
		</a>
		<?php esc_html_e( 'No downloads available yet.', 'woocommerce' ); ?>
	</div>
<?php endif; ?>
<?php 

   add_action('wp_footer', 'wpshout_action_example',99); 
function wpshout_action_example() { 
    echo '<script> jQuery( document ).ready(function($) {
    console.log( "ready!" );
    $(\'[data-toggle="tooltip"]\').tooltip(); 
});</script>'; 
}

?>

