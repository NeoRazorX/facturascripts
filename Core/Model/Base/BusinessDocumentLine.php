<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     */
    public function transfer($fromCodalmacen, $toCodalmacen)
    {
        /// find the stock
        $fromStock = new Stock();
        $where = [
            new DataBaseWhere('codalmacen', $fromCodalmacen),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if ($fromStock->loadFromCode('', $where)) {
            $this->applyStockChanges($this->previousData['actualizastock'], $this->previousData['cantidad'] * -1, $fromStock);
            $fromStock->save();
        } else {
            /// no need to transfer
            return;
        }

        /// find the new stock
        $toStock = new Stock();
        $where2 = [
            new DataBaseWhere('codalmacen', $toCodalmacen),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if (false === $toStock->loadFromCode('', $where2)) {
            /// stock not found, then create one
            $toStock->codalmacen = $toCodalmacen;
            $toStock->idproducto = $this->idproducto;
            $toStock->referencia = $this->referencia;
        }

        $this->applyStockChanges($this->actualizastock, $this->cantidad, $toStock);
        $toStock->save();
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
        if ($type === 'new') {
            return 'Edit' . $name;
        }

        return parent::url($type, 'List' . $name . '?activetab=List');
    }

    /**
     * Apply stock modifications according to $mode.
     *
     * @param int   $mode
     * @param float $quantity
     * @param Stock $stock
     */
    private function applyStockChanges(int $mode, float $quantity, Stock $stock)
    {
        switch ($mode) {
            case 1:
            case -1:
                $stock->cantidad += $mode * $quantity;
                break;

            case 2:
                $stock->pterecibir += $quantity;
                break;

            case -2:
                $stock->reservada += $quantity;
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
                return $this->updateStock();
        }

        return parent::onChange($field);
    }

    /**
     * This method is called after this record is deleted from database.
     */
    protected function onDelete()
    {
        $this->cantidad = 0;
        $this->updateStock();
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if ($this->updateStock()) {
            return parent::saveInsert($values);
        }

        return false;
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['actualizastock', 'cantidad'];
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
        $codalmacen = $this->getDocument()->codalmacen;
        $where2 = [
            new DataBaseWhere('codalmacen', $codalmacen),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if (false === $stock->loadFromCode('', $where2)) {
            /// stock not found, then create one
            $stock->codalmacen = $codalmacen;
            $stock->idproducto = $this->idproducto;
            $stock->referencia = $this->referencia;
        }

        $this->applyStockChanges($this->previousData['actualizastock'], $this->previousData['cantidad'] * -1, $stock);
        $this->applyStockChanges($this->actualizastock, $this->cantidad, $stock);

        /// enough stock?
        if (false === $producto->ventasinstock && $this->actualizastock === -1 && $stock->cantidad < 0) {
            $this->toolBox()->i18nLog()->warning('not-enough-stock', ['%reference%' => $this->referencia]);
            return false;
        }

        if ($stock->save()) {
            $this->pipe('updateStock');
            return true;
        }

        return false;
    }
}
