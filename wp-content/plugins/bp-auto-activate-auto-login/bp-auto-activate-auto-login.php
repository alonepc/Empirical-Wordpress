<?php

/**
 * Plugin Name: BP Auto Activate  Autologin Redirect To Profile On Signup
 * Plugin URI: http://buddydev.com/plugins/bp-auto-activate-auto-login/
 * Description: BuddyPress Auto activate  Autologin Redirect To Profile On Signup, will automatically activate the user account when they signup for a username or for username/blog both. After activating the new user's account, It will automatically make them logged in and then, the new user will be redirected to his/her profile
 * Author: Brajesh Singh
 * Version: 1.4
 * Last Updated: 10th July, 2014
 * Author URI: http://buddydev.com/
 * Network: true
 * License: GPL
 */
class BPDevAccountAutoActivater {

    private static $instance;

    private function __construct() {
        //stop notifications
        
        add_filter( 'wpmu_welcome_notification', '__return_false', 110 ); //5 args,no need to send the clear text password when blog is activated
        add_filter( 'wpmu_welcome_user_notification', '__return_false', 110 ); //5 args,no need to send the clear text password when blog is activated
        add_filter( 'bp_core_signup_send_activation_key', '__return_false', 110 ); //5 args,no need to send the clear text password when blog is activated
        //remove buddypress notifications
        add_action( 'bp_loaded', array( $this, 'remove_bp_filters' ), 100 );


        add_filter( 'wpmu_signup_blog_notification', array( $this, 'activate_on_blog_signup' ), 10, 7 );
        // User signup - autoactivate
        add_filter( 'wpmu_signup_user_notification', array( $this, 'activate_user_for_wpms' ), 10, 4 );
        //activation for single wp
        add_action( 'bp_core_signup_user', array( $this, 'active_user_for_wps' ), 11, 5 );
    }

    //remove filters which notifies users
    public static function get_instance() {
        
        if ( !isset( self::$instance ) )
            self::$instance = new self();
        
        return self::$instance;
    }

    public function remove_bp_filters() {
        
        remove_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 ); //remove bp user notification for activating account
        remove_filter( 'wpmu_signup_blog_notification', 'bp_core_activation_signup_blog_notification', 1, 7 ); //remove bp blog notification
        remove_filter( 'wpmu_signup_user_notification', 'bp_core_activation_signup_user_notification', 1, 4 );
    }

    /**
     * makes a user logged in and redirect to his/her profile
     */
    public static function login_redirect( $user_login, $password ){
        
        $creds = array( 'user_login' => $user_login, 'user_password' => $password );
        //make the user login
        $user = wp_signon( $creds );


        if ( !is_wp_error( $user ) )//if the signup was success full.redirect to the membership page
            bp_core_redirect( apply_filters( 'bpdev_autoactivate_redirect_url', bp_core_get_user_domain( $user->ID ), $user->ID ) );
        
    }
    
    /**
     * Update xprofile fields from the signup meta data
     * 
     * @param type $user_id
     * @param type $signup
     */
    public static function update_profile_fields( $user_id, $signup ){
        
         /* Set any profile data */
        if (function_exists('xprofile_set_field_data')) {
            if (!empty($signup['meta']['profile_field_ids'])) {
                $profile_field_ids = explode(',', $signup['meta']['profile_field_ids']);

                foreach ($profile_field_ids as $field_id) {
                    $current_field = $signup['meta']["field_{$field_id}"];

                    if (!empty($current_field))
                        xprofile_set_field_data($field_id, $user_id, $current_field);
                }
            }
        }

    }
    
    /**
     * Activates User account on Multisite based on the given key
     * 
     * 
     */
    public static function ms_activate_account( $key ){
        
        //if doing ajax, return
        if( defined( 'DOING_AJAX' ) )
            return false;
        
        //mimic bp activation
        $bp = buddypress();
        
        $signup = apply_filters( 'bp_core_activate_account', wpmu_activate_signup( $key ) );

        /* If there was errors, add a message and redirect */
        if ( $signup->errors ) {
            
            bp_core_add_message( __( 'There was an error activating your account, please try again.', 'buddypress' ), 'error' );
            bp_core_redirect( $bp->root_domain . '/' . bp_get_activate_slug() );
            //send the activation mail in this case
        }

        $user_id = $signup['user_id'];
        //should we pass password as a param instead of the dependency here?
        
        $pass = $_POST['signup_password'];

        $ud = get_userdata( $user_id );

        $data = array( 'user_login' => $ud->user_login, 'user_email' => $ud->user_email, 'user_pass' => $pass, 'ID' => $user_id, 'display_name' => bp_core_get_user_displayname( $user_id ) );
        //update password
        if ( is_multisite() )
            wp_update_user( $data );

       self::update_profile_fields( $user_id, $signup );

        do_action( 'bp_core_activated_user', $user_id, $key, $signup );     //let bp handle the new user registerd activity
        //do_action( 'bp_core_account_activated', &$signup, $_GET['key'] );
        
        bp_core_add_message( __( 'Your account is now active!' ) );

        $bp->activation_complete = true;

        self::login_redirect( $ud->user_login, $pass );
        
    }
    
    public function ms_activate_on_blog_signup(){
        
    }
    
    /**
     * Activates User account on multisite 
     * 
     * @param type $user
     * @param type $user_email
     * @param type $key
     * @param type $meta
     * @return type
     */
    function activate_user_for_wpms( $user, $user_email, $key, $meta ) {
        
       return self::ms_activate_account( $key );
    }

    /**
     * Activates the User account when a User signs up for a blog
     * 
     * @param type $domain
     * @param type $path
     * @param type $title
     * @param type $user
     * @param type $user_email
     * @param type $key
     * @param type $meta
     * @return type
     * 
     * 
     */
    function activate_on_blog_signup($domain, $path, $title, $user, $user_email, $key, $meta) {
        
        return self::ms_activate_account( $key );
        
    }

    //for single user wordpress
    function active_user_for_wps( $user_id, $user_login, $user_password, $user_email, $usermeta ) {
        global $bp, $wpdb;
        
        $user = null;
        
        if( defined('DOING_AJAX') )
            return $user_id;
        
        
        if ( is_multisite() )
            return $user_id; //do not proceed for mu

        
        $signups = BP_Signup::get( array('user_login'=> $user_login ) );

      
        $signups = $signups['signups'];
       
        
        if( !$signups )
            return false;
        
        //if we are here, just popout the array
        $signup = array_pop( $signups );
         
       		// password is hashed again in wp_insert_user
		$password = wp_generate_password( 12, false );

		$user_id = username_exists( $signup->user_login );

        $key = $signup->activation_key;
        
        if( !$key )
            $key = bp_get_user_meta( $user_id, 'activation_key', true  );
        
		// Create the user
		if ( ! $user_id ) {//this should almost never happen
			$user_id = wp_create_user( $signup->user_login, $password, $signup->user_email );

		// If a user ID is found, this may be a legacy signup, or one
		// created locally for backward compatibility. Process it.
		} elseif ( $key == wp_hash( $user_id ) ) {
			// Change the user's status so they become active
			if ( ! $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->users} SET user_status = 0 WHERE ID = %d", $user_id ) ) ) {
				return new WP_Error( 'invalid_key', __( 'Invalid activation key', 'buddypress' ) );
			}

			bp_delete_user_meta( $user_id, 'activation_key' );

			$member = get_userdata( $user_id );
			$member->set_role( get_option('default_role') );

			$user_already_created = true;

		} else {
			$user_already_exists = true;
		}

		if ( ! $user_id ) {
			return new WP_Error( 'create_user', __( 'Could not create user', 'buddypress' ), $signup );
		}

		// Fetch the signup so we have the data later on
		$signups = BP_Signup::get( array(
			'activation_key' => $key,
		) );

		$signup = isset( $signups['signups'] ) && ! empty( $signups['signups'][0] ) ? $signups['signups'][0] : false;

		// Activate the signup
		BP_Signup::validate( $key );

		if ( isset( $user_already_exists ) ) {
			return new WP_Error( 'user_already_exists', __( 'That username is already activated.', 'buddypress' ), $signup );
		}

		// Set up data to pass to the legacy filter
		$user = array(
			'user_id'  => $user_id,
			'password' => $signup->meta['password'],
			'meta'     => $signup->meta,
		);

		// Notify the site admin of a new user registration
		wp_new_user_notification( $user_id );

		
        wp_cache_delete('bp_total_member_count', 'bp');

       /* Add a last active entry */
       bp_update_user_last_activity( $user_id );

       do_action( 'bp_core_activated_user', $user_id, $key, $user );
		
          


        bp_core_add_message(__('Your account is now active!'));

        $bp->activation_complete = true;
        xprofile_sync_wp_profile();
        //$ud = get_userdata($signup['user_id']);

        self::login_redirect( $user_login,  $user_password );

       
        //will never reach here anyway
        return $user_id;
    }

}

BPDevAccountAutoActivater::get_instance();
