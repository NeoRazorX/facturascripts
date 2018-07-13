<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos García Gómez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Utils;

/**
 * Define method and attributes of table variantes.
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Variante extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Barcode. Maximun 20 characteres.
     *
     * @var string
     */
    public $codbarras;

    /**
     * Cost price.
     *
     * @var int|float
     */
    public $coste;

    /**
     * Producti identifier.
     *
     * @var int
     */
    public $idarticulo;

    /**
     * Foreign key of table atributo_valores.
     *
     * @var int
     */
    public $idatributovalor1;

    /**
     * Foreign key of table atributo_valores.
     *
     * @var int
     */
    public $idatributovalor2;

    /**
     * Primary Key, autoincremental.
     *
     * @var int
     */
    public $idvariante;

    /**
     * Price of the variant. Without tax.
     *
     * @var int|float
     */
    public $precio;

    /**
     * Reference of the variant. Maximun 30 characteres.
     *
     * @var string
     */
    public $referencia;

    /**
     * Sets default values.
     */
    public function clear()
    {
        parent::clear();
        $this->coste = 0.0;
        $this->precio = 0.0;
    }
    
    /**
     * Returns related product.
     *
     * @return Articulo
     */
    public function getArticulo()
    {
        $articulo = new Articulo();
        $articulo->loadFromCode($this->idarticulo);
        return $articulo;
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
        new Articulo();
        new AtributoValor();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idvariante';
    }
    
    public function save()
    {
        if(parent::save()) {
            $articulo = $this->getArticulo();
            $articulo->update();
            return true;
        }
        
        return false;
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'variantes';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->codbarras = Utils::noHtml($this->codbarras);
        $this->referencia = Utils::noHtml($this->referencia);

        return parent::test();
    }
}
