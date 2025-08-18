<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Model\Base\EmailAndPhonesTrait;
use FacturaScripts\Core\Model\Base\FiscalNumberTrait;
use FacturaScripts\Core\Model\Base\GravatarTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Validator;
use FacturaScripts\Dinamic\Model\Cliente as DinCliente;
use FacturaScripts\Dinamic\Model\Proveedor as DinProveedor;

/**
 * Description of crm_contacto
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Contacto extends ModelClass
{
    use ModelTrait;
    use EmailAndPhonesTrait;
    use FiscalNumberTrait;
    use GravatarTrait;

    /** @var bool */
    public $aceptaprivacidad;

    /** @var bool */
    public $admitemarketing;

    /** @var string */
    public $apartado;

    /** @var string */
    public $apellidos;

    /** @var string */
    public $cargo;

    /** @var string */
    public $ciudad;

    /** @var string */
    public $codagente;

    /** @var string */
    public $codcliente;

    /** @var string */
    public $codpais;

    /** @var string */
    public $codpostal;

    /** @var string */
    public $codproveedor;

    /** @var string */
    public $descripcion;

    /** @var string */
    public $direccion;

    /** @var string */
    public $empresa;

    /** @var string */
    public $fechaalta;

    /** @var int */
    public $idcontacto;

    /** @var string */
    public $langcode;

    /** @var string */
    public $nombre;

    /** @var string */
    public $observaciones;

    /** @var bool */
    public $personafisica;

    /** @var string */
    public $provincia;

    /** @var bool */
    public $verificado;

    /** @var string */
    public $web;

    public function checkVies(bool $msg = true): bool
    {
        $codiso = Paises::get($this->codpais)->codiso ?? '';
        return Vies::check($this->cifnif ?? '', $codiso, $msg) === 1;
    }

    public function clear(): void
    {
        parent::clear();
        $this->aceptaprivacidad = false;
        $this->admitemarketing = false;
        $this->codpais = Tools::settings('default', 'codpais');
        $this->fechaalta = Tools::date();
        $this->personafisica = true;
        $this->tipoidfiscal = Tools::settings('default', 'tipoidfiscal');
        $this->verificado = false;
    }

    /**
     * @param string $query
     * @param string $fieldCode
     * @param DataBaseWhere[] $where
     *
     * @return CodeModel[]
     */
    public function codeModelSearch(string $query, string $fieldCode = '', array $where = []): array
    {
        $results = [];
        $field = empty($fieldCode) ? $this->primaryColumn() : $fieldCode;
        $fields = 'apellidos|cifnif|descripcion|email|empresa|nombre|observaciones|telefono1|telefono2';
        $where[] = new DataBaseWhere($fields, mb_strtolower($query, 'UTF8'), 'LIKE');
        foreach ($this->all($where) as $item) {
            $results[] = new CodeModel(['code' => $item->{$field}, 'description' => $item->fullName()]);
        }
        return $results;
    }

    public function country(): string
    {
        return Tools::fixHtml(Paises::get($this->codpais)->nombre) ?? $this->codpais ?? '';
    }

    /**
     * Returns full name.
     *
     * @return string
     */
    public function fullName(): string
    {
        return $this->nombre . ' ' . $this->apellidos;
    }

    public function getCustomer(bool $create = true): Cliente
    {
        $cliente = new DinCliente();
        if ($this->codcliente && $cliente->load($this->codcliente)) {
            return $cliente;
        }

        if ($create) {
            // creates a new customer
            $cliente->cifnif = $this->cifnif ?? '';
            $cliente->codagente = $this->codagente;
            $cliente->codproveedor = $this->codproveedor;
            $cliente->email = $this->email;
            $cliente->fax = $this->fax;
            $cliente->idcontactoenv = $this->idcontacto;
            $cliente->idcontactofact = $this->idcontacto;
            $cliente->langcode = $this->langcode;
            $cliente->nombre = $this->fullName();
            $cliente->observaciones = $this->observaciones;
            $cliente->personafisica = $this->personafisica;
            $cliente->razonsocial = empty($this->empresa) ? $this->fullName() : $this->empresa;
            $cliente->telefono1 = $this->telefono1;
            $cliente->telefono2 = $this->telefono2;
            $cliente->tipoidfiscal = $this->tipoidfiscal;
            $cliente->web = $this->web;
            if ($cliente->save()) {
                $this->codcliente = $cliente->codcliente;
                $this->save();
            }
        }

        return $cliente;
    }

    public function getSupplier(bool $create = true): Proveedor
    {
        $proveedor = new DinProveedor();
        if ($this->codproveedor && $proveedor->load($this->codproveedor)) {
            return $proveedor;
        }

        if ($create) {
            // creates a new supplier
            $proveedor->cifnif = $this->cifnif ?? '';
            $proveedor->codcliente = $this->codcliente;
            $proveedor->email = $this->email;
            $proveedor->fax = $this->fax;
            $proveedor->idcontacto = $this->idcontacto;
            $proveedor->langcode = $this->langcode;
            $proveedor->nombre = $this->fullName();
            $proveedor->observaciones = $this->observaciones;
            $proveedor->personafisica = $this->personafisica;
            $proveedor->razonsocial = empty($this->empresa) ? $this->fullName() : $this->empresa;
            $proveedor->telefono1 = $this->telefono1;
            $proveedor->telefono2 = $this->telefono2;
            $proveedor->tipoidfiscal = $this->tipoidfiscal;
            $proveedor->web = $this->web;
            if ($proveedor->save()) {
                $this->codproveedor = $proveedor->codproveedor;
                $this->save();
            }
        }

        return $proveedor;
    }

    public function install(): string
    {
        // we need this models to be checked before
        new Agente();
        new Cliente();
        new Pais();
        new Proveedor();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idcontacto';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'descripcion';
    }

    public static function tableName(): string
    {
        return 'contactos';
    }

    public function test(): bool
    {
        if (empty($this->nombre) && empty($this->email) && empty($this->direccion)) {
            Tools::log()->warning('empty-contact-data');
            return false;
        }

        if (empty($this->descripcion)) {
            $this->descripcion = empty($this->codcliente) && empty($this->codproveedor) ?
                $this->fullName() :
                $this->direccion;
        }

        $this->apartado = Tools::noHtml($this->apartado) ?? '';
        $this->apellidos = Tools::noHtml($this->apellidos) ?? '';
        $this->cargo = Tools::noHtml($this->cargo) ?? '';
        $this->ciudad = Tools::noHtml($this->ciudad) ?? '';
        $this->codpostal = Tools::noHtml($this->codpostal) ?? '';
        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->direccion = Tools::noHtml($this->direccion) ?? '';
        $this->empresa = Tools::noHtml($this->empresa) ?? '';
        $this->langcode = Tools::noHtml($this->langcode) ?? '';
        $this->nombre = Tools::noHtml($this->nombre) ?? '';
        $this->observaciones = Tools::noHtml($this->observaciones) ?? '';
        $this->provincia = Tools::noHtml($this->provincia) ?? '';
        $this->web = Tools::noHtml($this->web) ?? '';

        // comprobamos si la web es una url válida
        if (!empty($this->web) && false === Validator::url($this->web)) {
            Tools::log()->warning('invalid-web', ['%web%' => $this->web]);
            return false;
        }

        return parent::test() && $this->testEmailAndPhones() && $this->testFiscalNumber();
    }

    public function url(string $type = 'auto', string $list = 'ListCliente?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    public function delete(): bool
    {
        $deleted = parent::delete();

        if ($deleted){
            $sql = 'UPDATE ' . Cliente::tableName() . ' SET idcontactoenv = NULL WHERE idcontactoenv = ' . $this->idcontacto . ';';
            $sql .= 'UPDATE ' . Cliente::tableName() . ' SET idcontactofact = NULL WHERE idcontactofact = ' . $this->idcontacto . ';';
            $sql .= 'UPDATE ' . Proveedor::tableName() . ' SET idcontacto = NULL WHERE idcontacto = ' . $this->idcontacto;

            return self::$dataBase->exec($sql);
        }

        return $deleted;
    }
}
