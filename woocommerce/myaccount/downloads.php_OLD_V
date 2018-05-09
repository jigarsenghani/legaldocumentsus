<?php
/**
 * Downloads
 *
 * Shows downloads on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/downloads.php.
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


 


$downloads     = WC()->customer->get_downloadable_products();
$has_downloads = (bool) $downloads;

do_action( 'woocommerce_before_account_downloads', $has_downloads );

 ?>

<?php if ( $has_downloads ) : ?>
<style>
.files-download .doc:hover{
	background-color:#3366CC !important;
}
.files-download .rtf:hover{
	background-color:#0069FF !important;
}
.files-download .pdf:hover{
	background-color:#C30B15 !important;
}
.files-download{
    margin-bottom: 10px;
}
.files-download a {
    margin-left: 15px;
	
}
  
</style>
	<?php do_action( 'woocommerce_before_available_downloads' ); ?>

	<table class="woocommerce-MyAccount-downloads shop_table shop_table_responsive">
		<thead>
			<tr>
				<?php /*foreach ( wc_get_account_downloads_columns() as $column_id => $column_name ) : ?>
					<?php if('download-expires' === $column_id){
						//continue;
					}?>
					<th class="<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
				<?php endforeach;*/ ?>
				
				<th class="download-product"><span class="nobr"><?php echo esc_html("Product") ?></span></th>
				<th class="mainfiles-product"><span class="nobr"><?php echo esc_html("#Controller") ?></span></th>
				<th class="sample-product"><span class="nobr"><?php echo esc_html("Downloads") ?></span></th>
				
			</tr>
		</thead>
		<?php $status = array(); $order_item_id_list=[];
		$column_id = 1;
		?>
		<?php foreach ( $downloads as $download ) :

			if( is_product() &&  $download['product_id'] != get_the_ID()) continue;
		 ?>
			<?php $order_item_id_list = get_download_details($download['order_id'],$order_item_id_list); 
			
				list($bundly_by, $order_item_id_list) = get_order_items($download['order_id'],$download['product_id'],$order_item_id_list); 
								
				$bundly_ID = '';
				if(!empty($bundly_by[1]))
				{
					$bundly_ID = "&bundly_ID=" . $bundly_by[1];	
				}
			
				$userSelectdData = '';
				 $fileName = get_rules_by_ID($download['product_id'],$download['order_id'],$bundly_by[1]);
				 
				 //echo "<pre>"; print_r($fileName);
				 
				$userSelectdData = $fileName['userSelectedName'];				 
			?>
			<tr>
				<td class="<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>" width='45%'>
				<a href="<?php echo esc_url( get_permalink( $download['product_id'])); ?>"><span style='word-wrap: break-word;'>
											<?php //echo esc_html( $download['product_name'] ); 

											$string = strip_tags($download['product_name']);
											if (strlen($string) > 300) {

											    // truncate string
											    $stringCut = substr($string, 0, 30);
											    $endPoint = strrpos($stringCut, ' ');

											    //if the string doesn't contain any space then it will cut without word basis.
											    $string = $endPoint? substr($stringCut, 0, $endPoint):substr($stringCut, 0);
											    $string .= '...';
											}
											echo $string;	
											?>
										</span></a>
				</td>
				<td class="<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
				<?php if($fileName['filename'][0]){ 
				        #<?php //echo implode(",", array_values($userSelectdData));
					    echo $fileName['filename'][0];
					  }else{
					    echo "-";
					  }
				 ?>&nbsp;
				 
				</td>				
				<td class="<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
				<?php 
				$arrayfile = getFileURLByName($fileName['filename']); ?>
				<div class="main_files files-download">
				<?php if(!empty($arrayfile[0]['fetchControlNo'])):?>				
					<span style='padding-right:20px;'>Main</span><span>
				<?php 
						 
						 
						foreach($arrayfile[0]['fetchControlNo'] as $fetchControlNo)
						{
							
							echo "<a class='woocommerce-MyAccount-downloads-file button alt ".$fetchControlNo["formEXT"]." ' href=".$fetchControlNo["href"]." style='margin: 5px;' >".ucfirst($fetchControlNo["formEXT"])."</a>";
						}?>
						</span>
				<?php else: ?>
				<a href="<?php echo esc_url( $download['download_url'] ) .$bundly_ID ; ?>" class="woocommerce-MyAccount-downloads-file button alt">Download</a><strong><?php //echo $bundly_by;?></strong>
				<?php endif;?>
				</div>
				<?php 
				if(class_exists('RCP_Member')){
				$member = new RCP_Member( get_current_user_id());
				$status = $member->get_status(); 
				$statusname = $member->get_subscription_name();

				if((!empty($arrayfile[0]['fetchSample'])) && ($statusname != 'No Subscription') ):
				//if((!empty($arrayfile[0]['fetchSample']))):?>
				<div class="sample_files files-download">
				<div style="width:24%;float:left;">Sample</div><div style="float:left;">
				<?php 
						foreach($arrayfile[0]['fetchSample'] as $fetchSample)
						{
							echo "<a  class='woocommerce-MyAccount-downloads-file button alt ".$fetchSample["formEXT"]."' href=".$fetchSample["href"]." style='margin: 5px;' >".ucfirst($fetchSample["formEXT"])."</a>";
						}	
				 ?>
				</div></div>
				<?php endif; }?>
				</td>	
			</tr>
		<?php $totalRecode++; endforeach; ?>
	</table>
	<?php do_action( 'woocommerce_after_available_downloads' ); ?>

<?php else : ?>
	<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
		<a class="woocommerce-Button btn btn-normal pull-right" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php esc_html_e( 'Go Shop', 'woocommerce' ) ?>
		</a>
		<?php esc_html_e( 'No downloads available yet.', 'woocommerce' ); ?>
	</div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_account_downloads', $has_downloads ); ?>
