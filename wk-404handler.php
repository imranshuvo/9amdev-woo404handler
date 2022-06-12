<?php 
/**
 * Plugin Name: 404 to 301 for Woocommerce products
 * Description: This plugin redirects any 404 on woocommerce products url to it's similar product url. If similar url is not found, it will redirect to the homepage
 * Plugin URI : https://webkonsulenterne.dk
 * Version: 1.0
 * Author: Webkonsulenterne
 * Author URI: https://webkonsulenterne.dk
 * License: GPLv2.0
 * Text Domain: wk-404handler
 */


add_action('init','wk_trigger_404_handler');

function wk_trigger_404_handler(){
    add_action( 'template_redirect','wk_handle_404' );
}


//redirect function
function wk_redirect(){
    global $wpdb;

    //Note: Strip these words
    $search = array('produkt', 'product', 'kategori', 'category', 'detail','vare');
    $goto = '';
    $url = strtolower(urldecode($_SERVER['REQUEST_URI']));
    $url = str_replace($search, "", $url);
    $url = str_replace('/',"",$url);
    $url = explode("-", $url);

    //Just take first three sets if available
    $search_query = "SELECT ID FROM {$wpdb->prefix}posts
                             WHERE post_name LIKE '%{$url[0]}%' 
                             AND post_type = 'product' LIMIT 1";

    $id_arr = $wpdb->get_results($wpdb->prepare($search_query));

    if(is_array($id_arr) && count($id_arr) > 0){

        $id = $id_arr[0];

        if(property_exists($id,'ID')){
            $product = wc_get_product($id->ID);
            $goto = $product->get_permalink();
        }else{
            //Otherwise redirecto the home url
            $goto = get_site_url();
        }
    }else{
        //Redirect to home url 
        $goto = get_site_url();
    }


    if (!empty($goto)) {
        //Note: Giving Google a 301 response for updating the URL in their DB
        wp_redirect($goto, 301);
    }else {
        //Note: Give 404 error on broken links to files
        http_response_code(404);
    }
}

function wk_handle_404(){
    // Only if we can.
    if ( ! is_404() || is_admin() ) {
        return;
    }

    // Let's try folks.
    try {

        wk_redirect();
        exit();

    } catch ( Exception $ex ) {
        // Who cares?
    }
}


