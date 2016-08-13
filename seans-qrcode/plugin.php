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

/**
 * The following configuration constants can be overridden
 * by defining them in user/config.php
 */
// width of image in pixels. Will be approximate for png
defined('SEAN_QR_WIDTH') or define('SEAN_QR_WIDTH', 200);

// should we include a QR code in the share boxes
defined('SEAN_QR_ADD_TO_SHAREBOX') or define('SEAN_QR_ADD_TO_SHAREBOX', true);

// format of image. 'svg' for SVG, otherwise it will by PNG
defined('SEAN_QR_FMT') or define('SEAN_QR_FMT', 'png');

// outside margin of QR code in 'virtual' pixels:
defined('SEAN_QR_MARGIN') or define('SEAN_QR_MARGIN', 2);

//include qrcode library
require_once( dirname(__FILE__).'/phpqrcode.php' );


// Kick in if the loader does not recognize a valid pattern
yourls_add_action( 'loader_failed', 'sean_yourls_qrcode' );
function sean_yourls_qrcode( $request ) {
	// --- START configurable variables ---

	// output file name, if false outputs to browser with required headers:
	$outfile = false;

	// Error correction level. Constants - do not use quotes!
	// One of QR_ECLEVEL_L, QR_ECLEVEL_M, QR_ECLEVEL_Q or QR_ECLEVEL_H
	// NB this can not be defined as a constant in user/config.php because 
	// the QR_ECLEVEL_* constants haven't been defined at that point!!
	$level = QR_ECLEVEL_L;

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
			if(SEAN_QR_FMT == 'svg') {
				header('Content-type: image/svg+xml');
				echo QRcode::svg($url, false, $outfile, $level, SEAN_QR_WIDTH, false, SEAN_QR_MARGIN);
			} else {
				// crudely estimate the value of $size needed to get a png somewhere near
				// SEAN_QR_WIDTH. We assume version 2 QR (ie 25x25)
				$size = floor(SEAN_QR_WIDTH/(25+(2*SEAN_QR_MARGIN)));
				QRcode::png( $url, $outfile, $level, $size, SEAN_QR_MARGIN );
			}
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
<script type="text/javascript">
jQuery(document).ready(function($) {
   $('.button_qrcode').click(function() {
     var NWin = window.open($(this).prop('href'), '', 'scrollbars=0,location=0,height=<?php echo SEAN_QR_WIDTH ?>,width=<?php echo SEAN_QR_WIDTH ?>');
     if (window.focus)
     {
       NWin.focus();
     }
     return false;
    });
    $('#main_table tbody').on("update", function() {
        $('#main_table tbody tr:first td .button_qrcode').off("click").on("click", function() {
           var NWin = window.open($(this).prop('href'), '', 'scrollbars=0,location=0,height=<?php echo SEAN_QR_WIDTH ?>,width=<?php echo SEAN_QR_WIDTH ?>');
           if (window.focus) {
             NWin.focus();
           }
           return false;
        });
<?php			if( SEAN_QR_ADD_TO_SHAREBOX ): ?>
        var shorturl = $('#main_table tbody tr:first td.keyword a:first').attr('href').replace(/^http(s)?:\/\//, "//");
        $('#sean_qr_img').attr( 'src', shorturl + '.qr' );
<?php			endif; ?>
    });

});
<?php			if( SEAN_QR_ADD_TO_SHAREBOX ): ?>
function sean_toggle_qr(id) {
    var shorturl = $('#keyword-'+id+' a:first').attr('href').replace(/^http(s)?:\/\//, "//");
    $('#sean_qr_img').attr( 'src', shorturl + '.qr' );
}
<?php			endif; ?>
</script>
<?php
		endif;
	endforeach;
}

/* Displaying QR code in share box */
if (SEAN_QR_ADD_TO_SHAREBOX) {
    yourls_add_action( 'shareboxes_after', 'sean_add_qr_div');
    yourls_add_filter('table_add_row_action_array', 'sean_change_share_action');
}

function sean_add_qr_div($args) {
?>
<div id="sean_qr_box" class="share">
<h2>QR</h2>
<img src="<?php echo !empty($args[1])?$args[1] . '.qr':'' ?>" id="sean_qr_img" alt="QR code" width="75px" />
</div>
<?php
}


function sean_change_share_action($actions) {
    $id = substr($actions['share']['id'],13);
    $actions['share']['onclick'] = "toggle_share('$id');sean_toggle_qr('$id');return false;";
    return $actions;
}
