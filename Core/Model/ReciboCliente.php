<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\CompanyRelationTrait;
use FacturaScripts\Core\Model\Base\PaymentRelationTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\CustomerRiskTools;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\Cliente as DinCliente;
use FacturaScripts\Dinamic\Model\FacturaCliente as DinFacturaCliente;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\PagoCliente as DinPagoCliente;

/**
 * Description of ReciboCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ReciboCliente extends ModelClass
{
    use ModelTrait;
    use CompanyRelationTrait;
    use PaymentRelationTrait;

    /** Código del cliente asociado al recibo. @var string */
    public $codcliente;

    /** Código de la divisa del recibo. @var string */
    public $coddivisa;

    /** Código visible de la factura asociada. @var string */
    public $codigofactura;

    /** Indica si se debe omitir la actualización automática de la factura. @var bool */
    protected $disable_invoice_update = false;

    /** Indica si se debe omitir la generación automática del pago. @var bool */
    protected $disable_payment_generation = false;

    /** Fecha de emisión del recibo. @var string */
    public $fecha;

    /** Fecha en la que se completó el pago del recibo. @var string */
    public $fechapago;

    /** Gastos bancarios asociados al recibo. @var float */
    public $gastos;

    /** Identificador de la factura de cliente asociada. @var int */
    public $idfactura;

    /** Identificador único del recibo de cliente. @var int */
    public $idrecibo;

    /** Importe total del recibo. @var float */
    public $importe;

    /** Importe del recibo que ya ha sido pagado. @var float */
    public $liquidado;

    /** Nombre del usuario que creó el recibo. @var string */
    public $nick;

    /** Número de vencimiento del recibo dentro de la factura. @var int */
    public $numero;

    /** Observaciones internas sobre el recibo. @var string */
    public $observaciones;

    /** Indica si el recibo está completamente pagado. @var bool */
    public $pagado;

    /** Indica si el recibo está vencido y pendiente de pago. @var bool */
    public $vencido;

    /** Fecha de vencimiento del recibo. @var string */
    public $vencimiento;

    public function clear(): void
    {
        parent::clear();
        $this->coddivisa = Tools::settings('default', 'coddivisa');
        $this->codpago = Tools::settings('default', 'codpago');
        $this->fecha = Tools::date();
        $this->idempresa = Tools::settings('default', 'idempresa');
        $this->gastos = 0.0;
        $this->importe = 0.0;
        $this->liquidado = 0.0;
        $this->nick = Session::user()->nick;
        $this->numero = 1;
        $this->pagado = false;
        $this->vencido = false;
    }

    public function delete(): bool
    {
        foreach ($this->getPayments() as $pay) {
            if (false === $pay->delete()) {
                Tools::log()->warning('cant-remove-payment');
                return false;
            }
        }

        return parent::delete();
    }

    public function disableInvoiceUpdate(bool $disable = true): void
    {
        $this->disable_invoice_update = $disable;
    }

    public function disablePaymentGeneration(bool $disable = true): void
    {
        $this->disable_payment_generation = $disable;
    }

    public function getCode(): string
    {
        return $this->getInvoice()->codigo . '-' . $this->numero;
    }

    public function getInvoice(): DinFacturaCliente
    {
        $invoice = new DinFacturaCliente();
        $invoice->load($this->idfactura);
        return $invoice;
    }

    /**
     * Returns all payment history for this receipt
     *
     * @return DinPagoCliente[]
     */
    public function getPayments(): array
    {
        $orderBy = ['fecha' => 'DESC', 'hora' => 'DESC', 'idpago' => 'DESC'];
        return DinPagoCliente::allWhereEq('idrecibo', $this->idrecibo, $orderBy);
    }

    public function getSubject(): DinCliente
    {
        $cliente = new DinCliente();
        $cliente->load($this->codcliente);
        return $cliente;
    }

    public function install(): string
    {
        // needed dependencies
        new FacturaCliente();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idrecibo';
    }

    public function setExpiration(string $date): void
    {
        $this->vencimiento = $date;

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
        $this->vencimiento = date(Tools::DATE_STYLE, min($newDates));
    }

    public function setPaymentMethod(string $codpago): void
    {
        $formaPago = new FormaPago();
        if ($formaPago->load($codpago)) {
            $this->codpago = $codpago;
            $this->pagado = $formaPago->pagado;
            $this->setExpiration($formaPago->getExpiration($this->fecha));
            if ($formaPago->pagado && empty($this->fechapago)) {
                $this->fechapago = $this->fecha;
            }
        }
    }

    public static function tableName(): string
    {
        return 'recibospagoscli';
    }

    public function test(): bool
    {
        $this->observaciones = Tools::noHtml($this->observaciones);

        // asignamos el código de la factura
        if (empty($this->codigofactura)) {
            $this->codigofactura = $this->getInvoice()->codigo;
        }

        // comprobamos la fecha de pago
        if ($this->pagado === false) {
            $this->fechapago = null;
        } elseif (empty($this->fechapago)) {
            $this->fechapago = Tools::date();
        }

        // comprobamos la fecha de vencimiento
        if (empty($this->vencimiento) || strtotime($this->vencimiento) < strtotime($this->fecha)) {
            $this->vencimiento = $this->fecha;
        }

        // comprobamos el vencimiento
        $this->vencido = !$this->pagado && strtotime($this->vencimiento) < time();

        return parent::test();
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
        if ($this->disable_payment_generation) {
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

    protected function onChange(string $field): bool
    {
        if (false === parent::onChange($field)) {
            return false;
        }

        switch ($field) {
            case 'importe':
                return !$this->getOriginal('pagado');

            case 'pagado':
                $this->newPayment();
                return true;

            default:
                return true;
        }
    }

    protected function onDelete(): void
    {
        $this->updateInvoice();
        $this->updateCustomerRisk();

        parent::onDelete();
    }

    protected function onInsert(): void
    {
        $this->updateCustomerRisk();

        parent::onInsert();
    }

    protected function onUpdate(): void
    {
        $this->updateInvoice();
        $this->updateCustomerRisk();

        parent::onUpdate();
    }

    protected function saveInsert(): bool
    {
        if (false === parent::saveInsert()) {
            return false;
        }

        if ($this->pagado) {
            $this->newPayment();
            $this->updateInvoice();
        }

        return true;
    }

    protected function updateCustomerRisk(): void
    {
        $customer = new DinCliente();
        if ($customer->load($this->codcliente)) {
            $customer->riesgoalcanzado = CustomerRiskTools::getCurrent($customer->id());
            $customer->save();
        }
    }

    protected function updateInvoice(): void
    {
        if ($this->disable_invoice_update) {
            return;
        }

        $invoice = $this->getInvoice();
        $generator = new ReceiptGenerator();
        $generator->update($invoice);
    }
}
