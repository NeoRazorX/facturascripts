<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\FormaPago;

/**
 * Description of Receipt
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class Receipt extends ModelOnChangeClass
{
    use CompanyRelationTrait;
    use PaymentRelationTrait;

    /** @var string */
    public $coddivisa;

    /** @var string */
    public $codigofactura;

    /** @var bool */
    protected $disableInvoiceUpdate = false;

    /** @var bool */
    protected $disablePaymentGeneration = false;

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

    abstract public function getInvoice();

    abstract public function getPayments(): array;

    abstract protected function newPayment(): bool;

    public function clear()
    {
        parent::clear();
        $this->coddivisa = Tools::settings('default', 'coddivisa');
        $this->codpago = Tools::settings('default', 'codpago');
        $this->fecha = Tools::date();
        $this->idempresa = Tools::settings('default', 'idempresa');
        $this->importe = 0.0;
        $this->liquidado = 0.0;
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

    public function disableInvoiceUpdate(bool $disable = true)
    {
        $this->disableInvoiceUpdate = $disable;
    }

    public function disablePaymentGeneration(bool $disable = true)
    {
        $this->disablePaymentGeneration = $disable;
    }

    public function getCode(): string
    {
        return $this->getInvoice()->codigo . '-' . $this->numero;
    }

    public static function primaryColumn(): string
    {
        return 'idrecibo';
    }

    public function setExpiration(string $date)
    {
        $this->vencimiento = $date;
    }

    public function setPaymentMethod(string $codpago)
    {
        $formaPago = new FormaPago();
        if ($formaPago->loadFromCode($codpago)) {
            $this->codpago = $codpago;
            $this->pagado = $formaPago->pagado;
            $this->setExpiration($formaPago->getExpiration($this->fecha));
            if ($formaPago->pagado && empty($this->fechapago)) {
                $this->fechapago = $this->fecha;
            }
        }
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
        if (strtotime($this->vencimiento) < strtotime($this->fecha)) {
            $this->vencimiento = $this->fecha;
        }

        // comprobamos el vencimiento
        $this->vencido = !$this->pagado && strtotime($this->vencimiento) < time();

        return parent::test();
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        if (false === parent::onChange($field)) {
            return false;
        }

        switch ($field) {
            case 'importe':
                return !$this->previousData['pagado'];

            case 'pagado':
                $this->newPayment();
                return true;

            default:
                return true;
        }
    }

    protected function onDelete()
    {
        $this->updateInvoice();
        parent::onDelete();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (false === parent::saveInsert($values)) {
            return false;
        }

        if ($this->pagado) {
            $this->newPayment();
            $this->updateInvoice();
        }

        return true;
    }

    protected function saveUpdate(array $values = []): bool
    {
        if (parent::saveUpdate($values)) {
            $this->updateInvoice();
            return true;
        }

        return false;
    }

    protected function setPreviousData(array $fields = [])
    {
        parent::setPreviousData(array_merge(['importe', 'pagado'], $fields));
    }

    protected function updateInvoice()
    {
        if ($this->disableInvoiceUpdate) {
            return;
        }

        $invoice = $this->getInvoice();
        $generator = new ReceiptGenerator();
        $generator->update($invoice);
    }
}
