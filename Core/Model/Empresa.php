<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\RegimenIVA;

/**
 * This class stores the main data of the company.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Empresa extends Base\Contact
{
    use Base\ModelTrait;

    /**
     * Name of the company administrator.
     *
     * @var string
     */
    public $administrador;

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
     * Physical person.
     *
     * @var int
     */
    public $personafisica;

    /**
     * Primary key. Integer.
     *
     * @var int
     */
    public $idempresa;

    /**
     * Short name of the company, to show on the menu.
     *
     * @var string Name to show in the menu.
     */
    public $nombrecorto;

    /**
     * Province of the address.
     *
     * @var string
     */
    public $provincia;

    /**
     * True -> activates the use of an equivalence surcharge on delivery notes and purchase invoices.
     *
     * @var bool
     */
    public $recequivalencia;

    /**
     * VAT regime of the company.
     *
     * @var string
     */
    public $regimeniva;

    /**
     * Website of the person.
     *
     * @var string
     */
    public $web;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'empresas';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idempresa';
    }

    /**
     * Returns the description of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'nombrecorto';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();

        $regimenIVA = new RegimenIVA();
        $this->regimeniva = $regimenIVA::defaultValue();
    }

    /**
     * Check the company's data, return TRUE if correct
     *
     * @return bool
     */
    public function test()
    {
        $this->administrador = Utils::noHtml($this->administrador);
        $this->apartado = Utils::noHtml($this->apartado);
        $this->ciudad = Utils::noHtml($this->ciudad);
        $this->codpostal = Utils::noHtml($this->codpostal);
        $this->direccion = Utils::noHtml($this->direccion);
        $this->nombrecorto = Utils::noHtml($this->nombrecorto);
        $this->provincia = Utils::noHtml($this->provincia);
        $this->web = Utils::noHtml($this->web);

        if (empty($this->idempresa)) {
            $this->idempresa = $this->newCode();
        }

        return parent::test();
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        $num = mt_rand(1, 9999);

        return 'INSERT INTO ' . static::tableName() . ' (idempresa,recequivalencia,web,email,fax,telefono1,codpais,apartado,'
            . 'provincia,ciudad,codpostal,direccion,administrador,cifnif,nombre,nombrecorto,personafisica)'
            . "VALUES (1,NULL,'https://www.facturascripts.com',"
            . "NULL,NULL,NULL,'ESP',NULL,NULL,NULL,NULL,'C/ Falsa, 123','','00000014Z',"
            . "'Empresa " . $num . " S.L.','E-" . $num . "','0');";
    }
}
