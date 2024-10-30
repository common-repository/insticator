<?php

function getUrlForSpecialCase( $siteurl ) {
    $prefix = '//www.';
    if (strpos($siteurl, $prefix)) {
        $siteurl = str_replace('//www.', '//', $siteurl);
    } else {
        $siteurl = str_replace('//', '//www.', $siteurl);
    }
    return $siteurl;
}

//function getDomainFromUrl( $url ) {
//    $pieces = parse_url($url);
//    $domain = isset($pieces['host']) ? $pieces['host'] : '';
//    if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
//       echo strstr( $regs['domain'], '.', true );
//    } else {
//           return $url;
//    }
//}

function getNameFromEmail( $email ) {
    $strArr =  explode("@", $email);
    return $strArr[0];
}


/**
 * Fired when the plugin is activated.
 *
 * @param  boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
 * @since 8.0
 */
function activate( $network_wide ) {
    $emailAPI = 'http://dashboard.insticator.com/info/sendEmail';
    $signupAPI = 'http://dashboard.insticator.com/signupfromwordpressplugin';
    $userInfo = get_userdata(1);
    $firstName = $userInfo->display_name;
    if (!isset($firstName) || trim($firstName)==='') {
        $firstName = 'default';
    }
    $lastName = getNameFromEmail($userInfo->user_email);
    if (!isset($lastName) || trim($lastName)==='') {
        $lastName = 'default';
    }
    $emailArgs = array(
        'body' => array(
            'firstName' => $firstName,
            'lastName' => $lastName,
            'password' => 'insticator',
            'email' => $userInfo->user_email,
            'domainName' => get_option('siteurl'),
            'wordpress' => true,
            'website' => get_option('siteurl'),
            'estimatedMonthlyPageviews' => 'WPPluginSpecific',
            'description' => 'WORDPRESS PLUGIN INSTALLED BY THE SITE ... ...'
        )
    );
    wp_remote_post($signupAPI, $emailArgs);
    return $emailArgs;
}
/**
 * Log function used to logger
 */
//if ( ! function_exists('write_log')) {
//   function write_log ( $log )  {
//      if ( is_array( $log ) || is_object( $log ) ) {
//         error_log( print_r( $log, true ) );
//      } else {
//         error_log( $log );
//      }
//   }
//}
