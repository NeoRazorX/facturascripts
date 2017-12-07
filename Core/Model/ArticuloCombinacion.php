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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Este modelo representa el par atributo => valor de la combinación de un artículo con atributos.
 * Ten en cuenta que lo que se almacena son estos pares atributo => valor,
 * pero la combinación del artículo es el conjunto, que comparte el mismo código.
 *
 * Ejemplo de combinación:
 * talla => l
 * color => blanco
 *
 * Esto se traduce en dos objetos articulo_combinación, ambos con el mismo código,
 * pero uno con nombreatributo talla y valor l, y el otro con nombreatributo color
 * y valor blanco.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ArticuloCombinacion
{

    use Base\ModelTrait;

    /**
     * Primary key. Identifier of this value by attribute, not of the combination.
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
     * Related articles reference.
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
     * Reference of the combination itself.
     *
     * @var string
     */
    public $refcombinacion;

    /**
     * Barcode combination.
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
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table.
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// nos aseguramos de que existan las tablas necesarias
        //new Atributo();
        new AtributoValor();

        return '';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->id = null;
        $this->codigo = null;
        $this->codigo2 = null;
        $this->referencia = null;
        $this->idvalor = null;
        $this->nombreatributo = null;
        $this->valor = null;
        $this->refcombinacion = null;
        $this->codbarras = null;
        $this->impactoprecio = 0;
        $this->stockfis = 0;
    }

    public function test()
    {
        if ($this->codigo === null) {
            $this->codigo = (string) $this->getNewCodigo();
        }
    }

    /**
     * Returns a new code for an article combination
     *
     * @return int
     */
    private function getNewCodigo()
    {
        $sql = 'SELECT MAX(' . $this->dataBase->sql2Int('codigo') . ') as cod FROM ' . $this->tableName() . ';';
        $cod = $this->dataBase->select($sql);
        if (!empty($cod)) {
            return 1 + (int) $cod[0]['cod'];
        }

        return 1;
    }

    /**
     * Returns an array with all the data of the combination with código = $cod,
     * keep in mind that what are stored are the attribute pairs => value.
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
