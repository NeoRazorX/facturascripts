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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Model\Base\InvoiceLineTrait;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Model\Base\PurchaseDocumentLine;

/**
 * Line of a supplier invoice.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaFacturaProveedor extends PurchaseDocumentLine
{

    use ModelTrait;
    use InvoiceLineTrait;

    /**
     * Invoice ID of this line.
     *
     * @var int
     */
    public $idfactura;

    /**
     * @var int
     */
    public $idlinearect;

    /**
     * @return string
     */
    public function documentColumn()
    {
        return 'idfactura';
    }

    /**
     * @return FacturaProveedor
     */
    public function getDocument()
    {
        $factura = new FacturaProveedor();
        $factura->loadFromCode($this->idfactura);
        return $factura;
    }

    /**
     * @return string
     */
    public function install()
    {
        // needed dependency
        new FacturaProveedor();
        return parent::install();
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineasfacturasprov';
    }

    /**
     * @return bool
     */
    public function test()
    {
        // servido will always be 0 to prevent stock problems when removing rectified invoices
        $this->servido = 0.0;
        return parent::test();
    }

    /**
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        return $this->idfactura ? 'EditFacturaProveedor?code=' . $this->idfactura : parent::url($type, $list);
    }
}
