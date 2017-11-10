<?php
/**
 * This file is part of facturacion_base
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
namespace FacturaScripts\Core\Model;

/**
 * Define que cuentas hay que usar para generar los distintos informes contables.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Balance
{

    use Base\ModelTrait {
        save as private saveTrait;
        url as private traitURL;
    }

    /**
     * Clave primaria.
     *
     * @var string
     */
    public $codbalance;

    /**
     * Descripción 4  del balance
     *
     * @var string
     */
    public $descripcion4ba;

    /**
     * Descripción 4 del balance
     *
     * @var string
     */
    public $descripcion4;

    /**
     * Nivel 4  del balance
     *
     * @var string
     */
    public $nivel4;

    /**
     * Descripción 3 del balance
     *
     * @var string
     */
    public $descripcion3;

    /**
     * Orden 3 del balance
     *
     * @var string
     */
    public $orden3;

    /**
     * Nivel 2 del balance
     *
     * @var string
     */
    public $nivel3;

    /**
     * Descripción 2 del balance
     *
     * @var string
     */
    public $descripcion2;

    /**
     * Nivel 2 del balance
     *
     * @var int
     */
    public $nivel2;

    /**
     * Descripción 1 del balance
     *
     * @var string
     */
    public $descripcion1;

    /**
     * Nivel 1 del balance
     *
     * @var string
     */
    public $nivel1;

    /**
     * Naturaleza del balance
     *
     * @var string
     */
    public $naturaleza;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_codbalances08';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codbalance';
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     *
     * @return bool
     */
    public function save()
    {
        $this->descripcion1 = self::noHtml($this->descripcion1);
        $this->descripcion2 = self::noHtml($this->descripcion2);
        $this->descripcion3 = self::noHtml($this->descripcion3);
        $this->descripcion4 = self::noHtml($this->descripcion4);
        $this->descripcion4ba = self::noHtml($this->descripcion4ba);

        return $this->saveTrait();
    }
}
