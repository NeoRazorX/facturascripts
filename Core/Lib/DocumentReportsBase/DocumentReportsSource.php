<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\DocumentReportsBase;

use FacturaScripts\Core\Model;

/**
 * Description of DocumentReportsSource
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DocumentReportsSource
{

    /**
     * Document Model selected.
     *
     * @var string
     */
    public $source;

    /**
     * RGB colors values
     *
     * @var string
     */
    public $color;

    /**
     * Start date.
     *
     * @var \DateTime
     */
    public $dateFrom;

    /**
     * End date.
     *
     * @var \DateTime
     */
    public $dateTo;

    /**
     * Create and initialize object
     *
     * @param string $source
     * @param string $color
     */
    public function __construct($source, $color)
    {
        $this->source = $source;
        $this->color = $color;
        $this->dateFrom = new \DateTime(date('01-01-Y'));
        $this->dateTo = new \DateTime(date('t-m-Y'));
    }

    /**
     * Return the table name for source name.
     *
     * @return string
     */
    public function getTableName()
    {
        switch ($this->source) {
            case 'customer-estimations':
                return Model\PresupuestoCliente::tableName();

            case 'customer-orders':
                return Model\PedidoCliente::tableName();

            case 'customer-delivery-notes':
                return Model\AlbaranCliente::tableName();

            case 'customer-invoices':
                return Model\FacturaCliente::tableName();
                
            case 'supplier-estimations':
                return Model\PresupuestoProveedor::tableName();

            case 'supplier-orders':
                return Model\PedidoProveedor::tableName();

            case 'supplier-delivery-notes':
                return Model\AlbaranProveedor::tableName();

            case 'supplier-invoices':
                return Model\FacturaProveedor::tableName();

            default:
                return '';
        }
    }
}
