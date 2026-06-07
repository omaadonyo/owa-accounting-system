<?php

namespace App\Helpers;

class QrCode
{
    public static function generate(string $data, int $size = 150): string
    {
        $options = new \chillerlan\QRCode\QROptions;
        $options->outputInterface = \chillerlan\QRCode\Output\QRMarkupSVG::class;
        $options->svgAddXmlHeader = false;
        $options->outputBase64 = false;

        $qrcode = new \chillerlan\QRCode\QRCode($options);

        return $qrcode->render($data);
    }

    public static function generateDataUri(string $data, int $size = 150): string
    {
        $options = new \chillerlan\QRCode\QROptions;
        $options->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;
        $options->scale = max(5, (int) round($size / 25));
        $options->outputBase64 = true;

        $qrcode = new \chillerlan\QRCode\QRCode($options);

        return $qrcode->render($data);
    }
}
