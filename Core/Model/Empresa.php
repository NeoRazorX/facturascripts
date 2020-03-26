<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\CuentaBanco as DinCuentaBanco;

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
     *
     * @var int
     */
    public $idlogo;

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
     * Website of the person.
     *
     * @var string
     */
    public $web;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->codpais = $this->toolBox()->appSettings()->get('default', 'codpais');
        $this->regimeniva = RegimenIVA::defaultValue();
    }

    /**
     * Removes company from database.
     *
     * @return bool
     */
    public function delete()
    {
        if ($this->isDefault()) {
            $this->toolBox()->i18nLog()->warning('cant-delete-default-company');
            return false;
        }

        return parent::delete();
    }

    /**
     * Returns the bank accounts associated with the company.
     * 
     * @return DinCuentaBanco[]
     */
    public function getBankAccounts()
    {
        $companyAccounts = new DinCuentaBanco();
        $where = [new DataBaseWhere($this->primaryColumn(), $this->primaryColumnValue())];
        return $companyAccounts->all($where, [], 0, 0);
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
        /// needed dependencies
        new AttachedFile();

        $num = mt_rand(1, 9999);
        $name = \defined('FS_INITIAL_EMPRESA') ? \FS_INITIAL_EMPRESA : 'E-' . $num;
        $codpais = \defined('FS_INITIAL_CODPAIS') ? \FS_INITIAL_CODPAIS : 'ESP';
        return 'INSERT INTO ' . static::tableName() . ' (idempresa,web,codpais,'
            . 'direccion,administrador,cifnif,nombre,nombrecorto,personafisica,regimeniva)'
            . "VALUES (1,'','" . $codpais . "','','','00000014Z','" . $name . "','" . $name . "','0',"
            . "'" . RegimenIVA::defaultValue() . "');";
    }

    /**
     * Returns True if this is the default company.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->idempresa === (int) $this->toolBox()->appSettings()->get('default', 'idempresa');
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
        $utils = $this->toolBox()->utils();
        $this->administrador = $utils->noHtml($this->administrador);
        $this->apartado = $utils->noHtml($this->apartado);
        $this->ciudad = $utils->noHtml($this->ciudad);
        $this->codpostal = $utils->noHtml($this->codpostal);
        $this->direccion = $utils->noHtml($this->direccion);
        $this->nombrecorto = $utils->noHtml($this->nombrecorto);
        $this->provincia = $utils->noHtml($this->provincia);
        $this->web = $utils->noHtml($this->web);

        return parent::test();
    }

    protected function createPaymentMethods()
    {
        $formaPago = new FormaPago();
        $formaPago->codpago = $formaPago->newCode();
        $formaPago->descripcion = $this->toolBox()->i18n()->trans('default');
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
        if (empty($this->idempresa)) {
            $this->idempresa = $this->newCode();
        }

        if (parent::saveInsert($values)) {
            $this->createPaymentMethods();
            $this->createWarehouse();
            return true;
        }

        return false;
    }
}
