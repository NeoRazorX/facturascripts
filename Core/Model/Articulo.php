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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
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
     * Define the type of item, so you can set distinctions
     * according to one type or another. Varchar (10)
     *
     * @var string
     */
    public $tipo;

    /**
     * Code of the family to which it belongs. In the family class.
     *
     * @var string
     */
    public $codfamilia;

    /**
     * Code of the manufacturer to which it belongs. In the manufacturer class.
     *
     * @var string
     */
    public $codfabricante;

    /**
     * Price of the item, without taxes.
     *
     * @var float|int
     */
    public $pvp;

    /**
     * Stores the value of the pvp before making the change.
     * This value is not stored in the database, that is,
     * is not remembered.
     *
     * @var float|int
     */
    public $pvp_ant;

    /**
     * Average cost when buying the item. Calculated.
     *
     * @var float|int
     */
    public $costemedio;

    /**
     * Cost price manually edited
     * It is not necessarily the purchase price, it can include
     * also other costs.
     *
     * @var float|int
     */
    public $preciocoste;

    /**
     * True => the articles are locked / obsolete.
     *
     * @var bool
     */
    public $bloqueado;

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
     * True -> will be synchronized with the online store.
     *
     * @var bool
     */
    public $publico;

    /**
     * Equivalence code. Varchar (18).
     * Two or more articles are equivalent if they have the same equivalence code.
     *
     * @var string
     */
    public $equivalencia;

    /**
     * True -> allow sales without stock.
     *
     * @var bool
     */
    public $ventasinstock;

    /**
     * Observations of the article.
     *
     * @var string
     */
    public $observaciones;

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
     * Traceability control.
     *
     * @var bool
     */
    public $trazabilidad;

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
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'referencia';
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
     * Returns the stock of the item.
     *
     * @return Stock[]
     */
    public function getStock()
    {
        $stock = new Stock();

        return $this->nostock ? [] : $stock->all([new DataBaseWhere('referencia', $this->referencia)]);
    }

    /**
     * Sets the retail price.
     *
     * @param float $pvp
     */
    public function setPvp($pvp)
    {
        $pvp = round($pvp, FS_NF0 + 2);

        if (!Utils::floatcmp($this->pvp, $pvp, FS_NF0 + 2)) {
            $this->pvp_ant = $this->pvp;
            $this->pvp = $pvp;
        }
    }

    /**
     * Sets the retail price with VAT.
     *
     * @param float $pvp
     */
    public function setPvpIva($pvp)
    {
        $this->setPvp((100 * $pvp) / (100 + $this->getIva()));
    }

    /**
     * Modifies the stock of the item in a specific warehouse.
     * Already responsible for executing save() if necessary.
     *
     * @param string $codalmacen
     * @param int    $cantidad
     *
     * @return bool
     */
    public function setStock($codalmacen, $cantidad = 1)
    {
        if ($this->nostock) {
            return true;
        }

        $result = false;
        $stock = new Stock();
        $encontrado = false;
        foreach ($stock->all([new DataBaseWhere('referencia', $this->referencia)]) as $sto) {
            if ($sto->codalmacen === $codalmacen) {
                $sto->setCantidad($cantidad);
                $result = $sto->save();
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            $stock->referencia = $this->referencia;
            $stock->codalmacen = $codalmacen;
            $stock->setCantidad($cantidad);
            $result = $stock->save();
        }

        if (!$result) {
            self::$miniLog->alert(self::$i18n->trans('error-saving-stock'));
            return false;
        }

        /// this code is highly optimized to save only the changes
        $nuevoStock = $stock->totalFromArticulo($this->referencia);
        if ($this->stockfis !== $nuevoStock) {
            $this->stockfis = $nuevoStock;

            if ($this->exists()) {
                $sql = 'UPDATE ' . static::tableName()
                    . ' SET stockfis = ' . self::$dataBase->var2str($this->stockfis)
                    . ' WHERE referencia = ' . self::$dataBase->var2str($this->referencia) . ';';
                return self::$dataBase->exec($sql);
            }

            if (!$this->save()) {
                self::$miniLog->alert(self::$i18n->trans('error-updating-product-stock'));
                return false;
            }
        }

        return true;
    }

    /**
     * Add the specified amount to the stock of the item in the specified store.
     * Already responsible for executing save() if necessary.
     *
     * @param string $codalmacen
     * @param int    $cantidad
     * @param bool   $recalculaCoste
     * @param string $codcombinacion
     *
     * @return bool
     */
    public function sumStock($codalmacen, $cantidad = 1, $recalculaCoste = false, $codcombinacion = null)
    {
        if ($recalculaCoste) {
            // TODO: Uncomplete condition
            $this->costemedio = 1;
        }

        if ($this->nostock) {
            if ($recalculaCoste) {
                /// this code is highly optimized to save only the changes
                if ($this->exists()) {
                    $sql = 'UPDATE ' . static::tableName()
                        . '  SET costemedio = ' . self::$dataBase->var2str($this->costemedio)
                        . '  WHERE referencia = ' . self::$dataBase->var2str($this->referencia) . ';';
                    return self::$dataBase->exec($sql);
                }

                if (!$this->save()) {
                    self::$miniLog->alert(self::$i18n->trans('error-updating-product-stock'));
                    return false;
                }
            }

            return true;
        }

        $result = false;
        $stock = new Stock();
        $encontrado = false;
        foreach ($stock->all([new DataBaseWhere('referencia', $this->referencia)]) as $sto) {
            if ($sto instanceof Stock && $sto->codalmacen === $codalmacen) {
                $sto->sumCantidad($cantidad);
                $result = $sto->save();
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            $stock->referencia = $this->referencia;
            $stock->codalmacen = $codalmacen;
            $stock->setCantidad($cantidad);
            $result = $stock->save();
        }

        if (!$result) {
            self::$miniLog->alert(self::$i18n->trans('error-saving-stock'));
            return false;
        }

        /// this code is highly optimized to save only the changes
        $nuevoStock = $stock->totalFromArticulo($this->referencia);
        if ($this->stockfis !== $nuevoStock) {
            $this->stockfis = $nuevoStock;

            if ($this->exists()) {
                $sql = 'UPDATE ' . static::tableName()
                    . '  SET stockfis = ' . self::$dataBase->var2str($this->stockfis)
                    . ', costemedio = ' . self::$dataBase->var2str($this->costemedio)
                    . '  WHERE referencia = ' . self::$dataBase->var2str($this->referencia) . ';';
                $result = self::$dataBase->exec($sql);
            } elseif (!$this->save()) {
                self::$miniLog->alert(self::$i18n->trans('error-updating-product-stock'));
                return false;
            }

            /// Any combination?
            if ($codcombinacion !== null && $result) {
                $com0 = new ArticuloCombinacion();
                foreach ($com0->allFromCodigo($codcombinacion) as $combi) {
                    if ($combi instanceof ArticuloCombinacion) {
                        $combi->stockfis += $cantidad;
                        $combi->save();
                    }
                }
            }
        }

        return $result;
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
            $this->stockmax = 0.0;
            $this->stockmin = 0.0;
        }

        if ($this->bloqueado) {
            $this->publico = false;
        }

        return true;
    }
}
