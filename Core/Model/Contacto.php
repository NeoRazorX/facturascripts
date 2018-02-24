<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Utils;

/**
 * Description of crm_contacto
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Contacto extends Base\Contact
{

    use Base\ModelTrait;

    /**
     * True if it supports marketing, but False.
     *
     * @var bool
     */
    public $admitemarketing;

    /**
     * Last name.
     *
     * @var string
     */
    public $apellidos;

    /**
     * Contact charge.
     *
     * @var string
     */
    public $cargo;

    /**
     * Contact city.
     *
     * @var string
     */
    public $ciudad;

    /**
     * Associated employee has this contact. Agent model.
     *
     * @var string
     */
    public $codagente;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codcontacto;

    /**
     * Contact country.
     *
     * @var string
     */
    public $codpais;

    /**
     * Postal code of the contact.
     *
     * @var string
     */
    public $codpostal;

    /**
     * Address of the contact.
     *
     * @var string
     */
    public $direccion;

    /**
     * Contact company.
     *
     * @var string
     */
    public $empresa;

    /**
     * Contact province.
     *
     * @var string
     */
    public $provincia;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'contactos';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codcontacto';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->admitemarketing = true;
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        parent::test();
        $this->apellidos = Utils::noHtml($this->apellidos);
        $this->cargo = Utils::noHtml($this->cargo);
        $this->ciudad = Utils::noHtml($this->ciudad);
        $this->direccion = Utils::noHtml($this->direccion);
        $this->empresa = Utils::noHtml($this->empresa);
        $this->provincia = Utils::noHtml($this->provincia);

        return true;
    }
}
