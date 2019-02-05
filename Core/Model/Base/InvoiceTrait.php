<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\FormaPago;

/**
 * Description of InvoiceTrait
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait InvoiceTrait
{

    /**
     * Code of the invoice that rectifies.
     *
     * @var string
     */
    public $codigorect;

    /**
     * Payment method associated.
     *
     * @var string
     */
    public $codpago;

    /**
     * Date of the document.
     *
     * @var string
     */
    public $fecha;

    /**
     * Related accounting entry ID, if any.
     *
     * @var int
     */
    public $idasiento;

    /**
     * ID of the related payment accounting entry, if any.
     *
     * @var int
     */
    public $idasientop;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idfactura;

    /**
     * ID of the invoice that you rectify.
     *
     * @var int
     */
    public $idfacturarect;

    /**
     * Due date of the invoice.
     *
     * @var string
     */
    public $vencimiento;

    abstract public function all(array $where = [], array $order = [], int $offset = 0, int $limit = 50);

    abstract public function getLines();

    /**
     * 
     * @return Asiento
     */
    public function getAccountingEntry()
    {
        $asiento = new Asiento();
        $asiento->loadFromCode($this->idasiento);
        return $asiento;
    }

    /**
     * 
     * @return self[]
     */
    public function getRefunds()
    {
        if (empty($this->idfactura)) {
            return [];
        }

        $where = [new DataBaseWhere('idfacturarect', $this->idfactura)];
        return $this->all($where, ['idfactura' => 'DESC'], 0, 0);
    }

    /**
     * Returns the refunded items amount associated with the invoice.
     *
     * @return float|int
     */
    public function refundedItemAmount($ref)
    {
        $amount = 0;
        foreach ($this->getRefunds() as $invoice) {
            foreach ($invoice->getLines() as $line) {
                if ($line->referencia == $ref) {
                    $amount += abs($line->cantidad);
                }
            }
        }

        return $amount;
    }

    /**
     * 
     * @param string $codpago
     */
    public function setPaymentMethod($codpago)
    {
        $this->vencimiento = $this->fecha;

        $formaPago = new FormaPago();
        if ($formaPago->loadFromCode($codpago)) {
            $this->codpago = $codpago;

            $string = '+' . $formaPago->plazovencimiento . ' ' . $formaPago->tipovencimiento;
            $this->vencimiento = date('d-m-Y', strtotime($this->fecha . ' ' . $string));
        }
    }
}
