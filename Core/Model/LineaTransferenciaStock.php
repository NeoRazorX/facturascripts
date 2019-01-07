<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2018  Carlos Garcia Gomez       <carlos@facturascripts.com>
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   <shawe.ewahs@gmail.com>
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
 * Transfers stock lines.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos Garcia Gomez          <carlos@facturascripts.com>
 */
class LineaTransferenciaStock extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Quantity of product transfered
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
     * Primary key of line transfer stock. Autoincremental
     *
     * @var int
     */
    public $idlinea;

    /**
     * Foreign key with Productos table.
     *
     * @var int
     */
    public $idproducto;

    /**
     * Foreign key with head of this transfer line.
     *
     * @var int
     */
    public $idtrans;

    /**
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
        $this->cantidadAnt = isset($this->cantidad) ? $this->cantidad : 0;
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->cantidad = 0.0;
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        if (parent::delete()) {
            $this->cantidad = 0.0;
            $this->updateStock();
            return true;
        }

        return false;
    }

    /**
     * 
     * @return TransferenciaStock
     */
    public function getTransference()
    {
        $transf = new TransferenciaStock();
        $transf->loadFromCode($this->idtrans);
        return $transf;
    }

    /**
     * 
     * @return Variante
     */
    public function getVariant()
    {
        $variant = new Variante();
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        $variant->loadFromCode('', $where);
        return $variant;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new TransferenciaStock();
        new Variante();

        return parent::install();
    }

    /**
     * 
     * @param string $cod
     * @param array  $where
     * @param array  $orderby
     * 
     * @return bool
     */
    public function loadFromCode($cod, array $where = [], array $orderby = [])
    {
        if (parent::loadFromCode($cod, $where, $orderby)) {
            $this->cantidadAnt = $this->cantidad;
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
     * 
     * @return bool
     */
    public function save()
    {
        if (parent::save()) {
            $this->updateStock();
            return true;
        }

        return false;
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        if (is_null($this->idproducto)) {
            $variant = $this->getVariant();
            $this->idproducto = $variant->idproducto;
        }

        return parent::test();
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineastransferenciasstock';
    }

    protected function updateStock()
    {
        $transfer = $this->getTransference();
        $variant = $this->getVariant();

        $stock1 = new Stock();
        $where1 = [
            new DataBaseWhere('referencia', $this->referencia),
            new DataBaseWhere('codalmacen', $transfer->codalmacenorigen),
        ];

        if (!$stock1->loadFromCode('', $where1)) {
            $stock1->codalmacen = $transfer->codalmacenorigen;
            $stock1->idproducto = $variant->idproducto;
            $stock1->referencia = $this->referencia;
            $stock1->save();
        }

        $quantity = $this->cantidad - $this->cantidadAnt;
        $stock1->transferTo($transfer->codalmacendestino, $quantity);
    }
}
