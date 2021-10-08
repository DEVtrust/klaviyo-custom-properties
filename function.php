<?php
/**
 * Plugin Name: Klaviyo Custom Properties
 * Plugin URI: http://devtrust.biz
 * Description: custom events for klaviyo.
 * Version: 1.0
 * Author: Devtrust
 * Author URI: http://devtrust.biz
 * "namespaces": ["wp/v2/api"]
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
use Klaviyo\Model\ProfileModel as KlaviyoProfile;
use Klaviyo\Model\EventModel as KlaviyoEvent;


add_action( 'user_register', 'myplugin_registration_save', 10, 1 );

function myplugin_registration_save( $user_id ) {  
    	global $wpdb;
    	$userdata = array();
    $email= $_POST['email'];
        $table_name = $wpdb->prefix . 'klaviyoCustomTable';
	$result = $wpdb->get_results("SELECT * FROM $table_name where id=1");
	if(count($result) >0){
		define('PRIVATE_API_KEY', $result[0]->klaviyo_key);
		define('PUBLIC_API_KEY', $result[0]->public_api); 
		  $client = new Klaviyo(PRIVATE_API_KEY, PUBLIC_API_KEY );
	$profile = new KlaviyoProfile(
                array(
            '$email' =>$email,                    
		    'Assessment Started' =>'false',
		    'Assessment Complete' => 'false',
		    'Post Assessment Link'=>'NA',
		    'Custom Report Link'=>'NA',
		    'Payment Method '=>'NA',
		    'Trial Pack Type'=>'NA'
		     
               )
            );
            $client->publicAPI->identify( $profile ); 
}
}


//--------------------------------Klavio Woochecout ----------------------------
function KlavioOrderEvent($email,$order_status,$order_id,$payment_title,$purchase_type,$trial_pack_type_count){
    
   if($payment_title == "")
   {
   	$payment_title='Coupon Code Applied';
   }

	global $wpdb;
	$current_user = wp_get_current_user();
    if(isset($current_user->user_email) &&  $current_user->user_email !=''){
        $email=$current_user->user_email;
    }
	$table_name = $wpdb->prefix . 'klaviyoCustomTable';
	$result = $wpdb->get_results("SELECT * FROM $table_name where id=1");
	
	if(count($result) >0){
		define('PRIVATE_API_KEY', $result[0]->klaviyo_key);
		define('PUBLIC_API_KEY', $result[0]->public_api); 
		  $client = new Klaviyo(PRIVATE_API_KEY, PUBLIC_API_KEY );
			$profile = new KlaviyoProfile(
                array(
                    '$email' =>$email,
                    'Trial Pack Type' => ucwords($order_status),
					'Payment Method ' => ucwords($payment_title),
					"Purchase Type" => $purchase_type,
					"Trial Pack Type Count"=> $trial_pack_type_count
                )
            );
            $client->publicAPI->identify( $profile );  
//	 $client->publicAPI->track( $event ); 
	 $_SESSION['OrderId#']=$order_id;
	// echo "fsdf";
	 return true;
	}else{
		return false;
	}
}

add_action('KlavioOrder_Event','KlavioOrderEvent',10,3);
add_action( 'woocommerce_thankyou', array('Wc_class', 'woo_checkout'));
//------------------Woo commerce hook for checkout -----------------------------
class Wc_class{


public static function woo_checkout($order_id){
	 $order = wc_get_order( $order_id );
	 $items = $order->get_items();
	 
	 $product_name=array();
	$payment_title = $order->get_payment_method_title();
//echo $digits = $order->get_meta('_wc_square_credit_card_account_four');
//echo "<pre>";print_r($order);

	foreach ( $items as $item ) {
            $product_name[] = $item->get_name();
        }
        $email  = $order->get_billing_email();
        if( array_partial_search($product_name,'SleepZ') && array_partial_search($product_name,'CalmZ')){
            $order_status="SleepZ and CalmZ";
        }else if(array_partial_search($product_name,'SleepZ') && !array_partial_search($product_name,'CalmZ')){
            $order_status="SleepZ";
        }else if(!array_partial_search($product_name,'SleepZ') && array_partial_search($product_name,'CalmZ')){
            $order_status="CalmZ";
            
        }
/******************Purchase Type & Trial Pack Type Count************** */
$prd_name=$product_name[0];
function purchase_type($str)
{
if (strpos($str, 'Trial') !== false) {
    return 'Trial Pack';
}
else
{
	return "Full Bottle";
}
}

function trial_pack_type_count($str)
{
if (strpos($str, '2-Pack') !== false) {
    return '2 pack';
}
else if(strpos($str, '4-Pack') !== false)
{
	return "4 pack";
}
else
{
	return "";
}
}

$purchase_type=purchase_type($prd_name);
$trial_pack_type_count=trial_pack_type_count($prd_name);

/****************************** */
       KlavioOrderEvent($email,$order_status,$order_id,$payment_title,$purchase_type,$trial_pack_type_count);


    
}
}


//add_action('woocommerce_after_order_notes', 'KlavioShipStations');
add_action( 'ordercompletetrack', 'ordercompletetrack',10,1);

function ordercompletetrack($order_id){
   //echo $order_id; die;
   
	$trackingNo=array();
    remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) );
    $comments = get_comments( array(
        'post_id' => $order_id,
        'orderby' => 'comment_ID',
        'order'   => 'DESC',
        'approve' => 'approve',
        'type'    => 'order_note',
    ) );
    $notes = wp_list_pluck( $comments, 'comment_content' );
   // print_r($notes);die;
    add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) );
     $found = [];
  
    // Loop through each item and check for a match.
    foreach ( $notes as $string ) {
        // If found somewhere inside the string, add.
        if ( strpos( $string, 'tracking' ) !== false ) {
            $found[] = $string;
        }
    }
   if(count( $found) >0){
     $trackingNo=array_reverse(explode( "tracking number",$found[0]))[0];
  // return $trackingNo ."----".$order_id;die;
     KlavioShipStations($trackingNo, $order_id);
    }
    // KlavioShipStations('#jhjhj', '4983');
}
function KlavioShipStations($TrackingNumber="", $order_id=""){
	global $wpdb;
    $order = wc_get_order( $order_id );
    $billing_email  = $order->get_billing_email();
	$table_name = $wpdb->prefix . 'klaviyoCustomTable';
	$result = $wpdb->get_results("SELECT * FROM $table_name where id=1");
	if(count($result) >0){
	    //echo "hll"; die;
		define('PRIVATE_API_KEY', $result[0]->klaviyo_key);
		define('PUBLIC_API_KEY', $result[0]->public_api); 
		  $client = new Klaviyo(PRIVATE_API_KEY, PUBLIC_API_KEY );
			 $profile = new KlaviyoProfile(
                array(
                '$email' =>$billing_email,
                'Tracking URL Link'=> $result[0]->usps_url . explode("(",rtrim($TrackingNumber,"."))[0]
                //'Tracking URL Link'=> "https://tools.usps.com/go/TrackConfirmAction.action?tLabels=".explode("(",rtrim($TrackingNumber,"."))[0]
                )
            );
		
	 $client->publicAPI->identify( $profile ); 
	 return true;
	}else{
		return false;
	}
}
//-------------------Assestment form url Klavio---------------------------------
function KlavioAssestment($email,$url,$Status){
    //-------------
    //echo  "ddgd0".$email;
    //-------------
    $product_data=get_assessment_product();
   
	global $wpdb;
	$current_user = wp_get_current_user();	
    if(isset($current_user->user_email) &&  $current_user->user_email !=''){
        $email=$current_user->user_email;
    }
	$table_name = $wpdb->prefix . 'klaviyoCustomTable';
	$result = $wpdb->get_results("SELECT * FROM $table_name where id=1");
	if(count($result) >0 && $_SESSION['uuid']!=$_SESSION['urlid']){
		define('PRIVATE_API_KEY', $result[0]->klaviyo_key);
		define('PUBLIC_API_KEY', $result[0]->public_api); 
		  $client = new Klaviyo(PRIVATE_API_KEY, PUBLIC_API_KEY );
		  $homeurl=get_home_url();
		  $profile = new KlaviyoProfile(
                array(
                    '$email' =>$email,
                    'Assessment Complete'=>$Status,
					'Custom Report Link' => $url,
					'Post Assessment Link'=>$homeurl.'/post-assessment-new/?ref='.$_SESSION['uuid'],
					'Product Name'=>$product_data['product_name'],
					'Product Price'=>$product_data['product_price'],
					'Product Image'=>$product_data['image_url']
                )
            );
	 $client->publicAPI->identify( $profile ); 
	 
	 // Create metrics for same -----------------------------------------
	 
	 
	 if(count($result) >0 && $_SESSION['uuid']!=$_SESSION['urlid'] ){
		define('PRIVATE_API_KEY', $result[0]->klaviyo_key);
		define('PUBLIC_API_KEY', $result[0]->public_api); 
		  $client = new Klaviyo(PRIVATE_API_KEY, PUBLIC_API_KEY );
			$event = new KlaviyoEvent(
				array(
					'event' => 'Assessment Complete',
					'customer_properties' => array(
						'$email' => $email  
					),
					'properties' => array(
					    'AssessmentComplete'=>true,
					    'ProductName'=>$product_data['product_name'],
					    'ProductPrice'=>$product_data['product_price'],
					    'ProductImage'=>$product_data['image_url']
					)
				)
			);	
	 $_SESSION['urlid']=$_SESSION['uuid'];
	 $client->publicAPI->track( $event );
	 }
	 
	 //---------------------------------------------
	 return true;
	}else{
		return false;
	}
}

/* ------------------ Klaviyo Assesment Product recomandation*/
function get_assessment_product()
{
	
	global $wpdb;
	$result=array();
	$uuid = wp_get_session_token();
	//$uuid="55168a9b-b7dc-43d7-bdb4-247930eca53e";
	$recom = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}chartdata WHERE uuid=%s", $uuid));
	$result['product_name']=$recom->trialpack;
	$post_product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts WHERE post_title LIKE '%s' and post_type=%s", $result['product_name'],'product'));
	
	$product_id=$post_product->ID;
	$product   = wc_get_product( $product_id );
	$image_id  = $product->get_image_id();
        $result['image_url']= wp_get_attachment_image_url( $image_id, 'full' );
	$result['product_name']=$product->get_name();
	$result['product_price']=$product->get_price();	
return $result;	
} 

/* --------------------------URL ---------------------------------------------*/

function assesment_platform_url(){
$uuid = $_SESSION['uuid'];
global $wpdb;
$results = $wpdb->get_row($wpdb->prepare("SELECT email FROM {$wpdb->prefix}recommendations WHERE uuid=%s", $uuid));
$email=$results->email;
$table_name = $wpdb->prefix . 'klaviyoCustomTable';
$result = $wpdb->get_results("SELECT * FROM $table_name where id=1");

$url = $result[0]->report_url.$uuid;
$Status='true';
KlavioAssestment($email,$url,$Status); 
}
add_shortcode('Klavio_assetment_Url_Event','assesment_platform_url');

//------------------------------------------------------------------------------
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
//---------------------

//---------------
# for users not logged in
add_action('wp_ajax_nopriv_assessment_start_api', 'assessment_start_api');

# for users logged in
add_action('wp_ajax_payment_assessment_start_api', 'assessment_start_api');



add_action( 'rest_api_init', function () {
	register_rest_route( 'wp/v2/api/', 'assessmentstart', array(
	  'methods' => 'POST',
	  'callback' => 'assessment_start_api',
	) );
  } );

function assessment_start_api() { 

    global $wpdb;
    $resp="";
    $table_name = $wpdb->prefix . 'klaviyoCustomTable';
	$result = $wpdb->get_results("SELECT * FROM $table_name where id=1");
	if(count($result) >0){
		define('PRIVATE_API_KEY', $result[0]->klaviyo_key);
		define('PUBLIC_API_KEY', $result[0]->public_api); 
		
		$email= $_REQUEST['email'];
        $key= $_REQUEST['pubkey'];
        if($key==$result[0]->public_api){
            KlavioAssestmentStartedApi($email);
            $resp=array(
                'success'=>true
            );
            
        }else{
            $resp=array(
                'success'=>false,
                'error'=>'Invalid Api Key'
            );
        }
        
	}
   echo  json_encode($resp);
 // wp_die();
}

function KlavioAssestmentStartedApi($entered_email){

	global $wpdb;
	$current_user = wp_get_current_user();
    if(isset($current_user->user_email) &&  $current_user->user_email !=''){
        $email=$current_user->user_email;
    }else{
        $email=$entered_email;
    }
	$table_name = $wpdb->prefix . 'klaviyoCustomTable';
	$result = $wpdb->get_results("SELECT * FROM $table_name where id=1");
	if(count($result) >0){
		define('PRIVATE_API_KEY', $result[0]->klaviyo_key);
		define('PUBLIC_API_KEY', $result[0]->public_api); 
		  $client = new Klaviyo(PRIVATE_API_KEY, PUBLIC_API_KEY );
		$profile = new KlaviyoProfile(
                array(
                    '$email' =>$email,
                    'Assessment Started' =>true
                )
            );
	// $_SESSION['urlid']=$_SESSION['uuid'];
	 $client->publicAPI->identify( $profile ); 
	 
	 //-------------------------
	 
		define('PRIVATE_API_KEY', $result[0]->klaviyo_key);
		define('PUBLIC_API_KEY', $result[0]->public_api); 
		  $client = new Klaviyo(PRIVATE_API_KEY, PUBLIC_API_KEY );
			$event = new KlaviyoEvent(
				array(
					'event' => 'Assessment Started',
					'customer_properties' => array(
						'$email' => $email  
					),
					'properties' => array(
					    'Started'=>'true'
					   
					)
				)
			);	
	// $_SESSION['urlid']=$_SESSION['uuid'];
	 $client->publicAPI->track( $event ); 
	  
		  $client2 = new Klaviyo(PRIVATE_API_KEY, PUBLIC_API_KEY );
		  $homeurl=get_home_url();
		  $profile2 = new KlaviyoProfile(
                array(
                    '$email' =>$email,
                    'Assessment Complete'=>false,
					
                )
            );
	 $client2->publicAPI->identify( $profile2 ); 
	 
		define('PRIVATE_API_KEY', $result[0]->klaviyo_key);
		define('PUBLIC_API_KEY', $result[0]->public_api); 
		  $client1 = new Klaviyo(PRIVATE_API_KEY, PUBLIC_API_KEY );
			$event1 = new KlaviyoEvent(
				array(
					'event' => 'Assessment Complete',
					'customer_properties' => array(
						'$email' => $email  
					),
					'properties' => array(
					    'AssessmentComplete'=>false,
						
					)
				)
			);	
	// $_SESSION['urlid']=$_SESSION['uuid'];
	 $client1->publicAPI->track( $event1 );
	 
	 //----------------------------
	 return true;
	}else{
		return false;
	}
}