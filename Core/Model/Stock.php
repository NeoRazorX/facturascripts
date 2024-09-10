<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\ProductRelationTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Almacen as DinAlmacen;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;
use FacturaScripts\Dinamic\Model\Variante as DinVariante;

/**
 * The quantity in inventory of an item in a particular warehouse.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Stock extends ModelClass
{
    use ModelTrait;
    use ProductRelationTrait;

    const MAX_DECIMALS = 3;

    /**
     * Quantity.
     *
     * @var float|int
     */
    public $cantidad;

    /**
     * Warehouse code.
     *
     * @var string
     */
    public $codalmacen;

    /**
     * Available. Is the quantity minus reserved.
     *
     * @var float|int
     */
    public $disponible;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idstock;

    /**
     * Pending receipt. Merchandise pending receipt from the supplier.
     *
     * @var float|int
     */
    public $pterecibir;

    /**
     * Reference.
     *
     * @var string
     */
    public $referencia;

    /**
     * Reserved on customer orders.
     *
     * @var float|int
     */
    public $reservada;

    /**
     * Maximum stock.
     *
     * @var float|int
     */
    public $stockmax;

    /**
     * Minimum stock.
     *
     * @var float|int
     */
    public $stockmin;

    /**
     * @var string
     */
    public $ubicacion;

    public function clear()
    {
        parent::clear();
        $this->cantidad = 0.0;
        $this->codalmacen = Tools::settings('default', 'codalmacen');
        $this->disponible = 0.0;
        $this->pterecibir = 0.0;
        $this->reservada = 0.0;
        $this->stockmax = 0.0;
        $this->stockmin = 0.0;
    }

    public function delete(): bool
    {
        if (parent::delete()) {
            $this->cantidad = 0.0;
            $this->updateProductStock();
            return true;
        }

        return false;
    }

    public function install(): string
    {
        // needed dependencies
        new DinAlmacen();
        new DinProducto();
        new DinVariante();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idstock';
    }

    public function save(): bool
    {
        if (parent::save()) {
            $this->updateProductStock();
            return true;
        }

        return false;
    }

    public static function tableName(): string
    {
        return 'stocks';
    }

    /**
     * Transfer $qty unities of stock to $toWarehouse
     *
     * @param string $toWarehouse destination warehouse
     * @param float $qty quantity to move
     *
     * @return bool
     */
    public function transferTo(string $toWarehouse, float $qty): bool
    {
        $destination = new static();
        $where = [
            new DataBaseWhere('codalmacen', $toWarehouse),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if ($destination->loadFromCode('', $where)) {
            $destination->cantidad += $qty;
        } else {
            $destination->codalmacen = $toWarehouse;
            $destination->idproducto = $this->idproducto;
            $destination->referencia = $this->referencia;
            $destination->cantidad = $qty;
        }

        $this->cantidad -= $qty;
        return $destination->save() && $this->save();
    }

    public function test(): bool
    {
        $this->ubicacion = Tools::noHtml($this->ubicacion);

        $this->cantidad = round($this->cantidad, self::MAX_DECIMALS);
        $this->reservada = round($this->reservada, self::MAX_DECIMALS);
        $this->pterecibir = round($this->pterecibir, self::MAX_DECIMALS);
        $this->disponible = max([0, $this->cantidad - $this->reservada]);

        $this->referencia = Tools::noHtml($this->referencia);
        if (empty($this->idproducto)) {
            $variante = new DinVariante();
            $whereRef = [new DataBaseWhere('referencia', $this->referencia)];
            if ($variante->loadFromCode('', $whereRef)) {
                $this->idproducto = $variante->idproducto;
            }
        }

        return parent::test();
    }

    /**
     * Returns the total stock of the product.
     *
     * @param int $idproducto
     * @param string $referencia
     *
     * @return float
     */
    public function totalFromProduct(int $idproducto, string $referencia = ''): float
    {
        $sql = 'SELECT SUM(cantidad) AS total FROM ' . static::tableName()
            . ' WHERE idproducto = ' . self::$dataBase->var2str($idproducto);

        if (!empty($referencia)) {
            $sql .= ' AND referencia = ' . self::$dataBase->var2str($referencia);
        }

        $data = self::$dataBase->select($sql);
        return empty($data) ? 0.0 : round((float)$data[0]['total'], self::MAX_DECIMALS);
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return $this->getProducto()->url($type);
    }

    protected function updateProductStock(): bool
    {
        $total = $this->totalFromProduct($this->idproducto);
        $sql = "UPDATE " . DinProducto::tableName() . " SET stockfis = " . self::$dataBase->var2str($total)
            . ", actualizado = " . self::$dataBase->var2str(Tools::dateTime())
            . " WHERE idproducto = " . self::$dataBase->var2str($this->idproducto) . ';';

        $totalVariant = $this->totalFromProduct($this->idproducto, $this->referencia);
        $sql .= "UPDATE " . DinVariante::tableName() . " SET stockfis = " . self::$dataBase->var2str($totalVariant)
            . " WHERE referencia = " . self::$dataBase->var2str($this->referencia) . ';';

        return self::$dataBase->exec($sql);
    }
}
