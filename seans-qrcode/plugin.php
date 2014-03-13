<?php
/*
Plugin Name: Sean's QR Code Short URLs
Plugin URI: https://github.com/seandrickson/YOURLS-QRCode-Plugin
Description: Allows you to get the QR code by simply clicking on a button in the Admin area (or by adding <tt>.qr</tt> to the end of the keyword.) Works with <a href="https://github.com/seandrickson/YOURLS-Case-Insensitive">Case-Insensitive</a> to create smaller QR codes.
Version: 1.1
Author: Sean Hendrickson
Author URI: https://github.com/seandrickson
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();


//include qrcode library
require_once( dirname(__FILE__).'/phpqrcode.php' );


// Kick in if the loader does not recognize a valid pattern
yourls_add_action( 'loader_failed', 'sean_yourls_qrcode' );
function sean_yourls_qrcode( $request ) {
	// --- START configurable variables ---

	// output file name, if false outputs to browser with required headers:
	$outfile = false;

	// error correction level (constants, don't use quotes):
	$level = QR_ECLEVEL_L; // QR_ECLEVEL_L, QR_ECLEVEL_M, QR_ECLEVEL_Q or QR_ECLEVEL_H

	// pixel size multiplier (3 = 3x3 pixels for QR):
	$size = 3;

	// outside margin in 'virtual' pixels:
	$margin = 4;

	// if true code is outputed to browser and saved to file,
	// otherwise only saved to file.
	// It is effective only if $outfile is specified.
	$saveandprint = false;

	// --- END configurable variables ---

	// Get authorized charset in keywords and make a regexp pattern
	$pattern = yourls_make_regexp_pattern( yourls_get_shorturl_charset() );

	// if the shorturl is like bleh.qr...
	if( preg_match( "@^([$pattern]+)\.qr?/?$@", $request[0], $matches ) ) {

		// if this shorturl exists...
		$keyword = yourls_sanitize_keyword( $matches[1] );
		if( yourls_is_shorturl( $keyword ) ) {
			$url = yourls_link( $keyword );
			$yourls_url = yourls_site_url( false );

			// If Case-Insensitive plugin is enabled and YOURLS is not a sub-directory install...
			if( yourls_is_active_plugin( 'case-insensitive/plugin.php' )
				&& ( 'http://'.$_SERVER['HTTP_HOST'] == $yourls_url
				||  'https://'.$_SERVER['HTTP_HOST'] == $yourls_url ) ) {

				// Make the QR smaller
				// Alphanumeric URLs have less bits/char:
				// http://en.wikipedia.org/wiki/QR_code#Storage
				$url = strtoupper( $url );
			}

			// Show the QR code then!
			QRcode::png( $url, $outfile, $level, $size, $margin, $saveandprint );
			exit;
		}
	}
}


// Add our QR Code Button to the Admin interface
yourls_add_filter( 'action_links', 'sean_add_qrcode_button' );
function sean_add_qrcode_button( $action_links, $keyword, $url, $ip, $clicks, $timestamp ) {
	$surl = yourls_link( $keyword );
	$id = yourls_string2htmlid( $keyword ); // used as HTML #id

	// We're adding .qr to the end of the URL, right?
	$qr = '.qr';
	$qrlink = $surl . $qr;

	// Define the QR Code
	$qrcode = array(
		'href'    => $qrlink,
		'id'      => "qrlink-$id",
		'title'   => 'QR Code',
		'anchor'  => 'QR Code'
	);

	// Add our QR code generator button to the action links list
	$action_links .= sprintf( '<a href="%s" id="%s" title="%s" class="%s">%s</a>',
		$qrlink, $qrcode['id'], $qrcode['title'], 'button button_qrcode', $qrcode['anchor']
	);

  return $action_links;
}


// Add the CSS to <head>
yourls_add_action( 'html_head', 'sean_add_qrcode_css_head' );
function sean_add_qrcode_css_head( $context ) {

	// expose what page we are on
	foreach($context as $k):

		// If we are on the index page, use this css code for the button
		if( $k == 'index' ):
?>
<style type="text/css">
	td.actions .button_qrcode {
		margin-right: 0;
		background: url(data:image/png;base64,R0lGODlhEAAQAIAAAAAAAP///yH5BAAAAAAALAAAAAAQABAAAAIvjI9pwIztAjjTzYWr1FrS923NAymYSV3borJW26KdaHnr6UUxd4fqL0qNbD2UqQAAOw==) no-repeat 2px 50%;
	}
</style>
<?php
		endif;
	endforeach;
}
