<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Un concepto predefinido para una partida (la línea de un asiento contable).
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ConceptoPartida
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     *
     * @var string
     */
    public $idconceptopar;

    /**
     * Concepto de la partida
     *
     * @var string
     */
    public $concepto;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_conceptospar';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idconceptopar';
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
    public function test()
    {
        $this->concepto = self::noHtml($this->concepto);

        return true;
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     *
     * @return bool
     */
    public function save()
    {
        return false;
    }
}
