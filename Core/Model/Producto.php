<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2012-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Model\Base\TaxRelationTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Fabricante as DinFabricante;
use FacturaScripts\Dinamic\Model\Familia as DinFamilia;
use FacturaScripts\Dinamic\Model\ProductoImagen as DinProductoImagen;
use FacturaScripts\Dinamic\Model\Variante as DinVariante;

/**
 * Stores the data of an article.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Producto extends ModelClass
{
    use ModelTrait;
    use TaxRelationTrait;

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
     * Account code for purchases.
     *
     * @var string
     */
    public $codsubcuentacom;

    /**
     * Code for the shopping account, but with IRPF.
     *
     * @var string
     */
    public $codsubcuentairpfcom;

    /**
     * Account code for sales.
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

    /** @var string */
    public $excepcioniva;

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

    /** @var string */
    public $tipo;

    /**
     * True -> allow sales without stock.
     *
     * @var bool
     */
    public $ventasinstock;

    public function __get($name)
    {
        if ($name === 'precio_iva') {
            return $this->priceWithTax();
        }

        return null;
    }

    public function clear()
    {
        parent::clear();
        $this->actualizado = Tools::dateTime();
        $this->bloqueado = false;
        $this->codimpuesto = Tools::settings('default', 'codimpuesto');
        $this->fechaalta = Tools::date();
        $this->nostock = false;
        $this->precio = 0.0;
        $this->publico = false;
        $this->secompra = true;
        $this->sevende = true;
        $this->stockfis = 0.0;
        $this->ventasinstock = (bool)Tools::settings('default', 'ventasinstock', false);
    }

    public function delete(): bool
    {
        // comprobamos si podemos eliminar las variantes
        foreach ($this->getVariants() as $variant) {
            if ($variant->isInDocuments()) {
                Tools::log()->warning('cant-delete-variant-with-documents', ['%reference%' => $variant->referencia]);
                return false;
            }
        }

        // eliminamos las imágenes del producto
        foreach ($this->getImages() as $image) {
            if (false === $image->delete()) {
                return false;
            }
        }

        // eliminamos el resto de la base de datos
        return parent::delete();
    }

    public function getFabricante(): Fabricante
    {
        $fabricante = new DinFabricante();
        $fabricante->loadFromCode($this->codfabricante);
        return $fabricante;
    }

    public function getFamilia(): Familia
    {
        $familia = new DinFamilia();
        $familia->loadFromCode($this->codfamilia);
        return $familia;
    }

    /**
     * @return ProductoImagen[]
     */
    public function getImages(bool $imgVariant = true): array
    {
        $image = new DinProductoImagen();
        $where = [new DataBaseWhere('idproducto', $this->idproducto)];

        // solo si queremos lás imágenes del producto y no de las variantes
        if (false === $imgVariant) {
            $where[] = new DataBaseWhere('referencia', null);
        }

        $orderBy = ['orden' => 'ASC'];
        return $image->all($where, $orderBy, 0, 0);
    }

    /**
     * @return Variante[]
     */
    public function getVariants(): array
    {
        $variantModel = new DinVariante();
        $where = [new DataBaseWhere('idproducto', $this->idproducto)];
        return $variantModel->all($where, [], 0, 0);
    }

    public function install(): string
    {
        /**
         * products table has several foreign keys, so we must force the checking of those tables.
         */
        new Fabricante();
        new Familia();
        new Impuesto();

        return parent::install();
    }

    public function priceWithTax(): float
    {
        return $this->precio * (100 + $this->getTax()->iva) / 100;
    }

    public static function primaryColumn(): string
    {
        return 'idproducto';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'referencia';
    }

    public function setPriceWithTax(float $price)
    {
        $newPrice = (100 * $price) / (100 + $this->getTax()->iva);
        foreach ($this->getVariants() as $variant) {
            if ($variant->referencia == $this->referencia) {
                $variant->precio = round($newPrice, self::ROUND_DECIMALS);
                return $variant->save();
            }
        }

        $this->precio = round($newPrice, self::ROUND_DECIMALS);
    }

    public static function tableName(): string
    {
        return 'productos';
    }

    public function test(): bool
    {
        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->observaciones = Tools::noHtml($this->observaciones);
        $this->referencia = Tools::noHtml($this->referencia);

        // descripción y observaciones no pueden ser null
        if ($this->descripcion === null) {
            $this->descripcion = '';
        }
        if ($this->observaciones === null) {
            $this->observaciones = '';
        }

        if (empty($this->referencia)) {
            // obtenemos una nueva referencia de variantes, en lugar del producto
            $variant = new DinVariante();
            $this->referencia = (string)$variant->newCode('referencia');
        }
        if (strlen($this->referencia) > 30) {
            Tools::log()->warning(
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
            $this->sevende = false;
            $this->secompra = false;
        }

        $this->actualizado = Tools::dateTime();

        return parent::test();
    }

    /**
     * Updated product price or reference if any change in variants.
     */
    public function update(): void
    {
        $newPrecio = 0.0;
        $newReferencia = null;

        // recorremos las variantes y actualizamos el precio y la referencia
        foreach ($this->getVariants() as $variant) {
            if ($variant->referencia === $this->referencia) {
                $newPrecio = $variant->precio;
                $newReferencia = $variant->referencia;
                break;
            }

            if (is_null($newReferencia)) {
                $newPrecio = $variant->precio;
                $newReferencia = $variant->referencia;
            }
        }

        // si hay cambios, actualizamos el producto
        if ($newPrecio != $this->precio || $newReferencia !== $this->referencia) {
            $this->precio = $newPrecio;
            $this->referencia = $newReferencia;
            $this->save();
        }
    }

    protected function saveInsert(array $values = []): bool
    {
        // comprobamos si la referencia ya existe
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        if ($this->count($where) > 0) {
            Tools::log()->warning('duplicated-reference', ['%reference%' => $this->referencia]);
            return false;
        }

        if (false === parent::saveInsert($values)) {
            return false;
        }

        $variant = new DinVariante();
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
}
