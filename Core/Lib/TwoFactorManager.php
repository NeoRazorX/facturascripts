<?php

namespace FacturaScripts\Core\Lib;

use PragmaRX\Google2FA\Google2FA;
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

}