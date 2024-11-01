<?php
/**
 * Plugin Name: Simple User Locking
 * Conbributors: blackbam
 * Requires at least: 4.9
 * Requires PHP: 7.0
 * Stable tag: 1.0.1
 * Version: 1.0.1
 * Plugin URI: https://www.blackbam.at/
 * Description: Prevent users (like e.g. ex-employees, rule breakers or spamers) from logging into your WordPress installation for a certain timeframe or permanently. With advanced multisite support.
 * Author: David Stöckl
 * Text Domain: sulock
 * Domain Path: /languages/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.de.html
 * Tags: user, lock, security, management, protection, community, multisite
 */

define('SULOCK_VERSION',9);
define('SULOCK_TEXTDOMAIN','sulock');

// try to load localization
$languages_loaded = load_plugin_textdomain(SULOCK_TEXTDOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages');

if (!$languages_loaded) {
    $languages_loaded = load_muplugin_textdomain(SULOCK_TEXTDOMAIN, '/languages/');
}


/**
 * WordPress implements timezones in a really stupid way (don't ask). This gives you the correct Timezone for the site.
 *
 * @return DateTimeZone: The DateTimeZone of the site
 */
function sulock_get_timezone(): DateTimeZone {
    $timezone_string = get_option( 'timezone_string' );
    if ( ! empty( $timezone_string ) ) {
        return new DateTimeZone( $timezone_string );
    }
    $offset  = get_option( 'gmt_offset' );
    $hours   = (int) $offset;
    $minutes = abs( ( $offset - (int) $offset ) * 60 );
    $offset  = sprintf( '%+03d:%02d', $hours, $minutes );
    return new DateTimeZone( $offset );
}


/* Requires */
require_once(plugin_dir_path(__FILE__) . "Primitive.php");
require_once(plugin_dir_path(__FILE__) . "helpers.php");
require_once(plugin_dir_path(__FILE__) . "LockMeta.php");
require_once(plugin_dir_path(__FILE__) . "admin.php");


/**
 * This is where the actual logic happens. We hook into the authenticate filter and if a user was locked,
 * we prevent the user from logging in.
 *
 * @param $user
 * @param $username
 * @return WP_Error
 */
function chk_active_user($user, $username) {

    if($user && !is_wp_error($user)) {
        $user_data = $user->data;
        $user_id = $user_data->ID;

        if(intval(get_user_meta($user_id, "sulock_permanently_locked", true)) > 0) {
            return new WP_Error('sulock_account_disabled', __('Sorry, but your account has been locked permanently. In case you think this is a mistake, please contact the site administrators.',SULOCK_TEXTDOMAIN));
        }

        $temp_lock = get_user_meta($user_id, "sulock_temporarily_locked",true);

        if($temp_lock instanceof DateTime) {
            if(new DateTime('now',sulock_get_timezone()) < $temp_lock) {
                return new WP_Error('sulock_account_temporary_locked', sprintf(__('Sorry, but your account has been temporarily locked. It will be unlocked on %s. In case you think this is a mistake, please contact the site administrators.',SULOCK_TEXTDOMAIN),$temp_lock->format("Y-m-d H:i (e)")));
            } else if($temp_lock > 1) {
                delete_user_meta($user_id,"sulock_temporarily_locked"); // cleanup
                delete_user_meta($user_id,'sulock_templock_meta');
            }
        }
    }
    return $user;
}

add_filter('authenticate', 'chk_active_user', 100, 2);


/** If a logged in user is locked, lock him out on any possible action */
add_action('admin_init','sulock_perform_logout_if_locked');

function sulock_perform_logout_if_locked() {
    if ( is_user_logged_in() && !wp_doing_ajax()) {
        if(intval(get_user_meta(get_current_user_id(), "sulock_permanently_locked", true)) > 0) {

        }

        $temp_lock = get_user_meta(get_current_user_id(), "sulock_temporarily_locked",true);

        if($temp_lock instanceof DateTime) {
            if(new DateTime('now',sulock_get_timezone()) < $temp_lock) {
                wp_logout();
                wp_die(
                    sprintf(__('Sorry, but your account has been temporarily locked. It will be unlocked on %s. In case you think this is a mistake, please contact the site administrators.',SULOCK_TEXTDOMAIN),$temp_lock->format("Y-m-d H:i (e)")),
                    __("Account has been temporarily locked",SULOCK_TEXTDOMAIN)
                );
            } else if($temp_lock > 1) {
                delete_user_meta(get_current_user_id(),"sulock_temporarily_locked"); // cleanup
                delete_user_meta(get_current_user_id(),'sulock_templock_meta');
            }
        }
    }
}