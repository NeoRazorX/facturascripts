<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PragmaRX\Google2FA\Google2FA;
use Exception;
use FacturaScripts\Core\Tools;

class TwoFactorManager
{
    private const QR_CODE_SIZE = 400;
    private const VERIFICATION_WINDOW = 8;

    private static $google2fa;

    /**
     * Inicializa Google2FA si no ha sido instanciado.
     */
    private static function getGoogle2FA(): Google2FA
    {
        if (null === self::$google2fa) {
            self::$google2fa = new Google2FA();
        }
        return self::$google2fa;
    }

    /**
     * Genera una nueva clave secreta para la autenticación de dos factores.
     */
    public static function getSecretKey(): string
    {
        try {
            return self::getGoogle2FA()->generateSecretKey();
        } catch (Exception $e) {
            Tools::log()->error('error-generating-secret-key', [
                '%message%' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Genera la URL para el código QR que puede ser escaneado por una aplicación TOTP.
     */
    public static function getQRCodeUrl(string $companyName, string $email, string $secretKey): string
    {
        try {
            return self::getGoogle2FA()->getQRCodeUrl($companyName, $email, $secretKey);
        } catch (Exception $e) {
            Tools::log()->error('error-generating-qr-code-url', [
                '%message%' => $e->getMessage(),
                '%companyName%' => $companyName,
                '%email%' => $email,
                '%secretKey%' => $secretKey,
            ]);
            return '';
        }
    }

    /**
     * Genera una imagen de código QR en formato base64 a partir de una URL.
     */
    public static function getQRCodeImage(string $url): string
    {
        try {
            $options = new QROptions([
                'version' => QRCode::VERSION_AUTO,
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel' => QRCode::ECC_L,
                'scale' => 10,
                'imageBase64' => true,
            ]);

            $qrcode = new QRCode($options);
            return $qrcode->render($url);
        } catch (Exception $e) {
            Tools::log()->error('error-generating-qr-code', [
                '%message%' => $e->getMessage(),
                '%url%' => $url,
            ]);
            return '';
        }
    }

    /**
     * Verifica si un código TOTP es válido.
     */
    public static function verifyCode(string $secretKey, string $code): bool
    {
        try {
            return self::getGoogle2FA()->verifyKey($secretKey, $code, self::VERIFICATION_WINDOW);
        } catch (Exception $e) {
            Tools::log()->error('error-verifying-code', [
                '%message%' => $e->getMessage(),
                '%secretKey%' => $secretKey,
                '%code%' => $code,
            ]);
            return false;
        }
    }
}
