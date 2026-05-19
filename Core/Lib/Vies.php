<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Tools;
use SoapClient;

/**
 * Cliente para el servicio VIES (VAT Information Exchange System) de la Comisión
 * Europea, que permite verificar la validez de un número de IVA intracomunitario.
 *
 * Uso típico:
 *
 *     $result = Vies::check('B12345678', 'ES');
 *     if ($result === Vies::RESULT_VALID) { ... }
 *
 * La consulta se hace por SOAP contra ec.europa.eu y por tanto requiere la
 * extensión `soap` de PHP y conectividad de red. Para pruebas unitarias se
 * puede cortocircuitar la llamada con {@see Vies::simulateViesResponse()}.
 *
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class Vies
{
    /** Códigos ISO aceptados por VIES (UE + XI para Irlanda del Norte tras el Brexit). */
    const EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'EL', 'ES', 'FI', 'FR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU',
        'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'XI',
    ];

    const VIES_URL = "https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl";

    /** Timeout (en segundos) aplicado tanto a la conexión SOAP como al socket HTTP. */
    const VIES_TIMEOUT = 10;

    /** El número de IVA existe y está activo en el país indicado. */
    const RESULT_VALID = 1;

    /** El número de IVA tiene formato correcto pero VIES lo da por inválido o inexistente. */
    const RESULT_INVALID = 0;

    /** No se ha podido comprobar (extensión SOAP ausente, país no UE, red caída, etc.). */
    const RESULT_ERROR = -1;

    /** Último mensaje de error devuelto por VIES, accesible mediante {@see getLastError()}. */
    private static $lastError = '';

    /** Respuesta simulada para tests; cuando es distinta de null, {@see check()} la devuelve sin tocar la red. */
    private static $simulatedResponse = null;

    /**
     * Fija una respuesta fija que {@see check()} devolverá sin tocar la red.
     * Pensado exclusivamente para tests; pasar null para restaurar el comportamiento real.
     *
     * @param int|null $response uno de los Vies::RESULT_* o null para desactivar la simulación
     */
    public static function simulateViesResponse(?int $response): void
    {
        self::$simulatedResponse = $response;
    }

    /**
     * Comprueba contra VIES si un número de IVA es válido en el país indicado.
     * El cifnif se normaliza antes de enviarlo (ver {@see normalize()}), por lo
     * que admite indistintamente "B12345678", "ES-B12345678" o "es b12345678".
     *
     * @param string $cifnif número de IVA (con o sin prefijo ISO)
     * @param string $codiso código ISO de país en mayúsculas y dos caracteres
     * @param bool   $msg    si true, registra warnings en el log ante errores de validación
     *
     * @return int uno de Vies::RESULT_VALID, Vies::RESULT_INVALID o Vies::RESULT_ERROR
     */
    public static function check(string $cifnif, string $codiso, bool $msg = true): int
    {
        if (self::$simulatedResponse !== null) {
            return self::$simulatedResponse;
        }

        if (false === extension_loaded('soap')) {
            static::setMessage($msg, 'soap-extension-not-installed');
            return self::RESULT_ERROR;
        }

        if (empty($codiso) || strlen($codiso) !== 2) {
            static::setMessage($msg, 'invalid-iso-code', ['%iso-code%' => $codiso]);
            return self::RESULT_ERROR;
        }

        if (!in_array($codiso, self::EU_COUNTRIES)) {
            static::setMessage($msg, 'country-not-in-eu', ['%codiso%' => $codiso]);
            return self::RESULT_ERROR;
        }

        $cifnif = self::normalize($cifnif, $codiso);

        // longitud mínima razonable de un VAT comunitario tras quitar el prefijo ISO
        if (strlen($cifnif) < 5) {
            static::setMessage($msg, 'vat-number-is-short', ['%vat-number%' => $cifnif]);
            return self::RESULT_ERROR;
        }

        return static::getViesInfo($cifnif, $codiso, $msg);
    }

    /**
     * Normaliza un cifnif para enviarlo a VIES: lo pasa a mayúsculas, descarta
     * separadores habituales (espacios, guiones, puntos, barras...) y, si el
     * resultado empieza por el código ISO indicado, también lo elimina.
     *
     * El codiso debe llegar ya en mayúsculas; si no, no se reconocerá como
     * prefijo y se enviará tal cual a VIES.
     *
     * @param string $cifnif valor original tal como lo introduce el usuario
     * @param string $codiso código ISO de país en mayúsculas
     *
     * @return string cifnif normalizado, sin prefijo ISO
     */
    public static function normalize(string $cifnif, string $codiso): string
    {
        $cifnif = str_replace(['_', '-', '.', ',', '?', '¿', ' ', '/', '\\'], '', strtoupper(trim($cifnif)));

        if (substr($cifnif, 0, 2) === $codiso) {
            $cifnif = substr($cifnif, 2);
        }

        return $cifnif;
    }

    /**
     * Devuelve el mensaje de la última excepción capturada al hablar con VIES,
     * o cadena vacía si la última llamada no produjo error de transporte/SOAP.
     */
    public static function getLastError(): string
    {
        return self::$lastError;
    }

    private static function getViesInfo(string $vatNumber, string $codiso, bool $msg): int
    {
        self::$lastError = '';

        try {
            $context = stream_context_create([
                'http' => ['timeout' => self::VIES_TIMEOUT],
            ]);
            $client = new SoapClient(self::VIES_URL, [
                'exceptions' => true,
                'connection_timeout' => self::VIES_TIMEOUT,
                'cache_wsdl' => WSDL_CACHE_BOTH,
                'stream_context' => $context,
            ]);
            $json = json_encode(
                $client->checkVat([
                    'countryCode' => $codiso,
                    'vatNumber' => $vatNumber,
                ])
            );

            $result = json_decode($json, true);
            if (isset($result["valid"]) && $result["valid"]) {
                return self::RESULT_VALID;
            }

            static::setMessage($msg, 'vat-number-not-valid', ['%vat-number%' => $vatNumber]);
            return self::RESULT_INVALID;
        } catch (Exception $ex) {
            Tools::log('VatInfoFinder')->error($ex->getCode() . ' - ' . $ex->getMessage());
            self::$lastError = $ex->getMessage();

            // VIES devuelve 'INVALID_INPUT' como SoapFault cuando el formato del
            // VAT no es correcto; lo tratamos como inválido, no como fallo de red.
            if ($ex->getMessage() == 'INVALID_INPUT') {
                return self::RESULT_INVALID;
            }
        }

        static::setMessage($msg, 'error-checking-vat-number', ['%vat-number%' => $vatNumber]);
        return self::RESULT_ERROR;
    }

    private static function setMessage(bool $msg, string $txt, array $context = []): void
    {
        if ($msg) {
            Tools::log()->warning($txt, $context);
        }
    }
}
