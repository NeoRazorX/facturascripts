<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use PragmaRX\Google2FA\Google2FA;
use Exception;
use FacturaScripts\Core\Tools;

class TwoFactorManager
{
    // Constantes configurables
    private const QR_CODE_SIZE = 400;
    private const VERIFICATION_WINDOW = 8;

    // Instancia de Google2FA reutilizable
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
     *
     * @return string La clave secreta generada.
     */
    public static function getSecretKey(): string
    {
        return self::getGoogle2FA()->generateSecretKey();
    }

    /**
     * Genera la URL para el código QR que puede ser escaneado por una aplicación TOTP.
     *
     * @param string $companyName Nombre de la compañía.
     * @param string $email Correo electrónico del usuario.
     * @param string $secretKey La clave secreta generada.
     * @return string La URL del código QR.
     */
    public static function getQRCodeUrl(string $companyName, string $email, string $secretKey): string
    {
        return self::getGoogle2FA()->getQRCodeUrl($companyName, $email, $secretKey);
    }

    /**
     * Genera una imagen de código QR en formato base64 a partir de una URL.
     *
     * @param string $url La URL del código QR.
     * @return string La imagen del código QR codificada en base64.
     * @throws Exception Si ocurre un error al generar la imagen.
     */
    public static function getQRCodeImage(string $url): string
    {
        try {
            $QRcode = QrCode::create($url)
                ->setSize(self::QR_CODE_SIZE)
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $writer = new PngWriter();
            $result = $writer->write($QRcode);
            return $result->getDataUri();
            /*$writer = new Writer(
                new ImageRenderer(
                    new RendererStyle(self::QR_CODE_SIZE),
                    new ImagickImageBackEnd()
                )
            );

            return base64_encode($writer->writeString($url));*/
        } catch (Exception $e) {
            // Loguea el error si ocurre
            Tools::log()->error("Error generating QR code: " . $e->getMessage());
            throw new Exception("Failed to generate QR code image.");
        }
    }

    /**
     * Verifica si un código TOTP es válido.
     *
     * @param string $secretKey La clave secreta asociada con el usuario.
     * @param string $code El código TOTP introducido por el usuario.
     * @return bool Verdadero si el código es válido, falso si no lo es.
     */
    public static function verifyCode(string $secretKey, string $code): bool
    {
        return self::getGoogle2FA()->verifyKey($secretKey, $code, self::VERIFICATION_WINDOW);
    }
}
