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
     * Clave primaria.
     *
     * @var string
     */
    public $codcontacto;

    /**
     * NIF del contacto
     *
     * @var string
     */
    public $nif;

    /**
     * True si es una persona física, sino False
     *
     * @var bool
     */
    public $personafisica;

    /**
     * Nombre del contacto
     *
     * @var string
     */
    public $nombre;

    /**
     * Empresa del contacto
     *
     * @var string
     */
    public $empresa;

    /**
     * Cargo del contacto
     *
     * @var string
     */
    public $cargo;

    /**
     * Email de contacto.
     *
     * @var string
     */
    public $email;

    /**
     * Teléfono de la persona.
     *
     * @var string
     */
    public $telefono1;

    /**
     * Teléfono de la persona.
     *
     * @var string
     */
    public $telefono2;

    /**
     * Dirección del contacto.
     *
     * @var string
     */
    public $direccion;

    /**
     * Código postal del contacto.
     *
     * @var string
     */
    public $codpostal;

    /**
     * Ciudad del contacto.
     *
     * @var string
     */
    public $ciudad;

    /**
     * Provincia del contacto.
     *
     * @var string
     */
    public $provincia;

    /**
     * País del contacto.
     *
     * @var string
     */
    public $codpais;

    /**
     * True si admite marketing, sino False
     *
     * @var bool
     */
    public $admitemarketing;

    /**
     * Observaciones del contacto.
     *
     * @var string
     */
    public $observaciones;

    /**
     * Empleado asociado ha este contacto. Modelo agente.
     *
     * @var string
     */
    public $codagente;

    /**
     * Fecha de alta del contacto
     *
     * @var string
     */
    public $fechaalta;

    /**
     * Fecha de la última comunicación
     *
     * @var string
     */
    public $ultima_comunicacion;

    /**
     * Fuente del contacto
     *
     * @var string
     */
    public $fuente;

    /**
     * Estado del contacto
     *
     * @var string
     */
    public $estado;

    /**
     * Potencial cliente
     *
     * @var int
     */
    public $potencial;

    /**
     * Grupo al que pertenece el cliente.
     *
     * @var string
     */
    public $codgrupo;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'crm_contactos';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codcontacto';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
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
     * Devuelve un listado de los estados del contacto
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
     * Devuelve una versión resumida de las observaciones
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
