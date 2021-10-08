<?php
/**
 * Template Name: Attachment Event
 * @package WordPress
 * @since 28/10/2018
 */
require 'vendor/autoload.php';
use Klaviyo\Klaviyo as Klaviyo;

$client = new Klaviyo( 'pk_497a9f3e8208fd61948680ed11d7848ee7', 'VpgAQS' );

use Klaviyo\Model\EventModel as KlaviyoEvent;
 
$event = new KlaviyoEvent(
    array(
        'event' => 'Checkout Attachment',
        'customer_properties' => array(
            '$email' => $email    
        ),
        'properties' => array(
            'Attachment' => $status,
			'OrderNum'=>$order_id,
			'Quantity'=>$qty,
			'Total'=>$total
        )
    )
);
    
$client->publicAPI->track( $event ); 
?>