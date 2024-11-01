<?php
/**
 * @package wp-health-check
 * @version 1.1
 */
/*
Plugin Name: WP Health Check
Plugin URI: http://wordpress.org/extend/plugins/wp-health-check/
Description: WP Health Check scans your WordPress installation and reports any security issues with your site. It will also recommend you the solution to ovecome the issues. The purpose of this plugin is to keep your WordPress installation Safe and Secure from hackers.
Version: 1.1
Author: Brijesh Kothari
Author URI: http://www.wpinspired.com/
License: GPLv3 or later
*/

/*
Copyright (C) 2013  Brijesh Kothari (email : admin@wpinspired.com)
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly !';
	exit;
}

function standard_path($path) {
	return str_replace('\\', '/', $path);
}

$current_path = standard_path(dirname(__file__));

if(file_exists($current_path.'/inc/functions.php')){
	include_once($current_path.'/inc/functions.php');
}else{
	echo 'The functions file is missing !';
	exit;	
}

define('wphc_version', '1.1');

// Ok so we are now ready to go
register_activation_hook( __FILE__, 'wp_health_check_activation');
add_action('admin_menu', 'wp_health_check_admin_menu');

function wp_health_check_admin_menu() {
	global $wp_version;

	// Modern WP?
	if (version_compare($wp_version, '3.0', '>=')) {
	    add_options_page('WP Health Check', 'WP Health Check', 'manage_options', 'wp-health-check', 'wp_health_check_option_page');
	    return;
	}

	// Older WPMU?
	if (function_exists("get_current_site")) {
	    add_submenu_page('wpmu-admin.php', 'WP Health Check', 'WP Health Check', 9, 'wp-health-check', 'wp_health_check_option_page');
	    return;
	}

	// Older WP
	add_options_page('WP Health Check', 'WP Health Check', 9, 'wp-health-check', 'wp_health_check_option_page');
}

function wp_health_check_option_page(){

	global $wpdb, $table_prefix;
	
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}

	/* Make sure post was from this page */
	if(count($_POST) > 0){
		check_admin_referer('wp-health-check-options');
	}
	
	// Are we using the latest version of WordPress ?
	$get_updates = current(objectToArray(get_core_updates()));
	//wphc_r_print($get_updates);
	if($get_updates['response'] != 'latest'){
		$error[] = 'You are not using the latest version of <b>WordPress</b>. Please upgrade to the latest version of WordPress. Click <a href="update-core.php">here to update now</a>.';
	}
	
	// Are the plugins updated ?
	$plugin_updates = get_plugin_updates();
	
	if(!empty($plugin_updates)){
		foreach($plugin_updates as $pk => $pv){
			$plugin_updates[$pk] = objectToArray($pv);
			$error[] = 'You are not using the latest version of <b>'.$plugin_updates[$pk]['Name'].'</b> plugin. Please upgrade to the latest version of '.$plugin_updates[$pk]['Name'].'. Click <a href="plugins.php?plugin_status=upgrade">here to update now</a>.';
		}
	}
	//wphc_r_print($plugin_updates);
	
	// Are the themes updated ?
	$theme_updates = get_theme_updates();
	
	if(!empty($theme_updates)){
		foreach($theme_updates as $tk => $tv){
			$theme_updates[$tk] = objectToArray($tv);
		}
		if(count($theme_updates) > 0){
			$error[] = 'You are using one or more outdated theme(s). Please upgrade to the latest version. Click <a href="themes.php">here to update now</a>.';
		}
	}
	//wphc_r_print($theme_updates);
	
	$root_path = dirname(dirname(dirname(__file__)));
		
	// Can the contents be listed ?
	if(!file_exists($root_path.'/uploads/index.php') && !file_exists($root_path.'//uploads/index.html') && !file_exists($root_path.'/uploads/index.htm') && !file_exists($root_path.'/uploads/.htaccess')){
			$error[] = 'The contents of your <strong>uploads</strong> directory can be listed. Please create an empty <strong>index.html</strong> file in the <strong>uploads</strong> directory.';
	}
	
	// Can the contents be listed ?
	if(!file_exists($root_path.'/themes/index.php') && !file_exists($root_path.'/themes/index.html') && !file_exists($root_path.'/themes/index.htm') && !file_exists($root_path.'/themes/.htaccess')){
			$error[] = 'The contents of your <strong>themes</strong> directory can be listed. Please create an empty <strong>index.html</strong> file in the <strong>themes</strong> directory.';
	}
	
	// Can the contents be listed ?
	if(!file_exists($root_path.'/plugins/index.php') && !file_exists($root_path.'/plugins/index.html') && !file_exists($root_path.'/plugins/index.htm') && !file_exists($root_path.'/plugins/.htaccess')){
			$error[] = 'The contents of your <strong>plugins</strong> directory can be listed. Please create an empty <strong>index.html</strong> file in the <strong>plugins</strong> directory.';
	}
	
	// Get a list of plugins
	$all_plugins = get_plugins();
	//wphc_r_print($all_plugins);
	
	if(!empty($all_plugins)){
		foreach($all_plugins as $ak => $av){
			if(is_plugin_inactive($ak)){
				$inactive_plugins[$av['Name']] = $av;
			}
		}
	}
	
	// Are there any inactive plugins ?
	if(!empty($inactive_plugins)){
		$plural = 0;
		if(count($inactive_plugins) > 1){
			$plural = 1;
		}
		
		$notice[] = 'The plugin'.(!empty($plural) ? 's' : '').' <strong>'.implode(', ', array_keys($inactive_plugins)).'</strong> '.(!empty($plural) ? 'are' : 'is').' inactive. If you are not using '.(!empty($plural) ? 'these' : 'this').' plugins please uninstall '.(!empty($plural) ? 'them' : 'it').'. Click <a href="plugins.php?plugin_status=inactive">here to uninstall now</a>.';
		
	}
	
	$harmful_users = get_userdatabylogin('admin');
	//wphc_r_print($harmful_users);
	if(!empty($harmful_users)){
		$notice[] = 'You are using a user with username <b>admin</b>. We recommend to change the username to something else.';
	}
	
	if(trim($table_prefix) == 'wp_'){
		$notice[] = 'You are using a common database prefix <b>wp_</b>. We recommend to change the database prefix to something else.';
	}
	
	//$error = $notice = '';
	
	if(!empty($error)){
		wphc_report_error($error);	
	}
	
	if(!empty($notice)){
		wphc_report_notice($notice);	
	}
	
	?>
	<div class="wrap">
	  <h2><?php echo __('WP Health Check Settings','wp-health-check'); ?></h2>
	  
	</div>	
	<?php
	
	if(empty($error) && empty($notice)){
		echo '<h2>Congratulations, your installation has passed all the security checks.</h2>';
	}
	
	echo '<br /><br /><br /><br /><hr />
	WP Health Check is developed by <a href="http://wpinspired.com" target="_blank">WP Inspired</a>. 
	You can report any bugs <a href="http://wordpress.org/support/plugin/wp-health-check" target="_blank">here</a>. 
	You can provide any valuable feedback <a href="http://www.wpinspired.com/contact-us/" target="_blank">here</a>.';
}	

// Sorry to see you going
register_uninstall_hook( __FILE__, 'wp_health_check_deactivation');

?>
