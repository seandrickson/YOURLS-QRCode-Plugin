<?php

declare(strict_types=1);

namespace SeansQRCode;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\Data\AlphaNum;
use chillerlan\QRCode\Output\QRCodeOutputException;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Turns a URL into a PNG QR code, optionally with a logo in the middle.
 */
final class QRCodeRenderer
{
    /**
     * Smallest QR version used with a logo: smaller versions with a
     * 13-module logo don't scan reliably.
     */
    private const LOGO_MIN_VERSION = 5;

    /**
     * @param int    $scale     size of a QR module in pixels
     * @param int    $margin    quiet zone around the code, in modules
     * @param bool   $logo      whether to place the logo in the middle
     * @param int    $logoSpace size of the logo area, in modules
     * @param string $logoFile  path to the PNG logo
     */
    public function __construct(
        private readonly int $scale,
        private readonly int $margin,
        private readonly bool $logo,
        private readonly int $logoSpace,
        private readonly string $logoFile,
    ) {}

    /**
     * Whether the QR code gets a logo: the logo area needs at least
     * 3 modules (1 module border on each side).
     */
    public function hasLogo(): bool
    {
        return $this->logo && $this->logoSpace > 2;
    }

    /**
     * Render the URL as PNG image data.
     *
     * @throws QRCodeOutputException
     */
    public function render(string $url): string
    {
        $settings = $this->settings();

        $qrcode = new QRCode($settings);

        // Alphanumeric mode (0-9, A-Z, space and $%*+-./:) uses fewer bits
        // per character than byte mode, which gives a smaller QR code
        if (AlphaNum::validateString($url)) {
            $qrcode->addAlphaNumSegment($url);
        } else {
            $qrcode->addByteSegment($url);
        }

        $matrix = $qrcode->getQRMatrix();

        $options = new QROptions($settings);

        if ($this->hasLogo()) {
            return (new QRImageWithLogo($options, $matrix, $this->logoFile, $this->logoSpace))->dump();
        }

        $png = (new QRGdImagePNG($options, $matrix))->dump();
        if (!is_string($png)) {
            throw new QRCodeOutputException('unexpected image resource');
        }

        return $png;
    }

    /**
     * The php-qrcode settings for this QR code.
     *
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $options = [
            // pick the smallest QR version that fits the URL
            'version' => Version::AUTO,
            'eccLevel' => EccLevel::M,
            'outputBase64' => false,
            'scale' => $this->scale,
            'imageTransparent' => false,
            'quietzoneSize' => $this->margin,
        ];

        if ($this->hasLogo()) {
            // The logo hides part of the code, so use the highest error correction
            // and a minimum version so the logo stays a small share of the code
            $options['eccLevel'] = EccLevel::H;
            $options['versionMin'] = self::LOGO_MIN_VERSION;
            $options['addLogoSpace'] = true;
            $options['logoSpaceWidth'] = $this->logoSpace;
            $options['logoSpaceHeight'] = $this->logoSpace;
        }

        return $options;
    }
}
