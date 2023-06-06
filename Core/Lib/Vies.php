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
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Tools;
use SoapClient;

/**
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class Vies
{
    const VIES_URL = "https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl";

    public static function check(string $cifnif, ?string $codiso = null): int
    {
        // quitamos caracteres especiales del cifnif
        $cifnif = str_replace(['_', '-', '.', ',', '?', '¿', ' ', '/', '\\'], '', strtoupper(trim($cifnif)));

        // si el cifnif tiene menos de 5 caracteres, devolvemos error
        if (strlen($cifnif) < 5) {
            ToolBox::i18nLog()->warning('vat-number-is-short', ['%vat-number%' => $cifnif]);
            return -1;
        }

        // si codiso está vacío,
        // obtenemos los primeros caracteres del cifnif mientras sean letras,
        // hasta encontrar números
        if (empty($codiso)) {
            $codiso = '';
            for ($i = 0; $i < strlen($cifnif); $i++) {
                if (ctype_alpha($cifnif[$i])) {
                    $codiso .= $cifnif[$i];
                } else {
                    break;
                }
            }
        }

        // si codiso sigue estando vacío o es diferente de 2 caracteres, devolvemos error
        if (empty($codiso) || strlen($codiso) !== 2) {
            ToolBox::i18nLog()->warning('invalid-iso-code', ['%iso-code%' => $codiso]);
            return -1;
        }

        // si existe el codiso al principio del cifnif, lo quitamos
        if (substr($cifnif, 0, 2) === $codiso) {
            $cifnif = substr($cifnif, 2);
        }

        return static::setViesInfo($cifnif, $codiso);
    }

    private static function setViesInfo(string $vatNumber, string $codiso): int
    {
        try {
            $client = new SoapClient(self::VIES_URL, ['exceptions' => true]);
            $json = json_encode($client->checkVat([
                'countryCode' => $codiso,
                'vatNumber' => $vatNumber,
            ]));

            $result = json_decode($json, true);
            if (isset($result["valid"]) && $result["valid"]) {
                return 1;
            }

            return 0;
        } catch (Exception $ex) {
            Tools::log('VatInfoFinder')->error($ex->getCode() . ' - ' . $ex->getMessage());
            if ($ex->getMessage() == 'INVALID_INPUT') {
                return 0;
            }
        }

        // se ha producido error al comprobar el VAT number con VIES
        ToolBox::i18nLog()->warning('error-checking-vat-number', ['%vat-number%' => $vatNumber]);
        return -1;
    }
}