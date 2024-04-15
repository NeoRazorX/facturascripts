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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Model\Base\InvoiceLineTrait;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Model\Base\PurchaseDocumentLine;
use FacturaScripts\Dinamic\Model\FacturaProveedor as DinFacturaProveedor;

/**
 * Line of a supplier invoice.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaFacturaProveedor extends PurchaseDocumentLine
{
    use ModelTrait;
    use InvoiceLineTrait;

    /** @var bool */
    private $has_refunded_quantity = null;

    /** @var int */
    public $idfactura;

    /** @var int */
    public $idlinearect;

    public function documentColumn(): string
    {
        return 'idfactura';
    }

    public function getDocument(): DinFacturaProveedor
    {
        $factura = new DinFacturaProveedor();
        $factura->loadFromCode($this->idfactura);
        return $factura;
    }

    public function hasRefundedQuantity(): bool
    {
        // comprobamos si existe alguna factura rectificativa
        if ($this->has_refunded_quantity === null) {
            $refunds = $this->getDocument()->getRefunds();
            $this->has_refunded_quantity = !empty($refunds);
        }

        return $this->has_refunded_quantity;
    }

    public function install(): string
    {
        // needed dependency
        new FacturaProveedor();
        return parent::install();
    }

    public static function tableName(): string
    {
        return 'lineasfacturasprov';
    }

    public function test(): bool
    {
        // servido will always be 0 to prevent stock problems when removing rectified invoices
        $this->servido = 0.0;
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return $this->idfactura ? 'EditFacturaProveedor?code=' . $this->idfactura : parent::url($type, $list);
    }
}
