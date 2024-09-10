<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;

/**
 * Description of Address abstract class.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class Address extends ModelClass
{
    /**
     * Post office box of the address.
     *
     * @var string
     */
    public $apartado;

    /**
     * City of the address.
     *
     * @var string
     */
    public $ciudad;

    /**
     * Country of the address.
     *
     * @var string
     */
    public $codpais;

    /**
     * Postal code of the address.
     *
     * @var string
     */
    public $codpostal;

    /**
     * Address.
     *
     * @var string
     */
    public $direccion;

    /**
     * Province of the address.
     *
     * @var string
     */
    public $provincia;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->codpais = Tools::settings('default', 'codpais');
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test(): bool
    {
        $this->apartado = Tools::noHtml($this->apartado);
        $this->ciudad = Tools::noHtml($this->ciudad);
        $this->direccion = Tools::noHtml($this->direccion);
        $this->provincia = Tools::noHtml($this->provincia);

        return parent::test();
    }
}
