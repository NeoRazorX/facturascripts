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
     * Clave primaria. Identificador de este par atributo-valor, no de la combinación.
     *
     * @var int
     */
    public $id;

    /**
     * Identificador de la combinación.
     * Ten en cuenta que la combinación es la suma de todos los pares atributo-valor.
     *
     * @var string
     */
    public $codigo;

    /**
     * Segundo identificador para la combinación, para facilitar la sincronización
     * con woocommerce o prestashop.
     *
     * @var string
     */
    public $codigo2;

    /**
     * Referencia del artículos relacionado.
     *
     * @var string
     */
    public $referencia;

    /**
     * ID del valor del atributo.
     *
     * @var int
     */
    public $idvalor;

    /**
     * Nombre del atributo.
     *
     * @var string
     */
    public $nombreatributo;

    /**
     * Valor del atributo.
     *
     * @var string
     */
    public $valor;

    /**
     * Referencia de la propia combinación.
     *
     * @var string
     */
    public $refcombinacion;

    /**
     * Código de barras de la combinación.
     *
     * @var string
     */
    public $codbarras;

    /**
     * Impacto en el precio del artículo.
     *
     * @var float|int
     */
    public $impactoprecio;

    /**
     * Stock físico de la combinación.
     *
     * @var float|int
     */
    public $stockfis;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'articulo_combinaciones';
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

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
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
     * Devuelve un nuevo código para una combinación de artículo
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
