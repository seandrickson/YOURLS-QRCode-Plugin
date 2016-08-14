YOURLS QR Code Plugin
=====================

Allows you to get the QR code by simply clicking on a button in the Admin area (or by adding `.qr` to the end of the short URL. Can also optionally display a QR code in the share box. 

QR codes can be configured to be either PNG or SVG format by default. Appending the extension `.svg` or `.png` to the URL will force the format.

Installation
------------

Move the `seans-qrcode` folder into the `/users/plugins` folder. Then, activate the plugin in the admin interface. That's all there is to it, but see Configuration below

Requirements
------------

User must have [YOURLS](http://yourls.org/#Install) 1.5.1+ installed. Latest version is tested with YOURLS 1.7.1.

**WARNING**: Does not work with YOURLS 1.6.

In addition to the [server requirements of YOURLS](http://yourls.org/#requirement), make sure you follow the [server requirements of PHP QR Code library](http://sourceforge.net/p/phpqrcode/code/HEAD/tree/branches/www/1.1.4/INSTALL).

If these requirements can't be met, but you can install and run YOURLS, try the [Google Chart API QR Code Plugin](https://github.com/YOURLS/YOURLS/wiki/Plugin-%3D-QRCode-ShortURL) from Ozh (YOURL's developer).

Configuration
-------------

The plugin requires no special configuration, but there are a few options that you can control by defining constants in `user/config.php`, e.g.


```php
define("SEAN_QR_WIDTH", 250);
```

### SEAN_QR_WIDTH
_Interger. Default: 200._  
The width (and height) of the generated QR code in pixels. For PNG this will be approximate.

### SEAN_QR_ADD_TO_SHAREBOX
_Boolean. Default: false._  
Whether to include a QR code in the share boxes. Set to true to enable.

### SEAN_QR_DEFAULT_FMT
_String. 'png' or 'svg'. Default: png._  
The default format to generate QR codes in. This can be overridden be adding the appropriate extension to the QR code's URL. eg `http://mydomain.com/bleh.qr.svg` or `http://mydomain.com/bleh.qr.png`

### SEAN_QR_MARGIN
_Interger. Default: 2._  
The width of the margin (quiet zone) around the QR, in 'virtual pixels'. A value of 4 is generally recommended, but if you are using the QR context where it is surrounded by white space anyway lower values may work fine.

Bonus
-----

Works with [YOURLS Case-Insensitive](https://github.com/seandrickson/YOURLS-Case-Insensitive) to generate smaller QR codes!

Credits
-------

Main functionality of adding a QR code is borrowed from [Ozh's orginal plugin code](https://github.com/YOURLS/YOURLS/wiki/Plugin-%3D-QRCode-ShortURL).

QR code generation made possible by [PHP QR Code](http://phpqrcode.sourceforge.net/) (Google's own QR Code generation, through the Chart Image API [is deprecated](http://googledevelopers.blogspot.com/2012/04/changes-to-deprecation-policies-and-api.html) and will no longer be developed).
