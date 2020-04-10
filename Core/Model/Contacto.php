<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    use Base\PasswordTrait;

    /**
     * True if contact accepts the privacy policy.
     *
     * @var bool
     */
    public $aceptaprivacidad;

    /**
     * True if it supports marketing, but False.
     *
     * @var bool
     */
    public $admitemarketing;

    /**
     * Post office box of the address.
     *
     * @var string
     */
    public $apartado;

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
     * Associated agente to this contact. Agent model.
     *
     * @var string
     */
    public $codagente;

    /**
     * Associated customer to this contact. Customer model.
     *
     * @var string
     */
    public $codcliente;

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
     * Associated supplier to this contact. Supplier model.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Description of the contact.
     *
     * @var string
     */
    public $descripcion;

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
     *
     * @var bool
     */
    public $habilitado;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idcontacto;

    /**
     * Last activity date.
     *
     * @var string
     */
    public $lastactivity;

    /**
     * Last IP used.
     *
     * @var string
     */
    public $lastip;

    /**
     * Indicates the level of security that the contact can access.
     *
     * @var integer
     */
    public $level;

    /**
     * Session key, saved also in cookie. Regenerated when user log in.
     *
     * @var string
     */
    public $logkey;

    /**
     * Contact province.
     *
     * @var string
     */
    public $provincia;

    /**
     *
     * @var integer
     */
    public $puntos;

    /**
     * TRUE if contact is verified.
     *
     * @var bool
     */
    public $verificado;

    /**
     * Returns an unique alias for this contact.
     *
     * @return string
     */
    public function alias()
    {
        if (empty($this->email) || \strpos($this->email, '@') === false) {
            return (string) $this->idcontacto;
        }

        $aux = \explode('@', $this->email);
        switch ($aux[0]) {
            case 'admin':
            case 'info':
                $domain = \explode('.', $aux[1]);
                return $domain[0] . '_' . $this->idcontacto;

            default:
                return $aux[0] . '_' . $this->idcontacto;
        }
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->aceptaprivacidad = false;
        $this->admitemarketing = false;
        $this->codpais = $this->toolBox()->appSettings()->get('default', 'codpais');
        $this->habilitado = true;
        $this->level = 1;
        $this->puntos = 0;
        $this->verificado = false;
    }

    /**
     * 
     * @return string
     */
    public function country()
    {
        $country = new DinPais();
        $where = [new DataBaseWhere('codiso', $this->codpais)];
        if ($country->loadFromCode($this->codpais) || $country->loadFromCode('', $where)) {
            return $this->toolBox()->utils()->fixHtml($country->nombre);
        }

        return $this->codpais;
    }

    /**
     * Returns full name.
     *
     * @return string
     */
    public function fullName()
    {
        return $this->nombre . ' ' . $this->apellidos;
    }

    /**
     * 
     * @param bool $create
     *
     * @return DinCliente
     */
    public function getCustomer($create = true)
    {
        $cliente = new DinCliente();
        if ($this->codcliente && $cliente->loadFromCode($this->codcliente)) {
            return $cliente;
        }

        if ($create) {
            /// creates a new customer
            $cliente->cifnif = $this->cifnif ?? '';
            $cliente->codproveedor = $this->codproveedor;
            $cliente->email = $this->email;
            $cliente->fax = $this->fax;
            $cliente->idcontactoenv = $this->idcontacto;
            $cliente->idcontactofact = $this->idcontacto;
            $cliente->nombre = $this->fullName();
            $cliente->observaciones = $this->observaciones;
            $cliente->personafisica = $this->personafisica;
            $cliente->razonsocial = empty($this->empresa) ? $this->fullName() : $this->empresa;
            $cliente->telefono1 = $this->telefono1;
            $cliente->telefono2 = $this->telefono2;
            if ($cliente->save()) {
                $this->codcliente = $cliente->codcliente;
                $this->save();
            }
        }

        return $cliente;
    }

    /**
     * 
     * @param bool $create
     *
     * @return DinProveedor
     */
    public function getSupplier($create = true)
    {
        $proveedor = new DinProveedor();
        if ($this->codproveedor && $proveedor->loadFromCode($this->codproveedor)) {
            return $proveedor;
        }

        if ($create) {
            /// creates a new supplier
            $proveedor->cifnif = $this->cifnif ?? '';
            $proveedor->codcliente = $this->codcliente;
            $proveedor->email = $this->email;
            $proveedor->fax = $this->fax;
            $proveedor->idcontacto = $this->idcontacto;
            $proveedor->nombre = $this->fullName();
            $proveedor->observaciones = $this->observaciones;
            $proveedor->personafisica = $this->personafisica;
            $proveedor->razonsocial = empty($this->empresa) ? $this->fullName() : $this->empresa;
            $proveedor->telefono1 = $this->telefono1;
            $proveedor->telefono2 = $this->telefono2;
            if ($proveedor->save()) {
                $this->codproveedor = $proveedor->codproveedor;
                $this->save();
            }
        }

        return $proveedor;
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
        /// we need this models to be checked before
        new DinAgente();
        new DinCliente();
        new DinProveedor();

        return parent::install();
    }

    /**
     * Generates a new login key for the user. It also updates lastactivity
     * ans last IP.
     *
     * @param string $ipAddress
     *
     * @return string
     */
    public function newLogkey($ipAddress)
    {
        $this->lastactivity = \date(self::DATETIME_STYLE);
        $this->lastip = $ipAddress;
        $this->logkey = $this->toolBox()->utils()->randomString(99);
        return $this->logkey;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idcontacto';
    }

    /**
     * Returns the name of the column used to describe this item.
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'descripcion';
    }

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
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        if (empty($this->descripcion)) {
            $this->descripcion = empty($this->codcliente) ? $this->fullName() : $this->direccion;
        }

        if (empty($this->nombre)) {
            $this->nombre = $this->descripcion;
        }

        $utils = $this->toolBox()->utils();
        $this->descripcion = $utils->noHtml($this->descripcion);
        $this->apellidos = $utils->noHtml($this->apellidos);
        $this->cargo = $utils->noHtml($this->cargo);
        $this->ciudad = $utils->noHtml($this->ciudad);
        $this->direccion = $utils->noHtml($this->direccion);
        $this->empresa = $utils->noHtml($this->empresa);
        $this->provincia = $utils->noHtml($this->provincia);

        return $this->testPassword() && parent::test();
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListCliente?activetab=List')
    {
        return parent::url($type, $list);
    }

    /**
     * Verifies the login key.
     *
     * @param string $value
     *
     * @return bool
     */
    public function verifyLogkey($value)
    {
        return $this->logkey === $value;
    }
}
