<?php

/**
 * YOURLS hooks for Sean's QR Code Short URLs.
 *
 * Loaded by plugin.php once the requirements are met.
 *
 * No strict_types here on purpose: this file is where YOURLS passes values
 * in, and a loosely typed value from YOURLS shouldn't turn into a TypeError.
 * The classes in src/ use strict types.
 */

// No direct call
if (!defined('YOURLS_ABSPATH')) {
    die();
}

/**
 * The QR code renderer, configured from the plugin options.
 */
function sean_qr_renderer(): SeansQRCode\QRCodeRenderer
{
    return new SeansQRCode\QRCodeRenderer(
        (int) SEAN_QR_SCALE,
        (int) SEAN_QR_MARGIN,
        filter_var(SEAN_QR_LOGO, FILTER_VALIDATE_BOOLEAN),
        (int) SEAN_QR_LOGO_SPACE,
        __DIR__ . '/logo.png',
    );
}

// Kick in when the requested keyword is not found (e.g. 'abc.qr').
// This hook fires before 'loader_failed', so plugins that exit on it
// (such as Ozh's 404-if-not-found) don't prevent QR codes from being served.
yourls_add_action('redirect_keyword_not_found', 'sean_yourls_qrcode');

/**
 * Serve the QR code for requests like "abc.qr".
 *
 * @param array<array-key, mixed> $request the hook arguments; [0] is the requested keyword
 */
function sean_yourls_qrcode(array $request): void
{
    if (!array_key_exists(0, $request) || !is_string($request[0])) {
        return;
    }
    $requested = $request[0];

    // Get authorized charset in keywords and make a regexp pattern
    $pattern = yourls_make_regexp_pattern(yourls_get_shorturl_charset());

    // if the shorturl is like bleh.qr
    $matches = [];
    if (!preg_match("@^([{$pattern}]+)\.qr$@i", $requested, $matches)) {
        return;
    }

    // if this shorturl exists...
    $keyword = yourls_sanitize_keyword($matches[1]);
    if (!yourls_is_shorturl($keyword)) {
        return;
    }

    $url = yourls_link($keyword);
    $yourls_url = yourls_site_url(false);
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // If Case-Insensitive plugin is enabled and YOURLS is not a sub-directory install...
    if (
        yourls_is_active_plugin('case-insensitive/plugin.php')
        && ('http://' . $host === $yourls_url || 'https://' . $host === $yourls_url)
    ) {
        // Make the QR smaller
        // Alphanumeric URLs have less bits/char:
        // http://en.wikipedia.org/wiki/QR_code#Storage
        $url = strtoupper($url);
    }

    $png = sean_qr_renderer()->render($url);

    header('Content-type: image/png');
    echo $png;

    exit();
}

// Add our QR Code Button to the Admin interface
// (the filter passes 6 arguments, we only need the links and the keyword)
yourls_add_filter('action_links', 'sean_add_qrcode_button', 10, 2);

/**
 * Add the QR Code button to a row's action links.
 */
function sean_add_qrcode_button(string $action_links, string $keyword): string
{
    // the QR code is the short URL with .qr at the end
    $qrlink = yourls_link($keyword) . '.qr';
    $id = yourls_unique_element_id('qrlink'); // used as HTML #id

    // Add our QR code generator button to the action links list
    return sprintf(
        '%s<a href="%s" id="%s" title="QR Code" class="button button_qrcode">QR Code</a>',
        $action_links,
        yourls_esc_attr($qrlink),
        yourls_esc_attr($id),
    );
}

// Add the CSS and some extra javascript to <head>
yourls_add_action('html_head', 'sean_add_qrcode_css_head');
/**
 * Output the button CSS and the QR popup JavaScript on the admin index page.
 *
 * @param array<array-key, mixed> $context the page context, e.g. ['index']
 */
function sean_add_qrcode_css_head(array $context): void
{
    // only on the index page, where the QR Code buttons are
    if (!in_array('index', $context, true)) {
        return;
    }
    ?>
			<style type="text/css">
				td.actions .button_qrcode {
					margin-right: 0;
					background: url(data:image/png;base64,R0lGODlhEAAQAIAAAAAAAP///yH5BAAAAAAALAAAAAAQABAAAAIvjI9pwIztAjjTzYWr1FrS923NAymYSV3borJW26KdaHnr6UUxd4fqL0qNbD2UqQAAOw==) no-repeat 2px 50%;
					<?php if (SEAN_QR_BUTTON_ALWAYS_VISIBLE): ?>
					/* older YOURLS hides action buttons with visibility, newer with opacity + pointer-events */
					visibility: visible !important;
					opacity: 1 !important;
					pointer-events: auto !important;
					<?php endif; ?>
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
				<?php if (SEAN_QR_ADD_TO_SHAREBOX): ?>
					var shorturl = $('#keyword-'+id+' a:first').attr('href');
					if (shorturl != undefined) {
						$('#sean_qr_img').attr( 'src', shorturl.replace(/^http(s)?:\/\//, "//") + '.qr' );
					}
				<?php endif; ?>
			}

			</script>
<?php
}

/* Displaying QR code in share box */
if (SEAN_QR_ADD_TO_SHAREBOX) {
    yourls_add_action('shareboxes_after', 'sean_add_qr_div');
    yourls_add_filter('table_add_row_action_array', 'sean_change_share_action');
}

/**
 * Add the QR code to the share box.
 *
 * @param array<array-key, mixed> $args the hook arguments; [1] is the short URL
 */
function sean_add_qr_div(array $args): void
{
    // YOURLS defines YOURLS_INFOS (as true) on the stats page
    $h = defined('YOURLS_INFOS') ? 'h3' : 'h2';

    $img = '';
    if (array_key_exists(1, $args) && is_string($args[1]) && $args[1] !== '') {
        $img = yourls_match_current_protocol($args[1] . '.qr');
    }
    ?>
	<div id="sean_qr_box" class="share">
		<<?php echo $h; ?>>QR</<?php echo $h; ?>>
			<img src="<?php echo yourls_esc_attr($img); ?>" id="sean_qr_img" alt="QR code" width="75px" />
	</div>
<?php
}

/**
 * Make the share button also update the QR code in the share box.
 *
 * @param array<string, array<string, string>> $actions the row's action buttons
 *
 * @return array<string, array<string, string>>
 */
function sean_change_share_action(array $actions): array
{
    // the share button's id is "share-button-<row id>"
    $id = substr($actions['share']['id'], strlen('share-button-'));
    $actions['share']['onclick'] = "toggle_share('{$id}');sean_toggle_qr('{$id}');return false;";
    return $actions;
}
