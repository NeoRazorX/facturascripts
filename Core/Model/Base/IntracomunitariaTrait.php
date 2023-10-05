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

use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Tools;

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

    public function setIntracomunitaria(): bool
    {
        // comprobamos que el país de la empresa este dentro de la UE
        $company = $this->getCompany();
        if (false === Paises::miembroUE($company->codpais)) {
            Tools::log()->warning('company-not-in-eu');
            return false;
        }

        // comprobamos que el país del documento cuando es de venta
        // o el país de la dirección de facturación cuando es de compra
        // este dentro de la UE
        $subject = $this->getSubject();
        $country = property_exists($this, 'codpais')
            ? Paises::get($this->codpais)
            : Paises::get($subject->getDefaultAddress()->codpais);
        if (false === Paises::miembroUE($country->codpais)) {
            Tools::log()->warning('subject-not-in-eu');
            return false;
        }

        // si el país de la empresa es el mismo que el del cliente/proveedor, no es intracomunitario
        if ($company->codpais === $country->codpais) {
            Tools::log()->warning('company-subject-same-country');
            return false;
        }

        // comprobamos el vies de la empresa
        if (false === $company->checkVies()) {
            return false;
        }

        // comprobamos el vies del documento
        if (Vies::check($this->cifnif, $country->codiso) === 1) {
            $this->operacion = InvoiceOperation::INTRA_COMMUNITY;
            return true;
        }

        return false;
    }
}
