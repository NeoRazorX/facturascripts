<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Validator;
use FacturaScripts\Dinamic\Model\Agente as DinAgente;
use FacturaScripts\Dinamic\Model\Cliente as DinCliente;
use FacturaScripts\Dinamic\Model\Pais as DinPais;
use FacturaScripts\Dinamic\Model\Proveedor as DinProveedor;

/**
 * Description of crm_contacto
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Contacto extends Base\Contact
{
    use Base\ModelTrait;

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

    /** @var int */
    public $idcontacto;

    /** @var string */
    public $provincia;

    /** @var bool */
    public $verificado;

    /** @var string */
    public $web;

    public function alias(): string
    {
        if (empty($this->email) || strpos($this->email, '@') === false) {
            return (string)$this->idcontacto;
        }

        $aux = explode('@', $this->email);
        switch ($aux[0]) {
            case 'admin':
            case 'info':
                $domain = explode('.', $aux[1]);
                return $domain[0] . '_' . $this->idcontacto;

            default:
                return $aux[0] . '_' . $this->idcontacto;
        }
    }

    public function checkVies(bool $msg = true): bool
    {
        $codiso = Paises::get($this->codpais)->codiso ?? '';
        return Vies::check($this->cifnif ?? '', $codiso, $msg) === 1;
    }

    public function clear()
    {
        parent::clear();
        $this->aceptaprivacidad = false;
        $this->admitemarketing = false;
        $this->codpais = Tools::settings('default', 'codpais');
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
        $country = new DinPais();
        $where = [new DataBaseWhere('codiso', $this->codpais)];
        if ($country->loadFromCode($this->codpais) || $country->loadFromCode('', $where)) {
            return Tools::fixHtml($country->nombre) ?? '';
        }

        return $this->codpais ?? '';
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
        if ($this->codcliente && $cliente->loadFromCode($this->codcliente)) {
            return $cliente;
        }

        if ($create) {
            // obtenemos los campos que no comparten los modelos para excluirlos.
            $exclude = $this->obtenerCamposNoCoincidentes($this->getModelFields(), $cliente->getModelFields());

            // incluimos los datos de los campos coincidentes en ambos modelos al nuevo modelo
            $cliente->loadFromData($this->toArray(), $exclude);

            // adaptamos algunos datos al nuevo modelo
            $cliente->cifnif = $this->cifnif ?? '';
            $cliente->idcontactoenv = $this->idcontacto;
            $cliente->idcontactofact = $this->idcontacto;
            $cliente->nombre = $this->fullName();
            $cliente->razonsocial = empty($this->empresa) ? $this->fullName() : $this->empresa;

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
        if ($this->codproveedor && $proveedor->loadFromCode($this->codproveedor)) {
            return $proveedor;
        }

        if ($create) {
            // obtenemos los campos que no comparten los modelos para excluirlos.
            $exclude = $this->obtenerCamposNoCoincidentes($this->getModelFields(), $proveedor->getModelFields());

            // incluimos los datos de los campos coincidentes en ambos modelos al nuevo modelo
            $proveedor->loadFromData($this->toArray(), $exclude);

            // adaptamos algunos datos al nuevo modelo
            $proveedor->cifnif = $this->cifnif ?? '';
            $proveedor->nombre = $this->fullName();
            $proveedor->razonsocial = empty($this->empresa) ? $this->fullName() : $this->empresa;

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
        new DinAgente();
        new DinCliente();
        new DinProveedor();

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

        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->apellidos = Tools::noHtml($this->apellidos) ?? '';
        $this->cargo = Tools::noHtml($this->cargo) ?? '';
        $this->ciudad = Tools::noHtml($this->ciudad) ?? '';
        $this->direccion = Tools::noHtml($this->direccion) ?? '';
        $this->empresa = Tools::noHtml($this->empresa) ?? '';
        $this->provincia = Tools::noHtml($this->provincia) ?? '';
        $this->web = Tools::noHtml($this->web) ?? '';

        // comprobamos si la web es una url válida
        if (!empty($this->web) && false === Validator::url($this->web)) {
            Tools::log()->warning('invalid-web', ['%web%' => $this->web]);
            return false;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListCliente?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    private function obtenerCamposNoCoincidentes($arrayUno, $arrayDos)
    {
        // Obtener las claves de ambos arrays
        $clavesUno = array_keys($arrayUno);
        $clavesDos = array_keys($arrayDos);

        // Obtener las claves que están en $arrayUno pero no en $arrayDos
        $clavesSoloEnUno = array_diff($clavesUno, $clavesDos);

        // Obtener las claves que están en $arrayDos pero no en $arrayUno
        $clavesSoloEnDos = array_diff($clavesDos, $clavesUno);

        // Combinar ambas listas de claves no coincidentes
        $clavesNoCoincidentes = array_merge($clavesSoloEnUno, $clavesSoloEnDos);

        return $clavesNoCoincidentes;
    }
}
