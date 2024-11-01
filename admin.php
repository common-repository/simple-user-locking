<?php
/*
 * Contains all visible admin stuff like e.g. the extra user profile fields.
 */

add_action( 'admin_enqueue_scripts', 'sulock_admin_scripts_and_styles' );

// load admin styles
function sulock_admin_scripts_and_styles($hook) {

    // enqueue only on user edit pages
    if ( !in_array($hook,['user-edit.php','profile.php','users.php']) ) {
        return;
    }

    wp_register_style('sulock_admin_css', plugins_url( 'css/admin.css', __FILE__ ), false, SULOCK_VERSION );
    wp_enqueue_style('sulock_admin_css' );

    wp_register_script('sulock_admin_js', plugins_url( 'js/admin.js', __FILE__ ), ['jquery'],SULOCK_VERSION,true);
    wp_enqueue_script('sulock_admin_js');
}

// admin user fields
add_action( 'show_user_profile', 'extra_user_profile_fields' );
add_action( 'edit_user_profile', 'extra_user_profile_fields' );

function extra_user_profile_fields( $user ) {

    $user_id = sulock_resempty($_GET,'user_id',0,Sulock\Primitive::INT);

    if($user_id > 0) {
        ?>
        <h3><?php _e("Simple user locking: Options", SULOCK_TEXTDOMAIN); ?></h3>

        <table class="form-table">
            <tr>
                <th><label for="sulock_permanently_locked"><?php _e("Lock user permanently",SULOCK_TEXTDOMAIN); ?></span></label></th>
                <td>
                    <input type="checkbox" name="sulock_permanently_locked" id="sulock_permanently_locked" value="1" <?php echo (intval( get_the_author_meta( 'sulock_permanently_locked', $user->ID )) > 0) ? 'checked="checked"':''; ?> class="regular-text" /><br />
                </td>
                <td class="description">
                    <?php _e("If you check this the user is permanently locked and can not log into the admin dashboard anymore.", SULOCK_TEXTDOMAIN); ?>
                </td>
            </tr>
            <tr>
                <?php
                $temp_lock = get_user_meta($user_id, "sulock_temporarily_locked",true);
                $dateval = "";
                $timeval = "";
                if($temp_lock instanceof DateTime) {
                    $dateval = $temp_lock->format("Y-m-d");
                    $timeval = $temp_lock->format("H:i");
                }
                ?>
                <th><label><?php _e("Lock user temporarily",SULOCK_TEXTDOMAIN); ?></span></label></th>
                <td>
                    <span class="sulock-nowrap">
                    <?php _e("Date:",SULOCK_TEXTDOMAIN); ?>
                        <input type="date" name="sulock_templock_date" id="sulock_templock_date" min="<?= date("Y-m-d") ?>" value="<?= $dateval ?>" />
                    </span>
                    <span class="sulock-nowrap">
                    <?php _e("Time:",SULOCK_TEXTDOMAIN); ?>
                        <input type="time" name="sulock_templock_time" id="sulock_templock_time" value="<?= $timeval  ?>" />
                        <?php _e("o'clock",SULOCK_TEXTDOMAIN); ?>
                    </span>
                    <div id="sulock_remove" class="button-secondary"><?php _e("Remove lock",SULOCK_TEXTDOMAIN); ?></div>
                </td>
                <td class="description">
                    <?php _e("If you check this the user is temporarily locked out of the site and can not log into the admin dashboard until the specified point in time. Must be at least 5 minutes in the future, otherwise the templock is deactivated.", SULOCK_TEXTDOMAIN); ?>
                </td>
            </tr>
        </table>
    <?php }
}


function sulock_save_profile_fields( $user_being_edited_id ) {

    // only real administrators can lock / unlock users
    if ( !current_user_can( 'edit_user',$user_being_edited_id ) || !current_user_can('manage_options') ) {
        return false;
    }

    // administrators can not lock network administrators
    if(is_super_admin($user_being_edited_id) && !is_super_admin(get_current_user_id())) {
        return false;
    }

    // users may never lock / unlock themselves
    if($user_being_edited_id === get_current_user_id()) {
        return false;
    }

    // user is allowed to do it, go ahead

    // permlock check
    $permlock = 0;
    if(isset($_POST['sulock_permanently_locked'])) {
        $permlock = 1;
    }

    if(update_user_meta( $user_being_edited_id, 'sulock_permanently_locked', $permlock)) {
        update_user_meta($user_being_edited_id, 'sulock_permlock_meta',new Sulock\LockMeta());
        if($permlock) {
            sulock_redirected_admin_notice(__('This user has been permanently locked by you.',SULOCK_TEXTDOMAIN),'notice notice-warning');
        } else {
            sulock_redirected_admin_notice(__('This users permanent lock has been removed by you.',SULOCK_TEXTDOMAIN),'notice notice-info');
        }
    }

    // templock check
    $delete_templock = false;

    try {

        $datetime_submitted = new DateTime($_POST['sulock_templock_date'] . " ".$_POST['sulock_templock_time'], sulock_get_timezone());
        $datetime_minfuture = new DateTime('+5 minutes',sulock_get_timezone());

        if($datetime_submitted >= $datetime_minfuture) {
            if(update_user_meta($user_being_edited_id,'sulock_temporarily_locked',$datetime_submitted)) {
                update_user_meta($user_being_edited_id, 'sulock_templock_meta',new Sulock\LockMeta($datetime_submitted));
                sulock_redirected_admin_notice(__('You just have locked this user temporarily.',SULOCK_TEXTDOMAIN),'notice notice-warning');
            }
        } else {
            $delete_templock = true;
        }
    } catch (Exception $e) {

        $delete_templock = true;
    }

    if($delete_templock) {
        if(delete_user_meta($user_being_edited_id,'sulock_temporarily_locked')) {
            sulock_redirected_admin_notice(__('The temporary lock for this user has been removed.',SULOCK_TEXTDOMAIN),'notice notice-info');
        }
        delete_user_meta($user_being_edited_id,'sulock_templock_meta');
    }
}

// add_action( 'personal_options_update', 'save_extra_user_profile_fields' ); // nobody should lock / unlock himself
add_action( 'edit_user_profile_update', 'sulock_save_profile_fields' );


// a simplified function for redirected admin notices
function sulock_redirected_admin_notice($message,$class) {
    add_action('wp_redirect',function($location) use ($message) {
        return add_query_arg( 'sulock_admin_notice', urlencode($message), $location );
    });
    add_action('wp_redirect',function($location) use ($class) {
        return add_query_arg( 'sulock_admin_notice_class', urlencode($class), $location );
    });
}

// display admin notices in case
add_action( 'load-user-edit.php', function(){
    if ( isset( $_GET['sulock_admin_notice'] ) &&  $_GET['sulock_admin_notice'] != "" ) {
        $message = esc_html($_GET['sulock_admin_notice']);
        $class = (isset($_GET['sulock_admin_notice_class']) && $_GET['sulock_admin_notice_class'] != "") ?  esc_attr( $_GET['sulock_admin_notice_class'] ) : "notice notice-info";
        add_action('admin_notices',function() use ($message,$class) {
            printf( '<div class="%1$s"><p>%2$s</p></div>', $class , $message );
        });
    }
} );

/***** For adding this information to the users overview columns *****/
function sulock_modify_user_table( $column ) {
    $column['sulock'] = __('Lock status',SULOCK_TEXTDOMAIN);
    return $column;
}
add_filter( 'manage_users_columns', 'sulock_modify_user_table' );
add_filter( 'wpmu_users_columns', 'sulock_modify_user_table');



function sulock_modify_user_table_row( $val, $column_name, $user_id ) {
    switch ($column_name) {
        case 'sulock' : {

            if(intval(get_user_meta($user_id, "sulock_permanently_locked", true)) > 0) {
                return '<span class="sulock_permlock">' . __("Permanent lock",SULOCK_TEXTDOMAIN) . '</span>';
            }

            $temp_lock = get_user_meta($user_id, "sulock_temporarily_locked",true);

            if(($temp_lock instanceof DateTime) && (new DateTime('now',sulock_get_timezone()) < $temp_lock)) {
                return '<span class="sulock_templock">' . __("Locked until",SULOCK_TEXTDOMAIN).' ' . $temp_lock->format("Y-m-d H:i (e)") . '</span>';
            }

            return '<span class="sulock_nolock">' . __("Not locked",SULOCK_TEXTDOMAIN) . '</span>';
        }
        default:
    }
    return $val;
}
add_filter( 'manage_users_custom_column', 'sulock_modify_user_table_row', 10, 3 );