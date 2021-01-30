<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

    /**
     *
     * @var bool
     */
    private $disableUpdateStock = false;

    /**
     * Percentage of discount.
     *
     * @var float|int
     */
    public $dtopor;

    /**
     * Percentage of seccond discount.
     *
     * @var float|int
     */
    public $dtopor2;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idlinea;

    /**
     *
     * @var int
     */
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

    /**
     *
     * @var bool
     */
    public $suplido;

    /**
     * Returns the parent document of this line.
     */
    abstract public function getDocument();

    /**
     * Returns the name of the column to store the document's identifier.
     */
    abstract public function documentColumn();

    /**
     * Reset the values of all model properties.
     */
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

        /// default tax
        $this->codimpuesto = $this->toolBox()->appSettings()->get('default', 'codimpuesto');
        $this->iva = $this->getTax()->iva;
        $this->recargo = $this->getTax()->recargo;
    }

    /**
     * 
     * @param bool $value
     */
    public function disableUpdateStock(bool $value)
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

    /**
     * Returns related product.
     *
     * @return Producto
     */
    public function getProducto()
    {
        $producto = new Producto();

        /// for backward compatibility we must search by reference
        if (empty($this->idproducto) && !empty($this->referencia)) {
            $variante = new Variante();
            $where = [new DataBaseWhere('referencia', $this->referencia)];
            if ($variante->loadFromCode('', $where)) {
                $this->idproducto = $variante->idproducto;
            }
        }

        $producto->loadFromCode($this->idproducto);
        return $producto;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new Impuesto();
        new Producto();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idlinea';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        if (empty($this->codimpuesto)) {
            $this->codimpuesto = null;
        }

        if ($this->servido < 0 && $this->cantidad >= 0) {
            $this->servido = 0.0;
        }

        $utils = $this->toolBox()->utils();
        $this->descripcion = $utils->noHtml($this->descripcion);
        $this->pvpsindto = $this->pvpunitario * $this->cantidad;
        $this->pvptotal = $this->pvpsindto * $this->getEUDiscount();
        $this->referencia = $utils->noHtml($this->referencia);
        return parent::test();
    }

    /**
     * Transfers the line stock from one warehouse to another.
     * 
     * @param string $fromCodalmacen
     * @param string $toCodalmacen
     *
     * @return bool
     */
    public function transfer($fromCodalmacen, $toCodalmacen)
    {
        /// find the stock
        $fromStock = new Stock();
        $where = [
            new DataBaseWhere('codalmacen', $fromCodalmacen),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if (empty($this->referencia) || false === $fromStock->loadFromCode('', $where)) {
            /// no need to transfer
            return true;
        }
        $this->applyStockChanges($fromStock, $this->previousData['actualizastock'], $this->previousData['cantidad'] * -1, $this->previousData['servido'] * -1);
        $fromStock->save();

        /// find the new stock
        $toStock = new Stock();
        $where2 = [
            new DataBaseWhere('codalmacen', $toCodalmacen),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if (false === $toStock->loadFromCode('', $where2)) {
            /// stock not found, then create one
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
     * Custom url method.
     *
     * @param string $type
     * @param string $list
     * 
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        $name = \str_replace('Linea', '', $this->modelClassName());
        return $type === 'new' ? 'Edit' . $name : parent::url($type, 'List' . $name . '?activetab=List');
    }

    /**
     * Apply stock modifications according to $mode.
     * 
     * @param Stock $stock
     * @param int   $mode
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
                return $this->updateStock();
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

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        return $this->updateStock() && parent::saveInsert($values);
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['actualizastock', 'cantidad', 'servido'];
        parent::setPreviousData(\array_merge($more, $fields));

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
    protected function updateStock()
    {
        if ($this->disableUpdateStock) {
            return true;
        } elseif (empty($this->actualizastock) && empty($this->previousData['actualizastock'])) {
            return true;
        }

        /// find the variant
        $variante = new Variante();
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        if (empty($this->referencia) || false === $variante->loadFromCode('', $where)) {
            return true;
        }

        /// find the product
        $producto = $variante->getProducto();
        if ($producto->nostock) {
            return true;
        }

        /// find the stock
        $stock = new Stock();
        $doc = $this->getDocument();
        $where2 = [
            new DataBaseWhere('codalmacen', $doc->codalmacen),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if (false === $stock->loadFromCode('', $where2)) {
            /// stock not found, then create one
            $stock->codalmacen = $doc->codalmacen;
            $stock->idproducto = $this->idproducto ?? $this->getProducto()->idproducto;
            $stock->referencia = $this->referencia;
        }

        $this->applyStockChanges($stock, $this->previousData['actualizastock'], $this->previousData['cantidad'] * -1, $this->previousData['servido'] * -1);
        $this->applyStockChanges($stock, $this->actualizastock, $this->cantidad, $this->servido);

        /// enough stock?
        if (false === $producto->ventasinstock && $this->actualizastock === -1 && $stock->cantidad < 0) {
            $this->toolBox()->i18nLog()->warning('not-enough-stock', ['%reference%' => $this->referencia]);
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
