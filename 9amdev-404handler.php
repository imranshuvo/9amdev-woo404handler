<?php 
/**
 * Plugin Name: 404 to 301 for products url on WooCommerce
 * Plugin URI:  https://wordpress.org/plugins/404-to-301-for-woo-products-url
 * Description: This plugin redirects any 404 on woocommerce products url to it's similar product url or the related category url. If similar url or category url is not found, it will redirect to the homepage
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            9amdev
 * Author URI:        https://9amdev.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 9amdev-woo404handler
 */


add_action('plugins_loaded','nineamdev_woo404handler_activation_logic');

//register_activation_hook(__FILE__, 'nineamdev_woo404handler_activation_logic');

function nineamdev_woo404handler_activation_logic(){
    if(!class_exists('woocommerce')){
        add_action('admin_notices','nineamdev_show_woocommerce_not_avaialble_message');
        add_action('admin_init','nineamdev_deactivate_plugin');

    }

}

function nineamdev_deactivate_plugin(){
    deactivate_plugins(plugin_basename(__FILE__));
}

function nineamdev_show_woocommerce_not_avaialble_message(){
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e( '<strong>404 to 301 for products url on WooCommerce</strong> requires Woocommerce to be installed and activated! <strong>Plugin deactivated</strong>!', '9amdev-woo404handler' ); ?></p>
    </div>
    <?php
}


add_action('init','nineamdev_trigger_404_handler');

function nineamdev_trigger_404_handler(){
    add_action( 'template_redirect','nineamdev_handle_404' );
}


//redirect function
function nineamdev_redirect(){
    global $wpdb;

    //Note: Strip these words
    $search = array('product','category','product-category','shop');
    $params = array();
    $goto = '';
    
    //getting the request_uri 
    $url = strtolower(urldecode(sanitize_url($_SERVER['REQUEST_URI'])));
    $url = str_replace($search, "", $url);
    
    $url_items = explode("/", $url);
    
    if(is_array($url_items) && count($url_items) > 0){
        foreach($url_items as $item){
            if(!empty($item)){
                
                //Now strip each of them for possible more params
                if(strlen($item) > 3){
                    $item_arr = explode("-", $item);
                    
                    if(count($item_arr) > 0){
                        foreach($item_arr as $item){
                            array_push($params, $item);
                        }
                    }
                }
            }
        }
    }
    
    if(count($params) > 0 ){
        
        //Remove duplicates
        $params = array_unique($params);
        
        //Just take first three sets if available
        $search_query = "SELECT ID FROM {$wpdb->prefix}posts WHERE ( post_name LIKE '%{$params[0]}%' AND ( post_status='publish' AND post_type='product' )) ";
        
        if(count($params) > 1){
            foreach($params as $key => $param){
                if($key == 0){
                    continue;
                }
                $search_query .= " OR ( post_name LIKE '%".$param."%' AND (post_status='publish' AND post_type='product' )) ";
            } 
        }                             
             
        $search_query .= " LIMIT 1";
        
        
        
        $results = $wpdb->get_results($search_query);
        
        if(is_array($results) &&  count($results) > 0){
            //We have found match
            $goto = nineamdev_set_goto_product($results);
            
        }else{
            //No match on post_name, let's try post_title 
            
            $search_query = "SELECT ID FROM {$wpdb->prefix}posts WHERE ( post_name LIKE '%{$params[0]}%' AND ( post_status='publish' AND post_type='product' )) ";
        
            if(count($params) > 1){
                foreach($params as $key => $param){
                    if($key == 0){
                        continue;
                    }
                    $search_query .= " OR ( post_name LIKE '%".$param."%' AND (post_status='publish' AND post_type='product' )) ";
                } 
            }                             
                 
            $search_query .= " LIMIT 1";
            
            $results = $wpdb->get_results($search_query);
            
            
            if(is_array($results) && count($results) > 0){
                //We have found match
                $goto = nineamdev_set_goto_product($results);
                
                 //echo 'Found Second';
                
            }else{
                //Try finding in the category now 
                $term = false;
               foreach($params as $param){
                   $term = get_term_by('name', $param,'product_cat');
                   
                   if(!$term){
                       $term = get_term_by('slug', $param,'product_cat');
                       
                       if(!$term){
                           continue;
                       }else{
                           break;
                       }
                   }else{
                       break;
                   }
                   
               }
                //var_dump(gettype($term));
    
               if($term){
                   //We have found something, get the term 
                   $goto = get_term_link($term);
                   
               }else{
                   
                   //echo 'test';
                   $goto = get_site_url();
               }
                                  
            }
        }
        
    }else {
        
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


function nineamdev_set_goto_product($id_arr) {
    
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
    
    return $goto;
}

function nineamdev_handle_404(){
    // Only if we can.
    if ( ! is_404() || is_admin() ) {
        return;
    }

    // Let's try folks.
    try {

        nineamdev_redirect();
        exit();

    } catch ( Exception $ex ) {
        // should 
    }
}
