<?php
/*
Plugin Name: Sean's QR Code Short URLs
Plugin URI: https://github.com/seandrickson/YOURLS-QRCode-Plugin
Description: Allows you to get the QR code by simply clicking on a button in the Admin area (or by adding <tt>.qr</tt> to the end of the keyword.) Works with <a href="https://github.com/seandrickson/YOURLS-Case-Insensitive">Case-Insensitive</a> to create smaller QR codes.
Version: 1.2
Author: Sean Hendrickson
Author URI: https://github.com/seandrickson
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

/**
 * The following configuration constants can be overridden
 * by defining them in user/config.php
 */

// size of a QR code pixel (SVG, IMAGE_*), HTML -> via CSS
defined('SEAN_QR_SCALE') or define('SEAN_QR_SCALE', 5);

// size in QR modules, multiply with QROptions::$scale for pixel size
defined('SEAN_QR_LOGO_SPACE') or define('SEAN_QR_LOGO_SPACE', 13);

// should we include a QR code in the share boxes
defined('SEAN_QR_ADD_TO_SHAREBOX') or define('SEAN_QR_ADD_TO_SHAREBOX', true);

// outside margin of QR code in 'virtual' pixels:
defined('SEAN_QR_MARGIN') or define('SEAN_QR_MARGIN', 4);

require_once __DIR__.'/vendor/autoload.php';

//include qrcode library
require_once( dirname(__FILE__).'/QRImageWithLogo.php' );

use chillerlan\QRCode\{QRCode, QROptions};

class LogoOptions extends QROptions{
	// size in QR modules, multiply with QROptions::$scale for pixel size
	protected int $logoSpaceWidth;
	protected int $logoSpaceHeight;
}

// Kick in if the loader does not recognize a valid pattern
yourls_add_action( 'loader_failed', 'sean_yourls_qrcode' );
function sean_yourls_qrcode( $request ) {
	// Get authorized charset in keywords and make a regexp pattern
	$pattern = yourls_make_regexp_pattern( yourls_get_shorturl_charset() );

	// if the shorturl is like bleh.qr
	if( preg_match( "@^([$pattern]+)(\.qr)?$@i", $request[0], $matches ) ) {
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

			$options = new LogoOptions;

			$options->version          = 7;
			$options->eccLevel         = QRCode::ECC_H;
			$options->imageBase64      = false;
			$options->logoSpaceWidth   = SEAN_QR_LOGO_SPACE;
			$options->logoSpaceHeight  = SEAN_QR_LOGO_SPACE;
			$options->scale            = SEAN_QR_SCALE;
			$options->imageTransparent = false;
			$options->quietzoneSize    = SEAN_QR_MARGIN;

			header('Content-type: image/png');

			$qrOutputInterface = new QRImageWithLogo($options, (new QRCode($options))->getMatrix($url));

			// dump the output, with an additional logo
			echo $qrOutputInterface->dump(null, __DIR__.'/logo.png');

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


// Add the CSS and some extra javascript to <head>
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
          visibility: visible !important;
				}
			</style>

			<script type="text/javascript">

			jQuery(document).ready(function($) {
				sean_add_qr_popup($('.button_qrcode'));
				$('#main_table tbody').on("update", function() {
					if ($('#add-button').hasClass('loading')) {
						sean_add_qr_popup($('#main_table tbody tr:first td .button_qrcode'));
						var id = $('#main_table tbody tr:first').attr('id').replace(/^id-/, "");
						sean_toggle_qr(id);
					}
				});
			});

			function sean_add_qr_popup(el) {
				// need to do 'off' first because adding a URL triggers this twice and we don't want two identical actions added to click
				el.off('click').on('click', function() {
					var NWin = window.open($(this).attr('href'), '', 'scrollbars=0,location=0,height=200,width=200');
					if (window.focus) {
						NWin.focus();
					}
					return false;
				});
			}

			function sean_toggle_qr(id) {
				<?php if( SEAN_QR_ADD_TO_SHAREBOX ): ?>
					var shorturl = $('#keyword-'+id+' a:first').attr('href');
					if (shorturl != undefined) {
						$('#sean_qr_img').attr( 'src', shorturl.replace(/^http(s)?:\/\//, "//") + '.qr' );
					}
				<?php endif; ?>
			}

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

/* Add the extra HTML for the QR code to the share boxe */
function sean_add_qr_div($args) {
	$h = "h2";
	if( defined('YOURLS_INFOS') && YOURLS_INFOS) {
		$h = "h3";
	}

	$heading = "<$h>QR</$h>";
	$img = !empty($args[1])?$args[1] . '.qr':'';
	$img = yourls_match_current_protocol($img);
?>
	<div id="sean_qr_box" class="share">
		<?php echo $heading; ?>
			<img src="<?php echo $img; ?>" id="sean_qr_img" alt="QR code" width="75px" />
	</div>
<?php
}

/* modify the 'onclick' action for the share buttons to include toggling the QR code */
function sean_change_share_action($actions) {
    $id = substr($actions['share']['id'],13);
    $actions['share']['onclick'] = "toggle_share('$id');sean_toggle_qr('$id');return false;";
    return $actions;
}
