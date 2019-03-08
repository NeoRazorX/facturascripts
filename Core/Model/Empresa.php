<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Dinamic\Lib\RegimenIVA;

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
     * Taxation regime of the provider. For now they are only implemented general and exempt.
     *
     * @var string
     */
    public $regimeniva;

    /**
     * Type of VAT regime
     *
     * @var RegimenIVA
     */
    private static $regimenIVA;

    /**
     * Website of the person.
     *
     * @var string
     */
    public $web;

    /**
     * 
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        if (self::$regimenIVA === null) {
            self::$regimenIVA = new RegimenIVA();
        }

        parent::__construct($data);
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->codpais = AppSettings::get('default', 'codpais');
        $this->regimeniva = self::$regimenIVA->defaultValue();
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->idempresa == AppSettings::get('default', 'idempresa')) {
            self::$miniLog->alert('you-cant-not-remove-default-company');
            return false;
        }

        return parent::delete();
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

        return 'INSERT INTO ' . static::tableName() . ' (idempresa,web,codpais,'
            . 'direccion,administrador,cifnif,nombre,nombrecorto,personafisica,regimeniva)'
            . "VALUES (1,'https://www.facturascripts.com','ESP','C/ Falsa, 123',"
            . "'','00000014Z','Empresa " . $num . " S.L.','E-" . $num . "','0',"
            . "'" . self::$regimenIVA->defaultValue() . "');";
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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'empresas';
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

    protected function createPaymentMethods()
    {
        $formaPago = new FormaPago();
        $formaPago->codpago = $formaPago->newCode();
        $formaPago->descripcion = self::$i18n->trans('default');
        $formaPago->idempresa = $this->idempresa;
        $formaPago->save();
    }

    protected function createWarehouse()
    {
        $almacen = new Almacen();
        $almacen->apartado = $this->apartado;
        $almacen->codalmacen = $almacen->newCode();
        $almacen->ciudad = $this->ciudad;
        $almacen->codpais = $this->codpais;
        $almacen->codpostal = $this->codpostal;
        $almacen->direccion = $this->direccion;
        $almacen->idempresa = $this->idempresa;
        $almacen->nombre = $this->nombrecorto;
        $almacen->provincia = $this->provincia;
        $almacen->telefono = $this->telefono1;
        $almacen->save();
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (parent::saveInsert($values)) {
            $this->createPaymentMethods();
            $this->createWarehouse();
            return true;
        }

        return false;
    }
}
