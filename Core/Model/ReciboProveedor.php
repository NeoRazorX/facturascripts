<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\CompanyRelationTrait;
use FacturaScripts\Core\Model\Base\PaymentRelationTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\FacturaProveedor as DinFacturaProveedor;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\PagoProveedor as DinPagoProveedor;
use FacturaScripts\Dinamic\Model\Proveedor as DinProveedor;

/**
 * Description of ReciboProveedor
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ReciboProveedor extends ModelClass
{
    use ModelTrait;
    use CompanyRelationTrait;
    use PaymentRelationTrait;

    /** @var string */
    public $coddivisa;

    /** @var string */
    public $codigofactura;

    /** @var string */
    public $codproveedor;

    /** @var bool */
    protected $disable_invoice_update = false;

    /** @var bool */
    protected $disable_payment_generation = false;

    /** @var string */
    public $fecha;

    /** @var string */
    public $fechapago;

    /** @var int */
    public $idfactura;

    /** @var int */
    public $idrecibo;

    /** @var float */
    public $importe;

    /** @var float */
    public $liquidado;

    /** @var string */
    public $nick;

    /** @var int */
    public $numero;

    /** @var string */
    public $observaciones;

    /** @var bool */
    public $pagado;

    /** @var bool */
    public $vencido;

    /** @var string */
    public $vencimiento;

    public function clear(): void
    {
        parent::clear();
        $this->coddivisa = Tools::settings('default', 'coddivisa');
        $this->codpago = Tools::settings('default', 'codpago');
        $this->fecha = Tools::date();
        $this->idempresa = Tools::settings('default', 'idempresa');
        $this->importe = 0.0;
        $this->liquidado = 0.0;
        $this->numero = 1;
        $this->nick = Session::user()->nick;
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

    public function getInvoice(): DinFacturaProveedor
    {
        $invoice = new DinFacturaProveedor();
        $invoice->load($this->idfactura);
        return $invoice;
    }

    /**
     * Returns all payment history for this receipt
     *
     * @return DinPagoProveedor[]
     */
    public function getPayments(): array
    {
        $where = [new DataBaseWhere('idrecibo', $this->idrecibo)];
        $orderBy = ['fecha' => 'DESC', 'hora' => 'DESC', 'idpago' => 'DESC'];
        return DinPagoProveedor::all($where, $orderBy, 0, 0);
    }

    public function getSubject(): DinProveedor
    {
        $proveedor = new DinProveedor();
        $proveedor->load($this->codproveedor);
        return $proveedor;
    }

    public function install(): string
    {
        // needed dependencies
        new FacturaProveedor();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idrecibo';
    }

    public function setExpiration(string $date): void
    {
        $this->vencimiento = $date;
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
        return 'recibospagosprov';
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

    public function url(string $type = 'auto', string $list = 'ListFacturaProveedor?activetab=List'): string
    {
        if ('list' === $type && !empty($this->idfactura)) {
            return $this->getInvoice()->url() . '&activetab=List' . $this->modelClassName();
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

        $pago = new DinPagoProveedor();
        $pago->codpago = $this->codpago;
        $pago->fecha = $this->fechapago ?? $pago->fecha;
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

        parent::onDelete();
    }

    protected function onUpdate(): void
    {
        $this->updateInvoice();

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
