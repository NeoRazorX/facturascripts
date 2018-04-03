<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Dinamic\Model\Articulo;
use FacturaScripts\Dinamic\Model\Stock;

/**
 * Description of BusinessDocumentLine
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BusinessDocumentLine extends ModelClass
{

    /**
     * True if this states must update product stock.
     *
     * @var int
     */
    public $actualizastock;

    /**
     *
     * @var int
     */
    private $actualizastockAnt;

    /**
     * Quantity.
     *
     * @var float|int
     */
    public $cantidad;

    /**
     *
     * @var float|int
     */
    private $cantidadAnt;

    /**
     * Code of the selected combination, in the case of articles with attributes.
     *
     * @var string
     */
    public $codcombinacion;

    /**
     * Code of the related tax.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Description of the line.
     *
     * @var string
     */
    public $descripcion;

    /**
     * % of the related tax.
     *
     * @var float|int
     */
    public $iva;

    /**
     * % off.
     *
     * @var float|int
     */
    public $dtopor;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idlinea;

    /**
     * % of IRPF of the line.
     *
     * @var float|int
     */
    public $irpf;

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
     * 
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->actualizastockAnt = isset($this->actualizastock) ? $this->actualizastock : 0;
        $this->cantidadAnt = isset($this->cantidad) ? $this->cantidad : 0;
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->actualizastock = 0;
        $this->cantidad = 0.0;
        $this->descripcion = '';
        $this->dtopor = 0.0;
        $this->irpf = 0.0;
        $this->iva = 0.0;
        $this->orden = 0;
        $this->pvpsindto = 0.0;
        $this->pvptotal = 0.0;
        $this->pvpunitario = 0.0;
        $this->recargo = 0.0;
    }

    public function delete()
    {
        if (parent::delete()) {
            $this->cantidad = 0;
            return true;
        }

        return false;
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
        $this->descripcion = Utils::noHtml($this->descripcion);
        $this->pvpsindto = $this->pvpunitario * $this->cantidad;
        $this->pvptotal = $this->pvpsindto * (100 - $this->dtopor) / 100;

        return true;
    }

    public function updateStock(string $codalmacen)
    {
        if ($this->actualizastock === $this->actualizastockAnt && $this->cantidad === $this->cantidadAnt) {
            return true;
        }

        $articulo = new Articulo();
        if (!empty($this->referencia) && $articulo->loadFromCode($this->referencia)) {
            if ($articulo->nostock) {
                return true;
            }

            $stock = new Stock();
            if (!$stock->loadFromCode('', [new DataBaseWhere('codalmacen', $codalmacen), new DataBaseWhere('referencia', $this->referencia)])) {
                $stock->codalmacen = $codalmacen;
                $stock->referencia = $this->referencia;
            }

            $this->applyStockChanges($this->actualizastockAnt, $this->cantidadAnt * -1, $stock);
            $this->applyStockChanges($this->actualizastock, $this->cantidad, $stock);
            $this->actualizastockAnt = $this->actualizastock;
            $this->cantidadAnt = $this->cantidad;
            return $stock->save();
        }

        return true;
    }

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
}
