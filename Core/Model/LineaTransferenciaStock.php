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
use FacturaScripts\Dinamic\Model\Stock;

/**
 * Transfers stock lines.
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 * @author Rafael San José Tovar <rafael.sanjose@x-netdigital.com>
 */
class LineaTransferenciaStock extends Base\ModelClass
{

    use Base\ModelTrait;

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
     * Foreign key with variantes table
     *
     * @var int
     */
    public $idvariante;

    /**
     * Foreign key with head of this transfer line.
     *
     * @var int
     */
    public $idtrans;

    /**
     * Quantity of product transfered
     *
     * @var int
     */
    public $cantidad;

    /**
     * 
     * @return string
     */
    public function install()
    {
        new TransferenciaStock();
        new Variante();
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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineastransferenciasstock';
    }

    /**
     * Updates stock according to line data and $codalmacen warehouse.
     *
     * @param string $codalmacen
     *
     * @return boolean
     */
    public function updateStock(string $codalmacenori, $codalmacendes, $product, $variant, $qty)
    {
        if ($qty == 0) {
            return true;
        }

        $variante = new Variante();
        if (!$variante->loadFromCode('', [new DataBaseWhere('idproducto', $product), new DataBaseWhere('idvariante', $variant)])) {
            $this->miniLog->error($this->i18n->trans('product-not-found'));
            return false;
        }
        $referencia = $variante->referencia;

        $origen = new Stock();
        $destino = new Stock();

        $origenExists = $origen->loadFromCode('', [new DataBaseWhere('codalmacen', $codalmacenori), new DataBaseWhere('referencia', $referencia)]);
        $destinoExists = $destino->loadFromCode('', [new DataBaseWhere('codalmacen', $codalmacendes), new DataBaseWhere('referencia', $referencia)]);

        if ($origenExists) {
            $origen->cantidad -= $qty;
            $origen->disponible -= $qty;
        } else {
            $origen->codalmacen = $codalmacenori;
            $origen->idproducto = $product;
            $origen->referencia = $referencia;
            $origen->cantidad = -$qty;
            $origen->disponible = -$qty;
        }

        if ($destinoExists) {
            $destino->cantidad += $qty;
            $destino->disponible += $qty;
        } else {
            $destino->codalmacen = $codalmacendes;
            $destino->idproducto = $product;
            $destino->referencia = $referencia;
            $destino->cantidad = +$qty;
            $destino->disponible = +$qty;
        }

        $origen->save();
        $destino->save();

        return true;
    }
}
