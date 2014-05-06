YOURLS QR Code Plugin
=====================

Allows you to get the QR code by simply clicking on a button in the Admin area (or by adding `.qr` to the end of the keyword.)

Installation
------------

Move the `seans-qrcode` folder into the `/users/plugins` folder. Then, activate the plugin in the admin interface. That's all there is to it.

Requirements
------------

User must have [YOURLS](http://yourls.org/#Install) 1.5.1+ installed.

**WARNING**: Does not work with YOURLS 1.6.

In addition to the [server requirements of YOURLS](http://yourls.org/#requirement), make sure you follow the [server requirements of PHP QR Code library](http://sourceforge.net/p/phpqrcode/code/HEAD/tree/branches/www/1.1.4/INSTALL).

If these requirements can't be met, but you can install and run YOURLS, try the [Google Chart API QR Code Plugin](https://github.com/YOURLS/YOURLS/wiki/Plugin-%3D-QRCode-ShortURL) from Ozh (YOURL's developer).

Bonus
-----

Works with [YOURLS Case-Insensitive](https://github.com/seandrickson/YOURLS-Case-Insensitive) to generate smaller QR codes!

Credits
-------

Main functionality of adding a QR code is borrowed from [Ozh's orginal plugin code](https://github.com/YOURLS/YOURLS/wiki/Plugin-%3D-QRCode-ShortURL).

QR code generation made possible by [PHP QR Code](http://phpqrcode.sourceforge.net/) (Google's own QR Code generation, through the Chart Image API [is depreciated](http://googledevelopers.blogspot.com/2012/04/changes-to-deprecation-policies-and-api.html) and will no longer be developed).
