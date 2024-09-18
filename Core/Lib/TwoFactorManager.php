<?php

namespace FacturaScripts\Core\Lib;

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
class TwoFactorManager
{
    public static  function getSecretyKey(): string
    {
        $google2fa = new Google2FA();
        return $google2fa->generateSecretKey();
    }

    public static function getQRCodeUrl(string $companyName, string $email, string $secretKey): string
    {
        $google2fa = new Google2FA();
        return $google2fa->getQRCodeUrl($companyName, $email, $secretKey);
    }

    public static function getQRCodeImage(string $url): string
    {
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(400),
                new ImagickImageBackEnd()
            )
        );

        return base64_encode($writer->writeString($url));
    }

    public static function verifyCode(string $secretKey, string $code): bool
    {
        $google2fa = new Google2FA();
        $window = 8;

        return $google2fa->verifyKey($secretKey, $code, $window);
    }

}