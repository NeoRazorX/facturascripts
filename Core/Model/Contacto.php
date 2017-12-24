<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * Description of crm_contacto
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Contacto
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codcontacto;

    /**
     * Contact NIF.
     *
     * @var string
     */
    public $nif;

    /**
     * True if it is a physical person, but False.
     *
     * @var bool
     */
    public $personafisica;

    /**
     * Contact name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Contact company.
     *
     * @var string
     */
    public $empresa;

    /**
     * Contact charge.
     *
     * @var string
     */
    public $cargo;

    /**
     * Contact email.
     *
     * @var string
     */
    public $email;

    /**
     * Phone 1 of the person.
     *
     * @var string
     */
    public $telefono1;

    /**
     * Phone 2 of the person.
     *
     * @var string
     */
    public $telefono2;

    /**
     * Address of the contact.
     *
     * @var string
     */
    public $direccion;

    /**
     * Postal code of the contact.
     *
     * @var string
     */
    public $codpostal;

    /**
     * Contact city.
     *
     * @var string
     */
    public $ciudad;

    /**
     * Contact province.
     *
     * @var string
     */
    public $provincia;

    /**
     * Contact country.
     *
     * @var string
     */
    public $codpais;

    /**
     * True if it supports marketing, but False.
     *
     * @var bool
     */
    public $admitemarketing;

    /**
     * Contact's observations.
     *
     * @var string
     */
    public $observaciones;

    /**
     * Associated employee has this contact. Agent model.
     *
     * @var string
     */
    public $codagente;

    /**
     * Contact's date of registration.
     *
     * @var string
     */
    public $fechaalta;

    /**
     * Date of the last communication.
     *
     * @var string
     */
    public $ultima_comunicacion;

    /**
     * Contact source.
     *
     * @var string
     */
    public $fuente;

    /**
     * Contact status.
     *
     * @var string
     */
    public $estado;

    /**
     * Potential client.
     *
     * @var int
     */
    public $potencial;

    /**
     * Group to which the client belongs.
     *
     * @var string
     */
    public $codgrupo;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'crm_contactos';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codcontacto';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->codcontacto = null;
        $this->nif = '';
        $this->personafisica = true;
        $this->nombre = '';
        $this->empresa = null;
        $this->cargo = null;
        $this->email = null;
        $this->telefono1 = null;
        $this->telefono2 = null;
        $this->direccion = null;
        $this->codpostal = null;
        $this->ciudad = null;
        $this->provincia = null;
        $this->codpais = null;
        $this->admitemarketing = true;
        $this->codagente = null;
        $this->observaciones = '';
        $this->fechaalta = date('d-m-Y');
        $this->ultima_comunicacion = date('d-m-Y');
        $this->fuente = null;
        $this->estado = 'nuevo';
        $this->potencial = 0;
        $this->codgrupo = null;
    }

    /**
     * Returns a list of contact states.
     *
     * @return array
     */
    public function estados()
    {
        return [
            'nuevo', 'potencial', 'cliente', 'no interesado',
        ];
    }

    /**
     * Returns a summarized version of the observations.
     *
     * @return string
     */
    public function observacionesResume()
    {
        if ($this->observaciones == '') {
            return '-';
        }
        if (strlen($this->observaciones) < 60) {
            return $this->observaciones;
        }

        return substr($this->observaciones, 0, 57) . '...';
    }
}
