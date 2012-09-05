<?php /*
Plugin Name: QR Code Short URLs
Plugin URI: http://yourls.org/
Description: Add .qr to shorturls to display QR Code
Version: 1.0
Author: Sean Hendrickson
Author URI: http://flavors.me/seandrickson
*/

// Matches plugin folder name 
define( 'SEAN_QRCODE_PLUGINDIR', 'qrcode' );

// Kick in if the loader does not recognize a valid pattern
function sean_yourls_qrcode( $request ) {
	$size = '150x150'; // Size of QR code
	
	// Get authorized charset in keywords and make a regexp pattern
	$pattern = yourls_make_regexp_pattern( yourls_get_shorturl_charset() );
	
	// Shorturl is like bleh.qr ?
	if( preg_match( "@^([$pattern]+)\.qr?/?$@", $request[0], $matches ) ) {
		// this shorturl exists ?
		$keyword = yourls_sanitize_keyword( $matches[1] );
		if( yourls_is_shorturl( $keyword ) ) {
			$url = yourls_link( $keyword );
			$yourls_url = yourls_site_url( false );
			
			// If my Case Insensitive plugin is enabled...
			if( defined( 'SEAN_CASE_INSENSITIVE' ) && SEAN_CASE_INSENSITIVE == true && ( 'http://'.$_SERVER['HTTP_HOST'] == $yourls_url || 'https://'.$_SERVER['HTTP_HOST'] == $yourls_url ) ) {
				// Uppercase URLs have less data: http://en.wikipedia.org/wiki/QR_code#Storage
				$url = strtoupper( $url );
			}
			
			// Show the QR code then!
			header( 'Location: https://chart.googleapis.com/chart?chs=' . $size . '&cht=qr&chl=' . urlencode($url) );
			exit;
		}
	}
} yourls_add_action( 'loader_failed', 'sean_yourls_qrcode' );


// Add our QR Code Button to the Admin interface
function sean_add_row_action_qrcode( $links ) {
	global $keyword;
	$surl = yourls_link( $keyword );
	$id = yourls_string2htmlid( $keyword ); // used as HTML #id
	
	// We're adding .qr to the end of the URL, right?
	$qr = '.qr';
	$qrlink = $surl . $qr;
	
	// And add the button to the links in the actions
	$links['qrcode'] = array(
		'href'    => $qrlink,
		'id'      => "qrlink-$id",
		'title'   => 'QR Code',
		'anchor'  => 'QR Code',
	);
	
	return $links;
} yourls_add_filter( 'table_add_row_action_array', 'sean_add_row_action_qrcode' );


// Add the CSS to <head>
function sean_add_qrcode_css_head( $context ) {
	foreach($context as $k)
		if( $k == 'index' ) // If we are on the index page, use this css code for the button
			print '<style type="text/css">td.actions .button_qrcode{background: url("data:image/png;base64,R0lGODlhEAAQAIAAAAAAAP///yH5BAAAAAAALAAAAAAQABAAAAIvjI9pwIztAjjTzYWr1FrS923NAymYSV3borJW26KdaHnr6UUxd4fqL0qNbD2UqQAAOw==") no-repeat scroll 2px center transparent;}</style>' . PHP_EOL;
} yourls_add_action( 'html_head', 'sean_add_qrcode_css_head' );