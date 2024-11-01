<?php

function wp_health_check_activation(){

global $wpdb;

add_option('wphc_version', wphc_version);

}

function wphc_getip(){
	if(isset($_SERVER["REMOTE_ADDR"])){
		return $_SERVER["REMOTE_ADDR"];
	}elseif(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
		return $_SERVER["HTTP_X_FORWARDED_FOR"];
	}elseif(isset($_SERVER["HTTP_CLIENT_IP"])){
		return $_SERVER["HTTP_CLIENT_IP"];
	}
}

function wphc_selectquery($query){
	global $wpdb;
	
	$result = $wpdb->get_results($query, 'ARRAY_A');
	return current($result);
}

function wphc_sanitize_variables($variables = array()){
	
	if(is_array($variables)){
		foreach($variables as $k => $v){
			$variables[$k] = trim($v);
			$variables[$k] = escapeshellcmd($v);
			$variables[$k] = mysql_real_escape_string($v);
		}
	}else{
		$variables = mysql_real_escape_string(escapeshellcmd(trim($variables)));
	}
	
	return $variables;
}

function wphc_valid_ip($ip){

	if(!ip2long($ip)){
		return false;
	}	
	return true;
}

function wphc_report_error($error = array()){

	if(empty($error)){
		return true;
	}
	
	$error_string = '<b>The below issues are critical and should be addressed immediately :</b> <br />';
	
	foreach($error as $ek => $ev){
		$error_string .= '* '.$ev.'<br />';
	}
	
	echo '<div id="message" class="error"><p>'
					. __($error_string, 'wp-health-check')
					. '</p></div>';
}

function wphc_report_notice($notice = array()){

	global $wp_version;
	
	if(empty($notice)){
		return true;
	}
	
	// Which class do we have to use ?
	if(version_compare($wp_version, '3.8', '<')){
		$notice_class = 'updated';
	}else{
		$notice_class = 'updated';
	}
	
	$notice_string = '<b>The below issues are not critical but you should fix them :</b> <br />';
	
	foreach($notice as $ek => $ev){
		$notice_string .= '* '.$ev.'<br />';
	}
	
	echo '<div id="message" class="'.$notice_class.'"><p>'
					. __($notice_string, 'wp-health-check')
					. '</p></div>';
}

function objectToArray($d){
  if(is_object($d)){
    $d = get_object_vars($d);
  }
  
  if(is_array($d)){
    return array_map(__FUNCTION__, $d); // recursive
  }elseif(is_object($d)){
    return objectToArray($d);
  }else{
    return $d;
  }
}

function wphc_r_print($array){
	echo '<pre>';
	print_r($array);
	echo '</pre>';
}

function wp_health_check_deactivation(){

global $wpdb;

delete_option('wphc_version'); 

}

?>