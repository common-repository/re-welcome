<?php
/*
Plugin Name: Re-Welcome
Description: Re-Send welcome email from the Users list
Version:     1.1.0
Author:      Andrew J Klimek
Author URI:  https://andrewklimek.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Re-Welcome is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Re-Welcome is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Re-Welcome. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

function rewelcome_new_user_notification( $uid ) {
    
	// wp_new_user_notification was totally changed in 4.3.  This is the old method
	if ( version_compare( $GLOBALS['wp_version'], '4.3', '<' ) ) {
		
		$key = wp_generate_password( 20, false );
		wp_update_user(array('ID' => $uid, 'user_pass' => $key));
		wp_new_user_notification($uid, $key);
	
	} else {// New method
		wp_new_user_notification( $uid, null, 'user' );
	}
}

function rewelcome_load_users() {
    
	if ( !empty( $_REQUEST['rewelcome'] ) ) {
	    
	    add_action( 'admin_notices', function() { echo '<div class="updated">Welcome email sent</div>'; });

	    
	} elseif ( !empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'rewelcome' ) && !empty( $_REQUEST['user'] ) ) {
		
		$uid = $_REQUEST['user'];
		
		rewelcome_new_user_notification( $uid );
		
		add_action( 'admin_notices', function() { echo '<div class="updated">Welcome email sent</div>'; });
	}
}
add_action( 'load-users.php', 'rewelcome_load_users' );

function rewelcome_row_action($actions, $user_object) {
	// if ( $user_object->has_cap( 'manage_options' ) ) return $actions;// Don't show for admins (or anyone with manage_options capability)
	$nonce = wp_create_nonce( 'rewelcome' ); 
	$link = admin_url( "users.php?user={$user_object->ID}&_wpnonce=$nonce" );
	$actions['rewelcome'] = "<a href='$link'>Resend Welcome Email</a>";

	return $actions;
}
add_filter( 'user_row_actions', 'rewelcome_row_action', 10, 2 );

function rewelcome_bulk_action( $actions = array() ) {
    $actions['rewelcome'] = 'Resend Welcome Email';
    return $actions;
}
add_filter( 'bulk_actions-users', 'rewelcome_bulk_action' );

function rewelcome_handle_bulk_actions( $redirect_to, $doaction, $userids ) {
    
    if ( 'rewelcome' !== $doaction ) return;
    
    add_action('phpmailer_init', function($mail){ $mail->SMTPKeepAlive = true; });

    foreach( $userids as $uid ) {
        
        rewelcome_new_user_notification( $uid );
        
    }
    
    return add_query_arg( 'rewelcome', '1', $redirect_to );
    
}
add_filter( 'handle_bulk_actions-users', 'rewelcome_handle_bulk_actions', 10, 3 );
