<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Stock;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of BusinessDocumentLine
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BusinessDocumentLine extends ModelOnChangeClass
{
    use TaxRelationTrait;

    /**
     * Update stock status.
     *
     * @var int
     */
    public $actualizastock;

    /**
     * Quantity.
     *
     * @var float|int
     */
    public $cantidad;

    /**
     * Description of the line.
     *
     * @var string
     */
    public $descripcion;

    /** @var bool */
    private $disableUpdateStock = false;

    /** @var array */
    protected static $dont_copy_fields = ['idlinea', 'orden', 'servido'];

    /**
     * Percentage of discount.
     *
     * @var float|int
     */
    public $dtopor;

    /**
     * Percentage of second discount.
     *
     * @var float|int
     */
    public $dtopor2;

    /** @var string */
    public $excepcioniva;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idlinea;

    /** @var int */
    public $idproducto;

    /**
     * % of IRPF of the line.
     *
     * @var float|int
     */
    public $irpf;

    /**
     * % of the related tax.
     *
     * @var float|int
     */
    public $iva;

    /**
     * Position of the line in the document. The higher down.
     *
     * @var int
     */
    public $orden;

    /**
     * Net amount without discounts.
     *
     * @var float|int
     */
    public $pvpsindto;

    /**
     * Net amount of the line, without taxes.
     *
     * @var float|int
     */
    public $pvptotal;

    /**
     * Price of the item, one unit.
     *
     * @var float|int
     */
    public $pvpunitario;

    /**
     * % surcharge of line equivalence.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Reference of the article.
     *
     * @var string
     */
    public $referencia;

    /**
     * Served.
     *
     * @var float|int
     */
    public $servido;

    /** @var bool */
    public $suplido;

    /**
     * Returns the parent document of this line.
     */
    abstract public function getDocument();

    /**
     * Returns the name of the column to store the document's identifier.
     */
    abstract public function documentColumn();

    public function clear()
    {
        parent::clear();
        $this->actualizastock = 0;
        $this->cantidad = 1.0;
        $this->descripcion = '';
        $this->dtopor = 0.0;
        $this->dtopor2 = 0.0;
        $this->irpf = 0.0;
        $this->orden = 0;
        $this->pvpsindto = 0.0;
        $this->pvptotal = 0.0;
        $this->pvpunitario = 0.0;
        $this->servido = 0.0;
        $this->suplido = false;

        // default tax
        $this->codimpuesto = Tools::settings('default', 'codimpuesto');
        $this->iva = $this->getTax()->iva;
        $this->recargo = $this->getTax()->recargo;
    }

    public function disableUpdateStock(bool $value): void
    {
        $this->disableUpdateStock = $value;
    }

    /**
     * Returns the identifier of the document.
     *
     * @return int
     */
    public function documentColumnValue()
    {
        return $this->{$this->documentColumn()};
    }

    public static function dontCopyField(string $field): void
    {
        static::$dont_copy_fields[] = $field;
    }

    public static function dontCopyFields(): array
    {
        $more = [static::primaryColumn()];
        return array_merge(static::$dont_copy_fields, $more);
    }

    public function getDisableUpdateStock(): bool
    {
        return $this->disableUpdateStock;
    }

    /**
     * Returns the Equivalent Unified Discount.
     *
     * @return float
     */
    public function getEUDiscount()
    {
        $eud = 1.0;
        foreach ([$this->dtopor, $this->dtopor2] as $dto) {
            $eud *= 1 - $dto / 100;
        }

        return $eud;
    }

    public function getProducto(): Producto
    {
        $producto = new Producto();

        // for backward compatibility we must search by reference
        if (empty($this->idproducto) && !empty($this->referencia)) {
            $this->idproducto = $this->getVariante()->idproducto;
        }

        $producto->loadFromCode($this->idproducto);
        return $producto;
    }

    public function getVariante(): Variante
    {
        $variante = new Variante();
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        $variante->loadFromCode('', $where);
        return $variante;
    }

    public function install(): string
    {
        // needed dependencies
        new Impuesto();
        new Producto();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idlinea';
    }

    public function save(): bool
    {
        $done = parent::save();
        $this->disableUpdateStock(false);
        return $done;
    }

    public function setPriceWithTax(float $price): void
    {
        $newPrice = (100 * $price) / (100 + $this->getTax()->iva);
        $this->pvpunitario = round($newPrice, Producto::ROUND_DECIMALS);
    }

    /**
     * Transfers the line stock from one warehouse to another.
     *
     * @param string $fromCodalmacen
     * @param string $toCodalmacen
     *
     * @return bool
     */
    public function transfer($fromCodalmacen, $toCodalmacen): bool
    {
        // find the stock
        $fromStock = new Stock();
        $where = [
            new DataBaseWhere('codalmacen', $fromCodalmacen),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if (empty($this->referencia) || false === $fromStock->loadFromCode('', $where)) {
            // no need to transfer
            return true;
        }
        $this->applyStockChanges($fromStock, $this->previousData['actualizastock'], $this->previousData['cantidad'] * -1, $this->previousData['servido'] * -1);
        $fromStock->save();

        // find the new stock
        $toStock = new Stock();
        $where2 = [
            new DataBaseWhere('codalmacen', $toCodalmacen),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if (false === $toStock->loadFromCode('', $where2)) {
            // stock not found, then create one
            $toStock->codalmacen = $toCodalmacen;
            $toStock->idproducto = $this->idproducto ?? $this->getProducto()->idproducto;
            $toStock->referencia = $this->referencia;
        }

        $this->applyStockChanges($toStock, $this->actualizastock, $this->cantidad, $this->servido);
        if ($toStock->save()) {
            $this->pipe('transfer', $fromCodalmacen, $toCodalmacen, $this->getDocument());
            return true;
        }

        return false;
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test(): bool
    {
        if (empty($this->codimpuesto)) {
            $this->codimpuesto = null;
        }

        if ($this->servido < 0 && $this->cantidad >= 0) {
            $this->servido = 0.0;
        }

        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->referencia = Tools::noHtml($this->referencia);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        $name = str_replace('Linea', '', $this->modelClassName());
        return $type === 'new' ? 'Edit' . $name : parent::url($type, 'List' . $name . '?activetab=List');
    }

    /**
     * Apply stock modifications according to $mode.
     *
     * @param Stock $stock
     * @param int $mode
     * @param float $quantity
     * @param float $served
     */
    private function applyStockChanges(&$stock, int $mode, float $quantity, float $served)
    {
        if ($quantity < 0 && $served < $quantity) {
            $served = $quantity;
        }

        switch ($mode) {
            case 1:
            case -1:
                $stock->cantidad += $mode * ($quantity - $served);
                break;

            case 2:
                $stock->pterecibir += $quantity - $served;
                break;

            case -2:
                $stock->reservada += $quantity - $served;
                break;
        }
    }

    /**
     * This method is called before save (update) in the database this record
     * data when some field value has changed.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'actualizastock':
            case 'cantidad':
            case 'servido':
                return $this->updateStock() && parent::onChange($field);
        }

        return parent::onChange($field);
    }

    /**
     * This method is called after this record is deleted from database.
     */
    protected function onDelete()
    {
        $this->cantidad = 0.0;
        $this->updateStock();
        parent::onDelete();
    }

    protected function saveInsert(array $values = []): bool
    {
        return $this->updateStock() && parent::saveInsert($values);
    }

    protected function setPreviousData(array $fields = [])
    {
        $more = ['actualizastock', 'cantidad', 'servido'];
        parent::setPreviousData(array_merge($more, $fields));

        if (null === $this->previousData['actualizastock']) {
            $this->previousData['actualizastock'] = 0;
        }

        if (null === $this->previousData['cantidad']) {
            $this->previousData['cantidad'] = 0.0;
        }
    }

    /**
     * Updates stock according to line data and $codalmacen warehouse.
     *
     * @return bool
     */
    protected function updateStock(): bool
    {
        if ($this->disableUpdateStock) {
            return true;
        } elseif (empty($this->actualizastock) && empty($this->previousData['actualizastock'])) {
            return true;
        }

        // find the variant
        $variante = new Variante();
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        if (empty($this->referencia) || false === $variante->loadFromCode('', $where)) {
            return true;
        }

        // find the product
        $producto = $variante->getProducto();
        if ($producto->nostock) {
            return true;
        }

        // find the stock
        $stock = new Stock();
        $doc = $this->getDocument();
        $where2 = [
            new DataBaseWhere('codalmacen', $doc->codalmacen),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if (false === $stock->loadFromCode('', $where2)) {
            // stock not found, then create one
            $stock->codalmacen = $doc->codalmacen;
            $stock->idproducto = $this->idproducto ?? $this->getProducto()->idproducto;
            $stock->referencia = $this->referencia;
        }

        $this->applyStockChanges($stock, $this->previousData['actualizastock'], $this->previousData['cantidad'] * -1, $this->previousData['servido'] * -1);
        $this->applyStockChanges($stock, $this->actualizastock, $this->cantidad, $this->servido);

        // enough stock?
        if (false === $producto->ventasinstock && $this->actualizastock === -1 && $stock->cantidad < 0) {
            Tools::log()->warning('not-enough-stock', ['%reference%' => $this->referencia]);
            return false;
        }

        if ($stock->save()) {
            $this->pipe('updateStock', $doc);
            $this->disableUpdateStock = true;
            return true;
        }

        return false;
    }
}
