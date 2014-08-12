<?php
/*
WP-SpamShield Dynamic JS File
Version: 1.4.7
*/

// Security Sanitization - BEGIN
$id='';
if ( !empty( $_GET ) || preg_match ( "~\?~", $_SERVER['REQUEST_URI'] ) ) {
	header('HTTP/1.1 403 Forbidden');
	die('ERROR: This resource will not function with a query string. Remove the query string from the URL and try again.');
	}
if ( !empty( $_SERVER['REQUEST_METHOD'] ) ) { $wpss_request_method = $_SERVER['REQUEST_METHOD']; } else { $wpss_request_method = getenv('REQUEST_METHOD'); }
if ( empty( $wpss_request_method ) ) { $wpss_request_method = ''; }
if ( !empty( $_POST ) || preg_match( "~^(POST|TRACE|TRACK|DEBUG|DELETE)$~", $wpss_request_method ) ) {
	header('HTTP/1.1 405 Method Not Allowed');
	die('ERROR: This resource does not accept requests of that type.');
	}
// Security Sanitization - END

// Uncomment to use:
$wpss_js_start_time = spamshield_microtime_js();

// SESSION CHECK AND FUNCTIONS - BEGIN
$wpss_session_test = @session_id();
if( empty($wpss_session_test) && !headers_sent() ) {
	@session_start();
	global $wpss_session_id;
	$wpss_session_id = @session_id();
	}

$wpss_server_ip_nodot = preg_replace( "~\.~", "", spamshield_get_server_addr_js() );
if ( !defined( 'WPSS_HASH_ALT' ) ) { $wpss_alt_prefix = spamshield_md5_js( $wpss_server_ip_nodot ); define( 'WPSS_HASH_ALT', $wpss_alt_prefix ); }
if ( !defined( 'WPSS_SITE_URL' ) && !empty( $_SESSION['wpss_site_url_'.WPSS_HASH_ALT] ) ) {
	$wpss_site_url 		= $_SESSION['wpss_site_url_'.WPSS_HASH_ALT];
	$wpss_plugin_url	= $_SESSION['wpss_plugin_url_'.WPSS_HASH_ALT];
	define( 'WPSS_SITE_URL', $wpss_site_url );
	}

if ( defined( 'WPSS_SITE_URL' ) && !defined( 'WPSS_HASH' ) ) { 
	$wpss_hash_prefix = spamshield_md5_js( WPSS_SITE_URL ); define( 'WPSS_HASH', $wpss_hash_prefix ); 
	}
elseif ( !empty( $_SESSION ) && !empty( $_COOKIE ) && !defined( 'WPSS_HASH' ) ) {
	//$wpss_cookies = $_COOKIE;
	foreach( $_COOKIE as $ck_name => $ck_val ) {
		if ( preg_match( "~^comment_author_([a-z0-9]{32})$~i", $ck_name, $matches ) ) { define( 'WPSS_HASH', $matches[1] ); break; }
		}
	}
// SESSION CHECK AND FUNCTIONS - END

if ( defined( 'WPSS_HASH' ) && !empty( $_SESSION )  ) {
	// IP, PAGE HITS, PAGES VISITED HISTORY - BEGIN
	// Initial IP Address when visitor first comes to site
	$key_pages_hist 		= 'wpss_jscripts_referers_history_'.WPSS_HASH;
	$key_hits_per_page		= 'wpss_jscripts_referers_history_count_'.WPSS_HASH;
	$key_total_page_hits	= 'wpss_page_hits_js_'.WPSS_HASH;
	$key_ip_hist 			= 'wpss_jscripts_ip_history_'.WPSS_HASH;
	$key_init_ip			= 'wpss_user_ip_init_'.WPSS_HASH;
	$current_ip 			= $_SERVER['REMOTE_ADDR'];
	if ( empty( $_SESSION[$key_init_ip] ) ) { $_SESSION[$key_init_ip] = $current_ip; }
	// IP History - Lets see if they change IP's
	if ( empty( $_SESSION[$key_ip_hist] ) ) { $_SESSION[$key_ip_hist] = array(); $_SESSION[$key_ip_hist][] = $current_ip; }
	if ( $current_ip != $_SESSION[$key_init_ip] ) { $_SESSION[$key_ip_hist][] = $current_ip; }
	//Page hits - this page is more reliable than main if caching is on, so we'll keep a separate count
	if ( empty( $_SESSION[$key_total_page_hits] ) ) { $_SESSION[$key_total_page_hits] = 0; }
	++$_SESSION[$key_total_page_hits];
	// Referrer History - More reliable way to keep a list of pages, than using main
	if ( empty( $_SESSION[$key_pages_hist] ) ) { $_SESSION[$key_pages_hist] = array(); }
	if ( empty( $_SESSION[$key_hits_per_page] ) ) { $_SESSION[$key_hits_per_page] = array(); }
	if ( !empty( $_SERVER['HTTP_REFERER'] ) ) {
		$current_ref 	= $_SERVER['HTTP_REFERER'];
		$key_first_ref	= 'wpss_referer_init_'.WPSS_HASH;
		$key_last_ref	= 'wpss_jscripts_referer_last_'.WPSS_HASH;
		if ( !array_key_exists( $current_ref, $_SESSION[$key_pages_hist] ) ) {
			$_SESSION[$key_pages_hist][] = $current_ref;
			}
		if ( !array_key_exists( $current_ref, $_SESSION[$key_hits_per_page] ) ) {
			$_SESSION[$key_hits_per_page][$current_ref] = 1;
			}
		++$_SESSION[$key_hits_per_page][$current_ref];
		// First Referrer - Where Visitor Entered Site
		if ( empty( $_SESSION[$key_first_ref] ) ) { $_SESSION[$key_first_ref] = $current_ref; }
		// Last Referrer
		if ( empty( $_SESSION[$key_last_ref] ) ) {
			$_SESSION[$key_last_ref] = '';
			}
		$_SESSION[$key_last_ref] = $current_ref;
		}
	// IP, PAGE HITS, PAGES VISITED HISTORY - END

	// AUTHOR, EMAIL, URL HISTORY - BEGIN

	// Keep history of Author, Author Email, and Author URL in case they keep changing
	// This will expose spammer behavior patterns

	// Comment Author
	$key_auth_hist 		= 'wpss_author_history_'.WPSS_HASH;
	$key_comment_auth 	= 'comment_author_'.WPSS_HASH;
	if ( empty( $_SESSION[$key_auth_hist] ) ) {
		$_SESSION[$key_auth_hist] = array();
		if ( !empty( $_COOKIE[$key_comment_auth] ) ) {
			$_SESSION[$key_comment_auth] 	= $_COOKIE[$key_comment_auth];
			$_SESSION[$key_auth_hist][] 	= $_COOKIE[$key_comment_auth];
			}
		}
	else {
		if ( !empty( $_COOKIE[$key_comment_auth] ) ) {
			$_SESSION[$key_comment_auth] = $_COOKIE[$key_comment_auth];
			}
		}
	// Comment Author Email
	$key_email_hist 	= 'wpss_author_email_history_'.WPSS_HASH;
	$key_comment_email	= 'comment_author_email_'.WPSS_HASH;
	if ( empty( $_SESSION[$key_email_hist] ) ) {
		$_SESSION[$key_email_hist] = array();
		if ( !empty( $_COOKIE[$key_comment_email] ) ) {
			$_SESSION[$key_comment_email] 	= $_COOKIE[$key_comment_email];
			$_SESSION[$key_email_hist][] 	= $_COOKIE[$key_comment_email];
			}
		}
	else {
		if ( !empty( $_COOKIE[$key_comment_email] ) ) { $_SESSION[$key_comment_email] = $_COOKIE[$key_comment_email]; }
		}
	// Comment Author URL
	$key_auth_url_hist 	= 'wpss_author_url_history_'.WPSS_HASH;
	$key_comment_url	= 'comment_author_url_'.WPSS_HASH;
	if ( empty( $_SESSION[$key_auth_url_hist] ) ) {
		$_SESSION[$key_auth_url_hist] = array();
		if ( !empty( $_COOKIE[$key_comment_url] ) ) {
			$_SESSION[$key_comment_url] = $_COOKIE[$key_comment_url];
			$_SESSION[$key_auth_url_hist][] = $_COOKIE[$key_comment_url];
			}
		}
	else { 
		if ( !empty( $_COOKIE[$key_comment_url] ) ) { $_SESSION[$key_comment_url] = $_COOKIE[$key_comment_url]; }
		}
	// AUTHOR, EMAIL, URL HISTORY - END
	}

// STANDARD FUNCTIONS - BEGIN
function spamshield_md5_js( $string ) {
	// Use this function instead of hash for compatibility
	// BUT hash is faster than md5, so use it whenever possible
	if ( function_exists( 'hash' ) ) { $hash = hash( 'md5', $string ); } else { $hash = md5( $string );	}
	return $hash;
	}
function spamshield_microtime_js() {
	$mtime = microtime( true );
	return $mtime;
	}
function spamshield_timer_js( $start = NULL, $end = NULL, $show_seconds = false, $precision = 8 ) {
	if ( empty( $start ) || empty( $end ) ) { $start = 0; $end = 0; }
	// $precision will default to 8 but can be set to anything - 1,2,3,5,etc.
	$total_time = $end - $start;
	$total_time_for = number_format( $total_time, $precision );
	if ( !empty( $show_seconds ) ) { $total_time_for .= ' seconds'; }
	return $total_time_for;
	}
function spamshield_get_url_js() {
	if ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) { $url = 'https://'; } else { $url = 'http://'; }
	$url .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	return $url;
	}
function spamshield_get_server_addr_js() {
	if ( !empty( $_SERVER['SERVER_ADDR'] ) ) { $server_addr = $_SERVER['SERVER_ADDR']; } else { $server_addr = getenv('SERVER_ADDR'); }
	return $server_addr;
	}
// STANDARD FUNCTIONS - END

// SET COOKIE VALUES - BEGIN
$wpss_session_id = @session_id();
//$wpss_server_ip_nodot = preg_replace( "~\.~", "", spamshield_get_server_addr_js() );
$wpss_ck_key_phrase 	= 'wpss_ckkey_'.$wpss_server_ip_nodot.'_'.$wpss_session_id;
$wpss_ck_val_phrase 	= 'wpss_ckval_'.$wpss_server_ip_nodot.'_'.$wpss_session_id;
$wpss_ck_key 			= spamshield_md5_js( $wpss_ck_key_phrase );
$wpss_ck_val 			= spamshield_md5_js( $wpss_ck_val_phrase );
// SET COOKIE VALUES - END

// Last thing before headers sent
$_SESSION['wpss_sess_status'] = 'on';

if ( !empty( $current_ref ) && preg_match( "~([&\?])form\=response$~i", $current_ref ) && !empty( $_SESSION[$key_comment_auth] ) ) {
	@setcookie( $key_comment_auth, $_SESSION[$key_comment_auth], 0, '/' );
	if ( !empty( $_SESSION[$key_comment_auth] ) ) { @setcookie( $key_comment_email, $_SESSION[$key_comment_email], 0, '/' ); }
	if ( !empty( $_SESSION[$key_comment_auth] ) ) { @setcookie( $key_comment_url, $_SESSION[$key_comment_url], 0, '/' ); }
	}
@setcookie( $wpss_ck_key, $wpss_ck_val, 0, '/' );
header('Cache-Control: no-cache');
header('Pragma: no-cache');
header('Content-Type: application/x-javascript');
echo "
function GetCookie(e){var t=document.cookie.indexOf(e+'=');var n=t+e.length+1;if(!t&&e!=document.cookie.substring(0,e.length)){return null}if(t==-1)return null;var r=document.cookie.indexOf(';',n);if(r==-1)r=document.cookie.length;return unescape(document.cookie.substring(n,r))}function SetCookie(e,t,n,r,i,s){var o=new Date;o.setTime(o.getTime());if(n){n=n*1e3*60*60*24}var u=new Date(o.getTime()+n);document.cookie=e+'='+escape(t)+(n?';expires='+u.toGMTString():'')+(r?';path='+r:'')+(i?';domain='+i:'')+(s?';secure':'')}function DeleteCookie(e,t,n){if(getCookie(e))document.cookie=e+'='+(t?';path='+t:'')+(n?';domain='+n:'')+';expires=Thu, 01-Jan-1970 00:00:01 GMT'}
function commentValidation(){SetCookie('".$wpss_ck_key ."','".$wpss_ck_val ."','','/');SetCookie('SJECT14','CKON14','','/');}
commentValidation();
";

// Uncomment to use:
$wpss_js_end_time = spamshield_microtime_js();
$wpss_js_total_time = spamshield_timer_js( $wpss_js_start_time, $wpss_js_end_time, true, 6 );
echo "// Generated in: ".$wpss_js_total_time."\n";
?>