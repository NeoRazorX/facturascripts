<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of ReciboCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ReciboCliente extends Base\Receipt
{

    use Base\ModelTrait;

    /**
     *
     * @var string
     */
    public $codcliente;

    /**
     *
     * @var float
     */
    public $gastos;

    public function clear()
    {
        parent::clear();
        $this->gastos = 0.0;
    }

    /**
     * 
     * @return FacturaCliente
     */
    public function getInvoice()
    {
        $invoice = new FacturaCliente();
        $invoice->loadFromCode($this->idfactura);
        return $invoice;
    }

    /**
     * Returns all payment history for this receipt
     * 
     * @return PagoCliente[]
     */
    public function getPayments()
    {
        $payModel = new PagoCliente();
        $where = [new DataBaseWhere('idrecibo', $this->idrecibo)];
        return $payModel->all($where, [], 0, 0);
    }

    /**
     * 
     * @return Cliente
     */
    public function getSubject()
    {
        $cliente = new Cliente();
        $cliente->loadFromCode($this->codcliente);
        return $cliente;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new Cliente();

        return parent::install();
    }

    /**
     * 
     * @param string $date
     */
    public function setExpiration($date)
    {
        parent::setExpiration($date);

        $days = $this->getSubject()->getPaymentDays();
        if (empty($days)) {
            return;
        }

        /// try to select consumer defined days for expiration date
        $newDates = [];
        $maxDay = \date('t', \strtotime($this->vencimiento));
        foreach ($days as $numDay) {
            $day = min([$numDay, $maxDay]);
            for ($num = 0; $num < 30; $num++) {
                $newDay = \date('d', \strtotime($this->vencimiento . ' +' . $num . ' days'));
                if ($newDay == $day) {
                    $newDates[] = \strtotime($this->vencimiento . ' +' . $num . ' days');
                }
            }
        }

        $this->vencimiento = \date(self::DATE_STYLE, min($newDates));
    }

    /**
     * 
     * @return string
     */
    public static function tableName()
    {
        return 'recibospagoscli';
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListFacturaCliente?activetab=List')
    {
        if ('list' === $type && !empty($this->idfactura)) {
            return $this->getInvoice()->url() . '&activetab=List' . $this->modelClassName();
        } elseif ('pay' === $type) {
            return '';
        }

        return parent::url($type, $list);
    }

    /**
     * Creates a new payment fro this receipt.
     * 
     * @return bool
     */
    protected function newPayment()
    {
        if ($this->disablePaymentGeneration) {
            return false;
        }

        $pago = new PagoCliente();
        $pago->codpago = $this->codpago;
        $pago->fecha = $this->fechapago ?? $pago->fecha;
        $pago->gastos = $this->gastos;
        $pago->idrecibo = $this->idrecibo;
        $pago->importe = $this->pagado ? $this->importe : 0 - $this->importe;
        $pago->nick = $this->nick;
        return $pago->save();
    }
}
