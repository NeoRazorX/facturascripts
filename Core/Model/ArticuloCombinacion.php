<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * This model represents the attribute pair => value of the combination of an article with attributes.
 * Note that what is stored are these pairs attribute => value,
 * but the combination of the article is the set, which shares the same code.
 *
 * Example of combination:
 * size => l
 * color => white
 *
 * This results in two articulo_combination objects, both with the same code,
 * but one with name attribute size and value l, and the other with name attribute color
 * and white value.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ArticuloCombinacion extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Primary key. Identifier of this attribute-value pair, not of the combination.
     *
     * @var int
     */
    public $id;

    /**
     * Identifier of the combination.
     * Note that the combination is the sum of all attribute-value pairs.
     *
     * @var string
     */
    public $codigo;

    /**
     * Second identifier for the combination, to facilitate synchronization
     * with woocommerce or prestashop.
     *
     * @var string
     */
    public $codigo2;

    /**
     * Reference of related articles.
     *
     * @var string
     */
    public $referencia;

    /**
     * ID of the attribute value.
     *
     * @var int
     */
    public $idvalor;

    /**
     * Name of the attribute.
     *
     * @var string
     */
    public $nombreatributo;

    /**
     * Value of the attribute.
     *
     * @var string
     */
    public $valor;

    /**
     * Reference of the own combination.
     *
     * @var string
     */
    public $refcombinacion;

    /**
     * Barcode of the combination.
     *
     * @var string
     */
    public $codbarras;

    /**
     * Impact on the price of the item.
     *
     * @var float|int
     */
    public $impactoprecio;

    /**
     * Physical stock of the combination.
     *
     * @var float|int
     */
    public $stockfis;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'articulo_combinaciones';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
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
        new AtributoValor();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->impactoprecio = 0;
        $this->stockfis = 0;
    }

    /**
     * Returns True if there is no erros on properties values.
     */
    public function test()
    {
        if ($this->codigo === null) {
            $this->codigo = (string) $this->newCode();
        }
        
        return true;
    }

    /**
     * Devuelve un array con todos los datos de la combinación con código = $cod,
     * ten en cuenta que lo que se almacenan son los pares atributo => valor.
     *
     * @param string $cod
     *
     * @return self[]
     */
    public function allFromCodigo($cod)
    {
        $where = [new DataBaseWhere('codigo', $cod)];
        $order = ['nombreatributo' => 'ASC'];

        return $this->all($where, $order);
    }
}
