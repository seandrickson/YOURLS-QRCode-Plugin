<?php

/**
 * Class QRImageWithLogo
 *
 * Based on the php-qrcode logo example by smiley <smiley@chillerlan.net>
 * (https://github.com/chillerlan/php-qrcode), MIT license.
 */

declare(strict_types=1);

namespace SeansQRCode;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRCodeOutputException;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QROptions;

/**
 * PNG output that places a logo image in the middle of the QR code.
 */
final class QRImageWithLogo extends QRGdImagePNG
{
    /**
     * @param QROptions $qrOptions the QR options, with addLogoSpace enabled
     * @param QRMatrix  $matrix    the QR code to draw
     * @param string    $logoFile  path to the PNG logo
     * @param int       $logoSpace size of the logo area, in modules
     */
    public function __construct(
        private readonly QROptions $qrOptions,
        QRMatrix $matrix,
        private readonly string $logoFile,
        private readonly int $logoSpace,
    ) {
        parent::__construct($qrOptions, $matrix);
    }

    /**
     * @throws QRCodeOutputException
     */
    #[\Override]
    public function dump(?string $file = null): string
    {
        // the file type isn't checked, for simplicity (assuming PNG)
        if (!is_file($this->logoFile) || !is_readable($this->logoFile)) {
            throw new QRCodeOutputException('invalid logo');
        }

        // set returnResource to true to skip further processing for now
        $this->qrOptions->returnResource = true;

        // there's no need to save the result of dump() into $this->image here
        parent::dump($file);

        $im = imagecreatefrompng($this->logoFile);
        if ($im === false) {
            throw new QRCodeOutputException('could not read logo');
        }

        // set new logo size, leave a border of 1 module (no proportional resize/centering)
        $logoSize = ($this->logoSpace - 2) * $this->scale;
        $offset = intdiv($this->length - $logoSize, 2);

        // scale the logo and copy it over. done!
        imagecopyresampled($this->image, $im, $offset, $offset, 0, 0, $logoSize, $logoSize, imagesx($im), imagesy($im));

        $imageData = $this->dumpImage();

        if ($file !== null) {
            $this->saveToFile($imageData, $file);
        }

        if ($this->qrOptions->outputBase64) {
            $imageData = 'data:image/' . static::MIME_TYPE . ';base64,' . base64_encode($imageData);
        }

        return $imageData;
    }
}
