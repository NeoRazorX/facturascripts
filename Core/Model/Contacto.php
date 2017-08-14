<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
 * @author Carlos García Gómez
 */
class crm_contacto
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     * @var type 
     */
    public $codcontacto;
    public $nif;
    public $personafisica;
    public $nombre;
    public $empresa;
    public $cargo;
    public $email;
    public $telefono1;
    public $telefono2;
    public $direccion;
    public $codpostal;
    public $ciudad;
    public $provincia;
    public $codpais;
    public $admitemarketing;
    public $observaciones;
    public $codagente;
    public $fechaalta;
    public $ultima_comunicacion;
    public $fuente;
    public $estado;
    public $potencial;
    public $codgrupo;

    public function tableName()
    {
        return 'crm_contactos';
    }

    public function primaryColumn()
    {
        return 'codcontacto';
    }

    public function clear()
    {
        $this->codcontacto = NULL;
        $this->nif = '';
        $this->personafisica = TRUE;
        $this->nombre = '';
        $this->empresa = NULL;
        $this->cargo = NULL;
        $this->email = NULL;
        $this->telefono1 = NULL;
        $this->telefono2 = NULL;
        $this->direccion = NULL;
        $this->codpostal = NULL;
        $this->ciudad = NULL;
        $this->provincia = NULL;
        $this->codpais = NULL;
        $this->admitemarketing = TRUE;
        $this->codagente = NULL;
        $this->observaciones = '';
        $this->fechaalta = date('d-m-Y');
        $this->ultima_comunicacion = date('d-m-Y');
        $this->fuente = NULL;
        $this->estado = 'nuevo';
        $this->potencial = 0;
        $this->codgrupo = NULL;
    }

    public function estados()
    {
        return array(
            'nuevo', 'potencial', 'cliente', 'no interesado'
        );
    }

    public function observaciones_resume()
    {
        if ($this->observaciones == '') {
            return '-';
        } else if (strlen($this->observaciones) < 60) {
            return $this->observaciones;
        }

        return substr($this->observaciones, 0, 57) . '...';
    }

    public function url()
    {
        if (is_null($this->codcontacto)) {
            return 'index.php?page=crm_contactos';
        }

        return 'index.php?page=ver_crm_contacto&cod=' . $this->codcontacto;
    }
}
