<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2012-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Utils;

/**
 * Stores the data of an article.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Articulo extends Base\Product
{

    use Base\ModelTrait;

    /**
     * True => the articles are locked / obsolete.
     *
     * @var bool
     */
    public $bloqueado;

    /**
     * Code of the manufacturer to which it belongs. In the manufacturer class.
     *
     * @var string
     */
    public $codfabricante;

    /**
     * Code of the family to which it belongs. In the family class.
     *
     * @var string
     */
    public $codfamilia;

    /**
     * Sub-account code for purchases.
     *
     * @var string
     */
    public $codsubcuentacom;

    /**
     * Code for the shopping sub-account, but with IRPF.
     *
     * @var string
     */
    public $codsubcuentairpfcom;

    /**
     * Average cost when buying the item. Calculated.
     *
     * @var float|int
     */
    public $costemedio;

    /**
     * Equivalence code. Varchar (18).
     * Two or more articles are equivalent if they have the same equivalence code.
     *
     * @var string
     */
    public $equivalencia;

    /**
     * Observations of the article.
     *
     * @var string
     */
    public $observaciones;

    /**
     * Cost price manually edited
     * It is not necessarily the purchase price, it can include
     * also other costs.
     *
     * @var float|int
     */
    public $preciocoste;

    /**
     * True -> will be synchronized with the online store.
     *
     * @var bool
     */
    public $publico;

    /**
     * Price of the item, without taxes.
     *
     * @var float|int
     */
    public $pvp;

    /**
     * True => the item is purchased.
     *
     * @var bool
     */
    public $secompra;

    /**
     * True => the item is sold.
     *
     * @var bool
     */
    public $sevende;

    /**
     * Defines the type of item, so you can set distinctions
     * according to one type or another. Varchar (10)
     *
     * @var string
     */
    public $tipo;

    /**
     * Traceability control.
     *
     * @var bool
     */
    public $trazabilidad;

    /**
     * True -> allow sales without stock.
     *
     * @var bool
     */
    public $ventasinstock;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->costemedio = 0.0;
        $this->preciocoste = 0.0;
        $this->pvp = 0.0;
        $this->secompra = true;
        $this->sevende = true;
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
        /**
         * The articles table has several foreign keys, so we must force the checking of those tables.
         */
        new Fabricante();
        new Familia();
        new Impuesto();

        return '';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'referencia';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'articulos';
    }

    /**
     * Returns True if the article's data is correct.
     *
     * @return bool
     */
    public function test()
    {
        $this->observaciones = Utils::noHtml($this->observaciones);

        if ($this->equivalencia === '') {
            $this->equivalencia = null;
        }

        if ($this->nostock) {
            $this->ventasinstock = true;
        }

        if ($this->bloqueado) {
            $this->publico = false;
        }

        return parent::test();
    }
}
