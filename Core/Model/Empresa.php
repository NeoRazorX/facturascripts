<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Model\EmpresaSettings;
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

    /** @var string */
    public $administrador;

    /** @var string */
    public $apartado;

    /** @var string */
    public $ciudad;

    /** @var string */
    public $codpais;

    /** @var string */
    public $codpostal;

    /** @var string */
    public $direccion;

    /** @var string */
    public $exceptioniva;

    /** @var int */
    public $idempresa;

    /** @var int */
    public $idlogo;

    /** @var string */
    public $nombrecorto;

    /** @var string */
    public $provincia;

    /** @var string */
    public $regimeniva;

    /** @var string */
    public $web;

    /**
     *
     * @var EmpresaSettings[];
     */
    private $settings = null;

    public function clear()
    {
        parent::clear();
        $this->codpais = $this->toolBox()->appSettings()->get('default', 'codpais');
        $this->regimeniva = RegimenIVA::defaultValue();
    }

    /**
     * Return the value of property in group for company.
     * If there is no default value for the company,
     * the default value of the application is returned.
     *
     * @param string $group
     * @param string $property
     * @param mixed $default
     * @return mixed
     */
    public function config(string $group, string $property, $default = null)
    {
        if (false === isset($this->settings)) {
            $settingsModel = new EmpresaSettings();
            foreach ($settingsModel->all([new DataBaseWhere('idempresa', $this->idempresa)]) as $empresaSettings) {
                $this->settings[$empresaSettings->name] = $empresaSettings;
            }
        }

        $value = isset($this->settings[$group]) ? $this->settings[$group]->__get($property) : '';
        return empty($value)
            ? AppSettings::get($group, $property, $default)
            : $value;
    }

    public function delete(): bool
    {
        if ($this->isDefault()) {
            $this->toolBox()->i18nLog()->warning('cant-delete-default-company');
            return false;
        }

        if (parent::delete()) {
            // limpiamos la caché
            Empresas::clear();
            return true;
        }

        return false;
    }

    /**
     * Returns the bank accounts associated with the company.
     *
     * @return DinCuentaBanco[]
     */
    public function getBankAccounts(): array
    {
        $companyAccounts = new DinCuentaBanco();
        $where = [new DataBaseWhere($this->primaryColumn(), $this->primaryColumnValue())];
        return $companyAccounts->all($where, [], 0, 0);
    }

    public function install(): string
    {
        // needed dependencies
        new AttachedFile();

        $num = mt_rand(1, 9999);
        $name = defined('FS_INITIAL_EMPRESA') ? FS_INITIAL_EMPRESA : 'E-' . $num;
        $codpais = defined('FS_INITIAL_CODPAIS') ? FS_INITIAL_CODPAIS : 'ESP';
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
    public function isDefault(): bool
    {
        return $this->idempresa === (int)$this->toolBox()->appSettings()->get('default', 'idempresa');
    }

    public static function primaryColumn(): string
    {
        return 'idempresa';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombrecorto';
    }

    public function save(): bool
    {
        if (parent::save()) {
            // limpiamos la caché
            Empresas::clear();
            return true;
        }

        return false;
    }

    public static function tableName(): string
    {
        return 'empresas';
    }

    public function test(): bool
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

    protected function createPaymentMethods(): bool
    {
        $formaPago = new FormaPago();
        $formaPago->codpago = $formaPago->newCode();
        $formaPago->descripcion = $this->toolBox()->i18n()->trans('default');
        $formaPago->idempresa = $this->idempresa;
        return $formaPago->save();
    }

    protected function createWarehouse(): bool
    {
        $almacen = new Almacen();
        $almacen->apartado = $this->apartado;
        $almacen->codalmacen = $almacen->newCode();
        $almacen->ciudad = $this->ciudad;
        $almacen->codpais = $this->codpais;
        $almacen->codpostal = $this->codpostal;
        $almacen->direccion = $this->direccion;
        $almacen->idempresa = $this->idempresa;
        $almacen->nombre = $this->nombrecorto ?? $this->nombre;
        $almacen->provincia = $this->provincia;
        $almacen->telefono = $this->telefono1;
        return $almacen->save();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->idempresa)) {
            $this->idempresa = $this->newCode();
        }

        return parent::saveInsert($values) && $this->createPaymentMethods() && $this->createWarehouse();
    }
}
