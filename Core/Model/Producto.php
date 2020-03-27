<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2012-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Stores the data of an article.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Producto extends Base\ModelClass
{

    use Base\ModelTrait;
    use Base\TaxRelationTrait;

    const ROUND_DECIMALS = 5;

    /**
     * Date when this product was updated.
     *
     * @var string
     */
    public $actualizado;

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
     * Sub-account code for sales.
     *
     * @var string
     */
    public $codsubcuentaven;

    /**
     * Description of the product.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Date on which the product was registered.
     *
     * @var string
     */
    public $fechaalta;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idproducto;

    /**
     * True -> do not control the stock.
     * Activating it implies putting True $ventasinstock;
     *
     * @var bool
     */
    public $nostock;

    /**
     * Observations of the article.
     *
     * @var string
     */
    public $observaciones;

    /**
     * Price of the item, without taxes.
     *
     * @var float|int
     */
    public $precio;

    /**
     * True -> will be synchronized with the online store.
     *
     * @var bool
     */
    public $publico;

    /**
     * Main product reference or SKU.
     *
     * @var string
     */
    public $referencia;

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
     * Physical stock.
     *
     * @var float|int
     */
    public $stockfis;

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
        $this->actualizado = \date(self::DATETIME_STYLE);
        $this->bloqueado = false;
        $this->codimpuesto = $this->toolBox()->appSettings()->get('default', 'codimpuesto');
        $this->fechaalta = \date(self::DATE_STYLE);
        $this->nostock = false;
        $this->precio = 0.0;
        $this->publico = false;
        $this->secompra = true;
        $this->sevende = true;
        $this->stockfis = 0.0;
        $this->ventasinstock = (bool) $this->toolBox()->appSettings()->get('default', 'ventasinstock', false);
    }

    /**
     * 
     * @return Variante[]
     */
    public function getVariants()
    {
        $variantModel = new Variante();
        $where = [new DataBaseWhere('idproducto', $this->idproducto)];
        return $variantModel->all($where, [], 0, 0);
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

        return parent::install();
    }

    /**
     * 
     * @return float
     */
    public function priceWithTax()
    {
        return $this->precio * (100 + $this->getTax()->iva) / 100;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idproducto';
    }

    /**
     * 
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'referencia';
    }

    /**
     * 
     * @param float $price
     */
    public function setPriceWithTax($price)
    {
        $newPrice = (100 * $price) / (100 + $this->getTax()->iva);
        foreach ($this->getVariants() as $variant) {
            if ($variant->referencia == $this->referencia) {
                $variant->precio = \round($newPrice, self::ROUND_DECIMALS);
                return $variant->save();
            }
        }

        $this->precio = \round($newPrice, self::ROUND_DECIMALS);
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'productos';
    }

    /**
     * Returns True if the article's data is correct.
     *
     * @return bool
     */
    public function test()
    {
        $utils = $this->toolBox()->utils();
        $this->descripcion = $utils->noHtml($this->descripcion);
        $this->observaciones = $utils->noHtml($this->observaciones);
        $this->referencia = $utils->noHtml($this->referencia);

        if (\strlen($this->referencia) < 1 || \strlen($this->referencia) > 30) {
            $this->toolBox()->i18nLog()->warning(
                'invalid-column-lenght',
                ['%value%' => $this->referencia, '%column%' => 'referencia', '%min%' => '1', '%max%' => '30']
            );
            return false;
        }

        if ($this->nostock && $this->stockfis != 0 && null !== $this->idproducto) {
            $sql = "DELETE FROM " . Stock::tableName() . " WHERE idproducto = " . self::$dataBase->var2str($this->idproducto)
                . "; UPDATE " . Variante::tableName() . " SET stockfis = 0 WHERE idproducto = "
                . self::$dataBase->var2str($this->idproducto) . ";";
            self::$dataBase->exec($sql);
        }

        if ($this->nostock) {
            $this->stockfis = 0.0;
            $this->ventasinstock = true;
        }

        if ($this->bloqueado) {
            $this->publico = false;
        }

        $this->actualizado = \date(self::DATETIME_STYLE);
        return parent::test();
    }

    /**
     * Updated product price or reference if any change in variants.
     */
    public function update()
    {
        $newPrecio = 0.0;
        $newReferencia = null;

        foreach ($this->getVariants() as $variant) {
            if ($variant->referencia == $this->referencia || \is_null($newReferencia)) {
                $newPrecio = $variant->precio;
                $newReferencia = $variant->referencia;
                break;
            }
        }

        if ($newPrecio != $this->precio || $newReferencia != $this->referencia) {
            $this->precio = $newPrecio;
            $this->referencia = $newReferencia;
            $this->save();
        }
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (parent::saveInsert($values)) {
            $variant = new Variante();
            $variant->idproducto = $this->idproducto;
            $variant->precio = $this->precio;
            $variant->referencia = $this->referencia;
            $variant->stockfis = $this->stockfis;
            if ($variant->save()) {
                return true;
            }

            $this->delete();
            return false;
        }

        return false;
    }
}
