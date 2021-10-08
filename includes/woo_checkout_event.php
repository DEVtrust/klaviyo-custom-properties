<?php
/**
 * Plugin Name: Klaviyo Custom Properties
 * Plugin URI: http://devtrust.biz
 * Description: The very first plugin that I have ever created.
 * Version: 1.0
 * Author: Saurabh
 * Author URI: http://devtrust.biz
 */
 
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

!defined('KLAVIYOCUSTOM_DIR_FILE') && define('KLAVIYOCUSTOM_DIR_FILE', plugin_dir_path(__FILE__));
function installer(){
   require_once KLAVIYOCUSTOM_DIR_FILE.'admin/installer.php';
}
register_activation_hook(__file__, 'installer');
require_once KLAVIYOCUSTOM_DIR_FILE.'admin/settings.php';

require_once 'klaviyo/vendor/autoload.php';
use Klaviyo\Klaviyo as Klaviyo;

use Klaviyo\Model\EventModel as KlaviyoEvent;

function KlavioOrderEvent($email,$order_status,$order_id,$purchase_type='',$trial_pack_type_count=''){
	global $wpdb;
	$table_name = $wpdb->prefix . 'klaviyoCustomTable';
	$result = $wpdb->get_results("SELECT * FROM $table_name where id=1");
	if(count($result) >0){
		define('PRIVATE_API_KEY', $result[0]->klaviyo_key);
		define('PUBLIC_API_KEY', $result[0]->public_api); 
		  $client = new Klaviyo(PRIVATE_API_KEY, PUBLIC_API_KEY );
			$event = new KlaviyoEvent(
				array(
					'event' => 'OrderId#'.$order_id,
					'customer_properties' => array(
						'$email' => $email  
					),
					'properties' => array(
						'Trial Pack Type' => ucwords($order_status)
					)
				)
			);	   
	 $client->publicAPI->track( $event ); 
	 return true;
	}else{
		return false;
	}
}
function KlavioAssestment($email,$order_status){
	global $wpdb;
	$table_name = $wpdb->prefix . 'klaviyoCustomTable';
	$result = $wpdb->get_results("SELECT * FROM $table_name where id=1");
	if(count($result) >0){
		define('PRIVATE_API_KEY', $result[0]->klaviyo_key);
		define('PUBLIC_API_KEY', $result[0]->public_api); 
		  $client = new Klaviyo(PRIVATE_API_KEY, PUBLIC_API_KEY );
			$event = new KlaviyoEvent(
				array(
					'event' => 'Custom Report URL',
					'customer_properties' => array(
						'$email' => $email  
					),
					'properties' => array(
						'URL' => $order_status
					)
				)
			);	   
	 $client->publicAPI->track( $event ); 
	 return true;
	}else{
		return false;
	}
}
add_action('KlavioOrder_Event','KlavioOrderEvent',10,3);
add_action( 'woocommerce_thankyou', array('Wc_class', 'woo_checkout'));
class Wc_class{
public static function woo_checkout($order_id){
	 $order = wc_get_order( $order_id );
	 $items = $order->get_items();
	 $product_name=array();
	 foreach ( $items as $item ) {
            $product_name[] = $item->get_name();
        }
        $email  = $order->get_billing_email();
        if( (array_partial_search($product_name,'Trial') || array_partial_search($product_name,'SleepZ') && array_partial_search($product_name,'CalmZ'))){
            $order_status="Y";
            KlavioOrderEvent($email,$order_status,$order_id);
        }else{
             $order_status="N";
        }
}
}
function array_partial_search( $array, $keyword ) {
    $found = [];
    // Loop through each item and check for a match.
    foreach ( $array as $string ) {
        // If found somewhere inside the string, add.
        if ( strpos( $string, $keyword ) !== false ) {
            $found[] = $string;
        }
    }
   if(count( $found) >0){
        return true;
    }else{
        return false;
    }
}
/* URL */

function assesment_platformdata(){
$uuid = $_SESSION['uuid'];
global $wpdb;
$results = $wpdb->get_row($wpdb->prepare("SELECT email FROM {$wpdb->prefix}recommendations WHERE uuid=%s", $uuid));
echo $email=$results->email;
echo $url = 'https://app.zippz.work/pilot/report/'.$uuid;
KlavioAssestment($email,$url); 
}
add_shortcode('Klavio_assetment_Event','assesment_platformdata');
