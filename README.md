YOURLS QR Code Plugin
=====================

Allows you to get the QR code by simply clicking on a button in the Admin area (or by adding `.qr` to the end of the short URL.) Can also optionally display a QR code in the share box.

Installation
------------

1. Copy the `seans-qrcode` folder into your YOURLS `user/plugins` folder.
2. Install the QR code library with [Composer](https://getcomposer.org/), from inside that folder:

   ```bash
   cd user/plugins/seans-qrcode
   composer install
   ```

   This creates the `vendor` folder the plugin needs. If you skip this step, the admin area shows a notice and no QR codes are generated.
3. Activate the plugin in the admin interface. That's all there is to it, but see Configuration below.

Error messages like `Failed opening required '.../vendor/autoload.php'` or `syntax error, unexpected 'int'` on older versions of the plugin mean step 2 was skipped or PHP is older than 8.1.

Requirements
------------

User must have [YOURLS](http://yourls.org/#Install) 1.8+ installed (the plugin relies on the `redirect_keyword_not_found` hook). Latest version is tested with YOURLS 1.10.x, which requires PHP 8.1+.

In addition to the [server requirements of YOURLS](http://yourls.org/#requirement), the [chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode) library requires the `gd` and `mbstring` PHP extensions.

Compatible with Ozh's [404 if not found](https://github.com/YOURLS/404-if-not-found) plugin.

If these requirements can't be met, but you can install and run YOURLS, try the [Google Chart API QR Code Plugin](https://github.com/YOURLS/YOURLS/wiki/Plugin-%3D-QRCode-ShortURL) from Ozh (YOURL's developer).

Configuration
-------------

The plugin requires no special configuration, but there are a few options that you can control by defining constants in `user/config.php`, e.g.


```php
define("SEAN_QR_SCALE", 5);
```

### SEAN_QR_SCALE
_Integer. Default: 5._  
The size of a QR code pixel (SVG, IMAGE_*), HTML -> via CSS

### SEAN_QR_LOGO
_Boolean. Default: true._  
Whether to place the logo (`logo.png` in the plugin folder) in the middle of the QR code. With the logo, the highest error correction level (H) and a minimum QR version of 5 are used so the code still scans. Set to false for a plain, smaller QR code (error correction level M).

### SEAN_QR_LOGO_SPACE
_Integer. Default: 13._  
The size of the logo area in QR modules (multiply by `SEAN_QR_SCALE` for pixels). Values below 3 turn the logo off.

### SEAN_QR_ADD_TO_SHAREBOX
_Boolean. Default: true._  
Whether to include a QR code in the share boxes. Set to true to enable.

### SEAN_QR_MARGIN
_Integer. Default: 4._  
The width of the margin (quiet zone) around the QR, in 'virtual pixels'. A value of 4 is generally recommended, but if you are using the QR code in a context where it is surrounded by white space anyway lower values may work fine.

Bonus
-----

Works with [YOURLS Case-Insensitive](https://github.com/seandrickson/YOURLS-Case-Insensitive) to generate smaller QR codes! With that plugin active, the short URL is encoded in upper case, which lets the QR code use the more compact alphanumeric mode.

Credits
-------

Main functionality of adding a QR code is borrowed from [Ozh's orginal plugin code](https://github.com/YOURLS/YOURLS/wiki/Plugin-%3D-QRCode-ShortURL).

QR code generation made possible by [chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode)
