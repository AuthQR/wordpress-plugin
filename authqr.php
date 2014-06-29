<?php

/* 
	Plugin Name: AuthQR
	Plugin URI: http://authqr.com
	Description: Wordpress login with AuthQR.com
	Version: 0.1
	Author: Fernando Falci <falci@falci.me>
	Author URI: http://falci.me
	License: MIT
*/
	
	
class AuthQR {
	static $server_url = "https://authqr.herokuapp.com";
	static $verify_url = "/verify?code=";
	
	function init(){
		wp_register_script('socket.io', self::$server_url . '/socket.io/socket.io.js');
		wp_register_script('authqr-core', self::$server_url . '/jquery.authqr.js', array('jquery', 'socket.io'));
	}
	
	function add_resources_admin($hook){	
		if( 'profile.php' != $hook ){
			return;
		}
		
		wp_enqueue_script( 'authqr-admin', plugins_url( 'js/authqr.admin.js' , __FILE__ ), array('jquery', 'authqr-core') );
	}
	
	function add_resources_login(){
		self::init();          
		wp_enqueue_script( 'authqr-login', plugins_url( 'js/authqr.login.js' , __FILE__ ), array('jquery','authqr-core') );
		wp_enqueue_style( 'authqr-login-form', plugins_url( 'css/authqr.login.css' , __FILE__ ) );
	}
	
	function login_form() {

		?><div class='authqr-form-part authqr-main'>
			<input type="hidden" name="authqr_code" id="authqr_code" />
			<div id="authqr"></div>
		</div><?php
	}
	
	/**
	* Thanks to TBI Infotech: http://wordpress.stackexchange.com/a/151715/28742
	*/
	function check_login($user, $username, $password) {
		global $wpdb;  
		if(!is_user_logged_in() && !empty($_POST['authqr_code'])){
			$auhtqr_user = self::getUserId($_POST['authqr_code']);
			
			$user_id = $wpdb->get_var( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key='authqr_user' AND meta_value='$auhtqr_user'" );
			$user = get_user_by('id', $user_id );

			if ( !is_wp_error( $user ) ){
				wp_clear_auth_cookie();
				wp_set_current_user ( $user->ID );
				wp_set_auth_cookie  ( $user->ID );

				wp_safe_redirect( user_admin_url() );
				exit();
			}
			return $user;
		}
	}

	function user_form($user){ ?>
	
		<p><input type="hidden" id="authqr_code" name="authqr_code" value="not_changed" /></p>
		<h3><?php _e('AuthQR', 'AuthQR'); ?></h3>
		<?php 
		
			$error = get_user_meta($user, 'authqr_error', true);
			delete_user_meta($user, 'authqr_error');
			
			if($error != ""){ ?>
				<div id="authqr_error" class="error"><?php echo $error ?></div>
			<?php } ?>
		<table class="form-table">
			<tr>
				<th scope="row" rowspan="2">
					<?php _e('AuthQR', 'AuthQR'); ?>
				</th>
				<td id="authqr_status" data-status-ok="<?php _e('Código recebido com sucesso!', 'AuthQR'); ?>"><?php _e('Aguardando...', 'AuthQR'); ?></td>
			</tr>
			<tr>
				<td id="authqr_img"></td>
			</tr>
		</table><?php
	
	}
	
	function getUserId($code){
		$str = file_get_contents(self::$server_url . self::$verify_url . $code);
		$json = json_decode($str);
	
		if(isset($json->error)){
			throw new Exception($json->error->message,$json->error->code);
		}
	
		return $json->user;
	}

	function update($user_id) {
		if ( !current_user_can('edit_user', $user_id) || !isset($_POST['authqr_code']) || $_POST['authqr_code'] == "not_changed" ){
			return false;
		}
		
		try{
			$user = self::getUserId($_POST['authqr_code']);
			update_user_meta($user_id, 'authqr_user', $user);
			delete_user_meta($user_id, 'authqr_error');
			
		} catch(Exception $e){
			update_user_meta($user_id, 'authqr_error', _e('Erro validar código','AuthQR'));
			
			return new WP_Error($e->getCode(),$e->getMessage());
		}
	}
	
}

// init
add_action( 'admin_init', array('AuthQR', 'init') );

// resources
add_action( 'admin_enqueue_scripts', array('AuthQR', 'add_resources_admin') );
add_action( 'login_enqueue_scripts', array('AuthQR', 'add_resources_login'), 1 );
	
// user form
add_action('show_user_profile', array('AuthQR', 'user_form'));
add_action('edit_user_profile', array('AuthQR', 'user_form'));

// user save
add_action('personal_options_update', array('AuthQR', 'update'));
add_action('edit_user_profile_update', array('AuthQR', 'update'));

// login form
add_action('login_form', array('AuthQR', 'login_form'));

// login post
add_filter('authenticate', array('AuthQR', 'check_login'), 10, 3);