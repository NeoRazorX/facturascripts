<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2012-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\TaxRelationTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
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

    /** Fecha y hora de la última actualización del producto. @var string */
    public $actualizado;

    /** Indica si el producto está bloqueado u obsoleto. @var bool */
    public $bloqueado;

    /** Código del fabricante del producto. @var string */
    public $codfabricante;

    /** Código de la familia a la que pertenece el producto. @var string */
    public $codfamilia;

    /** Código de la subcuenta contable utilizada para compras. @var string */
    public $codsubcuentacom;

    /** Código de la subcuenta de compras utilizada cuando se aplica IRPF. @var string */
    public $codsubcuentairpfcom;

    /** Código de la subcuenta contable utilizada para ventas. @var string */
    public $codsubcuentaven;

    /** Descripción del producto. @var string */
    public $descripcion;

    /** Código de la excepción de IVA aplicable al producto. @var string */
    public $excepcioniva;

    /** Fecha de alta del producto. @var string */
    public $fechaalta;

    /** Identificador único del producto. @var int */
    public $idproducto;

    /** Indica si el producto no requiere control de stock. @var bool */
    public $nostock;

    /** Observaciones internas sobre el producto. @var string */
    public $observaciones;

    /** Precio de venta del producto sin impuestos. @var float|int */
    public $precio;

    /** Indica si el producto se publica o sincroniza con la tienda online. @var bool */
    public $publico;

    /** Referencia principal o SKU del producto. @var string */
    public $referencia;

    /** Indica si el producto se puede comprar a proveedores. @var bool */
    public $secompra;

    /** Indica si el producto se puede vender a clientes. @var bool */
    public $sevende;

    /** Stock físico total del producto. @var float|int */
    public $stockfis;

    /** Tipo o clasificación adicional del producto. @var string */
    public $tipo;

    /** Indica si se permite vender el producto sin stock disponible. @var bool */
    public $ventasinstock;

    public function __get($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        if ($key === 'precio_iva') {
            return $this->priceWithTax();
        }

        return null;
    }

    public function __isset(string $key): bool
    {
        if (isset($this->attributes[$key])) {
            return true;
        }

        if ($key === 'precio_iva') {
            return true;
        }

        return false;
    }

    public function clear(): void
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
        $this->ventasinstock = (bool)Tools::settings('default', 'ventasinstock', true);
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

    public function getFabricante(): ?Fabricante
    {
        return $this->belongsTo(Fabricante::class, 'codfabricante');
    }

    public function getFamilia(): ?Familia
    {
        return $this->belongsTo(Familia::class, 'codfamilia');
    }

    /**
     * @return ProductoImagen[]
     */
    public function getImages(bool $imgVariant = true): array
    {
        $where = [Where::eq('idproducto', $this->idproducto)];

        // solo si queremos lás imágenes del producto y no de las variantes
        if (false === $imgVariant) {
            $where[] = Where::eq('referencia', null);
        }

        $orderBy = ['orden' => 'ASC'];
        return DinProductoImagen::all($where, $orderBy, 0, 0);
    }

    /**
     * @return Variante[]
     */
    public function getVariants(): array
    {
        return $this->hasMany(Variante::class, 'idproducto');
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

    public function setPriceWithTax(float $price): bool
    {
        $newPrice = (100 * $price) / (100 + $this->getTax()->iva);
        foreach ($this->getVariants() as $variant) {
            if ($variant->referencia == $this->referencia) {
                $variant->precio = round($newPrice, self::ROUND_DECIMALS);
                return $variant->save();
            }
        }

        $this->precio = round($newPrice, self::ROUND_DECIMALS);

        return true;
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
        if ($this->nostock && $this->stockfis != 0 && null !== $this->idproducto) {
            $sql = "DELETE FROM " . Stock::tableName() . " WHERE idproducto = " . self::db()->var2str($this->idproducto)
                . "; UPDATE " . Variante::tableName() . " SET stockfis = 0 WHERE idproducto = "
                . self::db()->var2str($this->idproducto) . ";";
            self::db()->exec($sql);
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

        return $this->testTax() && parent::test();
    }

    protected function testTax(): bool
    {
        $tax = $this->getTax();

        // si el producto tiene impuesto, y el impuesto es 0, debe tener una excepción de iva
        if (!empty($this->codimpuesto) && $tax->iva == 0 && empty($this->excepcioniva)) {
            Tools::log()->warning('product-without-tax-exception', ['%reference%' => $this->referencia]);
        }

        // si el producto tiene una excepción de iva, debe tener un impuesto a 0
        if (!empty($this->excepcioniva) && empty($this->codimpuesto) && $tax->iva != 0) {
            Tools::log()->warning('product-with-tax-exception', ['%reference%' => $this->referencia]);
            return false;
        }

        // si el producto tiene una excepción de iva, no puede tener un impuesto distinto a 0
        if (!empty($this->excepcioniva) && !empty($this->codimpuesto) && $tax->iva != 0) {
            Tools::log()->warning('product-with-tax-exception-distinct-cero', ['%reference%' => $this->referencia]);
            return false;
        }

        // si el producto no tiene una excepción de iva, debe tener un impuesto
        if (!empty($this->excepcioniva) && empty($this->codimpuesto)) {
            Tools::log()->warning('product-without-tax', ['%reference%' => $this->referencia]);
            return false;
        }

        return true;
    }

    /**
     * Updated product price or reference if any change in variants.
     */
    public function updateInfo(): void
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

    protected function saveInsert(): bool
    {
        // comprobamos si la referencia ya existe
        $where = [Where::eq('referencia', $this->referencia)];
        if ($this->count($where) > 0) {
            Tools::log()->warning('duplicated-reference', ['%reference%' => $this->referencia]);
            return false;
        }

        if (false === parent::saveInsert()) {
            return false;
        }

        $variant = new DinVariante();
        $variant->idproducto = $this->idproducto;
        $variant->precio = $this->precio;
        $variant->referencia = $this->referencia;
        $variant->stockfis = $this->stockfis;
        if (false === $variant->save()) {
            $this->delete();
            return false;
        }

        return true;
    }
}
