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

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\Vies;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
trait IntracomunitariaTrait
{
    /**
     * Indicates the type of document operation, example: intra-community
     *
     * @var string
     */
    public $operacion;

    /** @var string */
    protected static $operacionIntracomunitaria = 'intracomunitaria';

    /** @var array */
    private static $operationValues = [];

    /** @var array */
    private static $paisesUE = [
        'DE', 'AT', 'BE', 'BG', 'CZ', 'CY', 'HR', 'DK', 'SK', 'SI',
        'EE', 'FI', 'FR', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU',
        'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'GB', 'ES'
    ];

    public static function addOperationValue(string $key, string $value)
    {
        $key = substr($key, 0, 20);
        self::$operationValues[$key] = $value;
    }

    public static function getOperationValues(): array
    {
        $default = [self::$operacionIntracomunitaria => 'intra-community'];
        return array_merge($default, self::$operationValues);
    }

    public function setIntracomunitaria(): bool
    {
        // comprobamos que el país de la empresa este dentro de la UE
        $company = $this->getCompany();
        if (false === in_array(Paises::get($company->codpais)->codiso, self::$paisesUE)) {
            ToolBox::i18nLog()->warning('company-not-in-eu');
            return false;
        }

        // comprobamos que el país del documento cuando es de venta
        // o el país de la dirección de facturación cuando es de compra
        // este dentro de la UE
        $subject = $this->getSubject();
        $country = $subject->modelClassName() === 'Cliente'
            ? Paises::get($this->codpais)
            : Paises::get($subject->getDefaultAddress()->codpais);
        if (false === in_array($country->codiso, self::$paisesUE)) {
            ToolBox::i18nLog()->warning('subject-not-in-eu');
            return false;
        }

        // si el país de la empresa es el mismo que el del cliente/proveedor, no es intracomunitario
        if ($company->codpais === $country->codpais) {
            ToolBox::i18nLog()->warning('company-subject-same-country');
            return false;
        }

        // comprobamos el vies de la empresa
        if (false === $company->checkVies(false)) {
            return false;
        }

        // comprobamos el vies del documento
        if (Vies::check($this->cifnif, $country->codiso) !== 1) {
            return false;
        }

        return true;
    }
}
