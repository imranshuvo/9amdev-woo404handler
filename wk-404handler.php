<?php 
/**
 * Plugin Name: 404 to 301 for Woocommerce products
 * Description: This plugin redirects any 404 on woocommerce products url to it's similar product url or the related category url. If similar url or category url is not found, it will redirect to the homepage
 * Plugin URI : https://9amdev.com
 * Version: 1.1
 * Author: 9amdev
 * Author URI: https://9amdev.com
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
    $params = array();
    $goto = '';
    
    //getting the request_uri 
    $url = strtolower(urldecode($_SERVER['REQUEST_URI']));
    $url = str_replace($search, "", $url);
    
    $url_items = explode("/", $url);
    
    // echo '<pre>';
    // print_r($url_items);
    // echo '</pre>';
    
    // wp_die();
    
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
    
    //  echo '<pre>';
    // print_r($params);
    // echo '</pre>';
    
    // wp_die();
    
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
            $goto = wk_set_goto_product($results);
            
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
                $goto = wk_set_goto_product($results);
                
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
                var_dump(gettype($term));
    
               if($term){
                   //We have found something, get the term 
                   $goto = get_term_link($term);
                   
               }else{
                   
                   echo 'test';
                   $goto = get_site_url();
               }
               
               //wp_die();
                   
            }
        }
        
    }else {
        
        $goto = get_site_url();
    }
    
    // echo '<pre>';
    // print_r($goto);
    // echo '</pre>';

    // wp_die();

    if (!empty($goto)) {
        //Note: Giving Google a 301 response for updating the URL in their DB
        wp_redirect($goto, 301);
    }else {
        //Note: Give 404 error on broken links to files
        http_response_code(404);
    }
}


function wk_set_goto_product($id_arr) {
    
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
