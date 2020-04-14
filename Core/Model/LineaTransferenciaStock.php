<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\TransferenciaStock as DinTransferenciaStock;
use FacturaScripts\Dinamic\Model\Variante as DinVariante;

/**
 * Transfers stock lines.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos Garcia Gomez          <carlos@facturascripts.com>
 */
class LineaTransferenciaStock extends Base\ModelOnChangeClass
{

    use Base\ModelTrait;
    use Base\ProductRelationTrait;

    /**
     * Quantity of product transfered
     *
     * @var float|int
     */
    public $cantidad;

    /**
     * Primary key of line transfer stock. Autoincremental
     *
     * @var int
     */
    public $idlinea;

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
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->cantidad = 0.0;
    }

    /**
     * 
     * @return DinTransferenciaStock
     */
    public function getTransference()
    {
        $transf = new DinTransferenciaStock();
        $transf->loadFromCode($this->idtrans);
        return $transf;
    }

    /**
     * 
     * @return DinVariante
     */
    public function getVariant()
    {
        $variant = new DinVariante();
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
        new DinTransferenciaStock();
        new DinVariante();

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
     * 
     * @return bool
     */
    public function test()
    {
        $this->referencia = $this->toolBox()->utils()->noHtml($this->referencia);
        if (empty($this->idproducto)) {
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

    /**
     * This methos is called before save (update) when some field value has changes.
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'cantidad':
                $this->updateStock();
                return true;
        }

        return parent::onChange($field);
    }

    /**
     * This method is called after remove this data from the database.
     */
    protected function onDelete()
    {
        $this->cantidad = 0.0;
        $this->updateStock();
    }

    /**
     * This method is called after insert this record in the database.
     */
    protected function onInsert()
    {
        $this->updateStock();
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['cantidad'];
        parent::setPreviousData(\array_merge($more, $fields));
    }

    protected function updateStock()
    {
        $transfer = $this->getTransference();
        $stock = new Stock();
        $where = [
            new DataBaseWhere('codalmacen', $transfer->codalmacenorigen),
            new DataBaseWhere('referencia', $this->referencia)
        ];
        if (false === $stock->loadFromCode('', $where)) {
            $stock->codalmacen = $transfer->codalmacenorigen;
            $stock->idproducto = $this->getVariant()->idproducto;
            $stock->referencia = $this->referencia;
            $stock->save();
        }

        $quantity = $this->cantidad - $this->previousData['cantidad'];
        $stock->transferTo($transfer->codalmacendestino, $quantity);
    }
}
