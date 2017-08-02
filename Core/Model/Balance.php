<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Define que cuentas hay que usar para generar los distintos informes contables.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Balance
{

    use Base\ModelTrait {
        save as private saveTrait;
    }

    /**
     * Clave primaria.
     * @var
     */
    public $codbalance;

    /**
     * TODO
     * @var
     */
    public $descripcion4ba;

    /**
     * TODO
     * @var
     */
    public $descripcion4;

    /**
     * TODO
     * @var
     */
    public $nivel4;

    /**
     * TODO
     * @var
     */
    public $descripcion3;

    /**
     * TODO
     * @var
     */
    public $orden3;

    /**
     * TODO
     * @var
     */
    public $nivel3;

    /**
     * TODO
     * @var
     */
    public $descripcion2;

    /**
     * TODO
     * @var
     */
    public $nivel2;

    /**
     * TODO
     * @var
     */
    public $descripcion1;

    /**
     * TODO
     * @var
     */
    public $nivel1;

    /**
     * TODO
     * @var
     */
    public $naturaleza;

    /**
     * Balance constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'co_codbalances08', 'codbalance');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if ($this->codbalance === null) {
            return 'index.php?page=EditarBalances';
        }
        return 'index.php?page=EditarBalances&cod=' . $this->codbalance;
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     * @return bool
     */
    public function save()
    {
        $this->descripcion1 = static::noHtml($this->descripcion1);
        $this->descripcion2 = static::noHtml($this->descripcion2);
        $this->descripcion3 = static::noHtml($this->descripcion3);
        $this->descripcion4 = static::noHtml($this->descripcion4);
        $this->descripcion4ba = static::noHtml($this->descripcion4ba);

        return $this->saveTrait();
    }
}
