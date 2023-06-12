<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * @author Carlos Garcia Gomez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class Vies
{
    const VIES_URL = "https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl";

    public static function check(string $cifnif, string $codiso): int
    {
        // quitamos caracteres especiales del cifnif
        $cifnif = str_replace(['_', '-', '.', ',', '?', '¿', ' ', '/', '\\'], '', strtoupper(trim($cifnif)));

        // si el cifnif tiene menos de 5 caracteres, devolvemos error
        if (strlen($cifnif) < 5) {
            Tools::log()->warning('vat-number-is-short', ['%vat-number%' => $cifnif]);
            return -1;
        }

        // si codiso está vacío o es diferente de 2 caracteres, devolvemos error
        if (empty($codiso) || strlen($codiso) !== 2) {
            Tools::log()->warning('invalid-iso-code', ['%iso-code%' => $codiso]);
            return -1;
        }

        // si existe el codiso al principio del cifnif, lo quitamos
        if (substr($cifnif, 0, 2) === $codiso) {
            $cifnif = substr($cifnif, 2);
        }

        return static::getViesInfo($cifnif, $codiso);
    }

    private static function getViesInfo(string $vatNumber, string $codiso): int
    {
        try {
            $client = new SoapClient(self::VIES_URL, ['exceptions' => true]);
            $json = json_encode(
                $client->checkVat([
                    'countryCode' => $codiso,
                    'vatNumber' => $vatNumber,
                ])
            );

            $result = json_decode($json, true);
            if (isset($result["valid"]) && $result["valid"]) {
                return 1;
            }

            Tools::log()->warning('vat-number-not-vies', ['%vat-number%' => $vatNumber]);
            return 0;
        } catch (Exception $ex) {
            Tools::log('VatInfoFinder')->error($ex->getCode() . ' - ' . $ex->getMessage());
            if ($ex->getMessage() == 'INVALID_INPUT') {
                return 0;
            }
        }

        // se ha producido error al comprobar el VAT number con VIES
        Tools::log()->warning('error-checking-vat-number', ['%vat-number%' => $vatNumber]);
        return -1;
    }
}
