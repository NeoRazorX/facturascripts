<?php
/**
 * This file is part of facturacion_base
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Un Valor para un atributo de artículos.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AtributoValor
{

    use Base\ModelTrait;

    /**
     * Clave primaria
     *
     * @var int
     */
    public $id;

    /**
     * Código del atributo relacionado.
     *
     * @var string
     */
    public $codatributo;

    /**
     * Valor del atributo
     *
     * @var string
     */
    public $valor;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'atributos_valores';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    public function install()
    {
        new Atributo();

        return '';
    }

    public function test()
    {
        $this->valor = self::noHtml($this->valor);
        return true;
    }

    /**
     * Selecciona todos los atributos de un código de atributo
     *
     * @param string $cod
     *
     * @return self[]
     */
    public function allFromAtributo($cod)
    {
        $where = [new DataBaseWhere('codatributo', $cod)];
        $order = ['valor' => 'ASC'];
        return $this->all($where, $order);
    }
}
