<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\Base;

/**
 * Esta clase agrupa los datos de contacto para un uso genérico.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
trait ContactInformation
{

    /**
     * Teléfono de contacto.
     *
     * @var string
     */
    public $telefono;

    /**
     * Número de fax del contacto.
     *
     * @var string
     */
    public $fax;

    /**
     * Email de contacto.
     *
     * @var string
     */
    public $email;

    /**
     * Página web del contacto.
     *
     * @var string
     */
    public $web;

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
     * Apartado de correos del contacto.
     *
     * @var string
     */
    public $apartado;

    /**
     * Ciudad del contacto.
     *
     * @var string
     */
    public $ciudad;

    /**
     * Nombre de la población del contacto.
     *
     * @var string
     */
    public $poblacion;

    /**
     * Provincia del contacto.
     *
     * @var string
     */
    public $provincia;

    /**
     * Código que representa al páis donde está el contacto.
     *
     * @var string
     */
    public $codpais;

    /**
     * Inicializa los valores del contacto.
     */
    private function clearContactInformation()
    {
        $this->telefono = null;
        $this->fax = null;
        $this->email = null;
        $this->web = null;
        $this->direccion = null;
        $this->codpostal = null;
        $this->apartado = null;
        $this->ciudad = null;
        $this->poblacion = null;
        $this->provincia = null;
        $this->codpais = null;
    }
}
