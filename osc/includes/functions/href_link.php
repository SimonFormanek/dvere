<?php
/*
  $Id$

  CE Phoenix, E-Commerce made Easy
  https://phoenixcart.org

  Copyright (c) 2021 Phoenix Cart

  Released under the GNU General Public License
*/

////
// Ultimate SEO URLs v2.2d
// The HTML href link wrapper function
 function tep_href_link($page = '', $parameters = '', $connection = 'SSL', $add_session_id = true, $search_engine_safe = true) {
   global $seo_urls;                
   if ( !is_object($seo_urls) ){
    if ( !class_exists('SEO_URL') ){
     include_once('includes/classes/seo.class.php');
    }
    global $languages_id;
    $seo_urls = new SEO_URL($languages_id);
   }
   return $seo_urls->href_link($page, $parameters, $connection, $add_session_id);
 }
