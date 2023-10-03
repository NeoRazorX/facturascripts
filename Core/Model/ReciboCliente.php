<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\CustomerRiskTools;
use FacturaScripts\Dinamic\Model\Cliente as DinCliente;
use FacturaScripts\Dinamic\Model\FacturaCliente as DinFacturaCliente;
use FacturaScripts\Dinamic\Model\PagoCliente as DinPagoCliente;

/**
 * Description of ReciboCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ReciboCliente extends Base\Receipt
{
    use Base\ModelTrait;

    /** @var string */
    public $codcliente;

    /** @var float */
    public $gastos;

    public function clear()
    {
        parent::clear();
        $this->gastos = 0.0;
    }

    public function getInvoice(): DinFacturaCliente
    {
        $invoice = new DinFacturaCliente();
        $invoice->loadFromCode($this->idfactura);
        return $invoice;
    }

    /**
     * Returns all payment history for this receipt
     *
     * @return DinPagoCliente[]
     */
    public function getPayments(): array
    {
        $payModel = new DinPagoCliente();
        $where = [new DataBaseWhere('idrecibo', $this->idrecibo)];
        $orderBy = ['fecha' => 'DESC', 'hora' => 'DESC', 'idpago' => 'DESC'];
        return $payModel->all($where, $orderBy, 0, 0);
    }

    public function getSubject(): DinCliente
    {
        $cliente = new DinCliente();
        $cliente->loadFromCode($this->codcliente);
        return $cliente;
    }

    public function install(): string
    {
        // needed dependencies
        new Cliente();

        return parent::install();
    }

    public function setExpiration(string $date)
    {
        parent::setExpiration($date);

        // obtenemos los días de pago del cliente
        $days = $this->getSubject()->getPaymentDays();
        if (empty($days)) {
            // si no tiene ninguno, dejamos la fecha como está
            return;
        }

        // si el cliente tiene días de pago, calculamos fechas con los días de pago
        $newDates = [];
        $maxDay = date('t', strtotime($this->vencimiento));
        foreach ($days as $numDay) {
            $day = min([$numDay, $maxDay]);
            for ($num = 0; $num < 30; $num++) {
                $newDay = date('d', strtotime($this->vencimiento . ' +' . $num . ' days'));
                if ($newDay == $day) {
                    $newDates[] = strtotime($this->vencimiento . ' +' . $num . ' days');
                }
            }
        }
        if (empty($newDates)) {
            return;
        }

        // asignamos la fecha más próxima a la fecha de vencimiento
        $this->vencimiento = date(self::DATE_STYLE, min($newDates));
    }

    public static function tableName(): string
    {
        return 'recibospagoscli';
    }

    public function url(string $type = 'auto', string $list = 'ListFacturaCliente?activetab=List'): string
    {
        if ('list' === $type && !empty($this->idfactura)) {
            return $this->getInvoice()->url() . '&activetab=List' . $this->modelClassName();
        } elseif ('pay' === $type) {
            return '';
        }

        return parent::url($type, $list);
    }

    /**
     * Creates a new payment for this receipt.
     *
     * @return bool
     */
    protected function newPayment(): bool
    {
        if ($this->disablePaymentGeneration) {
            return false;
        }

        $pago = new DinPagoCliente();
        $pago->codpago = $this->codpago;
        $pago->fecha = $this->fechapago ?? $pago->fecha;
        $pago->gastos = $this->gastos;
        $pago->idrecibo = $this->idrecibo;
        $pago->importe = $this->pagado ? $this->importe : 0 - $this->importe;
        $pago->nick = $this->nick;
        return $pago->save();
    }

    protected function onDelete()
    {
        $this->updateCustomerRisk();
        parent::onDelete();
    }

    protected function onInsert()
    {
        $this->updateCustomerRisk();
        parent::onInsert();
    }

    protected function onUpdate()
    {
        $this->updateCustomerRisk();
        parent::onUpdate();
    }

    /**
     * Update customer risk when a receipt is created, updated or deleted.
     *
     * @return void
     */
    protected function updateCustomerRisk()
    {
        $customer = new DinCliente();
        if ($customer->loadFromCode($this->codcliente)) {
            $customer->riesgoalcanzado = CustomerRiskTools::getCurrent($customer->primaryColumnValue());
            $customer->save();
        }
    }
}
