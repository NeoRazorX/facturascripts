<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\FormaPago;

/**
 * Description of Receipt
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class Receipt extends ModelOnChangeClass
{

    /**
     *
     * @var string
     */
    public $coddivisa;

    /**
     *
     * @var string
     */
    public $codpago;

    /**
     *
     * @var bool
     */
    protected $disablePaymentGeneration = false;

    /**
     *
     * @var string
     */
    public $fecha;

    /**
     *
     * @var string
     */
    public $fechapago;

    /**
     *
     * @var int
     */
    public $idempresa;

    /**
     *
     * @var int
     */
    public $idfactura;

    /**
     *
     * @var int
     */
    public $idrecibo;

    /**
     *
     * @var float
     */
    public $importe;

    /**
     *
     * @var float
     */
    public $liquidado;

    /**
     *
     * @var string
     */
    public $nick;

    /**
     *
     * @var int
     */
    public $numero;

    /**
     *
     * @var string
     */
    public $observaciones;

    /**
     *
     * @var bool
     */
    public $pagado;

    /**
     *
     * @var string
     */
    public $vencimiento;

    abstract public function getInvoice();

    abstract protected function newPayment();

    public function clear()
    {
        parent::clear();

        $appSettings = $this->toolBox()->appSettings();
        $this->coddivisa = $appSettings->get('default', 'coddivisa');
        $this->codpago = $appSettings->get('default', 'codpago');
        $this->fecha = date(self::DATE_STYLE);
        $this->idempresa = $appSettings->get('default', 'idempresa');
        $this->importe = 0.0;
        $this->liquidado = 0.0;
        $this->numero = 1;
        $this->pagado = false;
    }

    /**
     * 
     * @param bool $disable
     */
    public function disablePaymentGeneration($disable = true)
    {
        $this->disablePaymentGeneration = $disable;
    }

    /**
     * 
     * @return string
     */
    public function getCode()
    {
        return $this->getInvoice()->codigo . '-' . $this->numero;
    }

    /**
     * 
     * @return FormaPago
     */
    public function getPaymentMethod()
    {
        $paymentMethod = new FormaPago();
        $paymentMethod->loadFromCode($this->codpago);
        return $paymentMethod;
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idrecibo';
    }

    /**
     * 
     * @param string $date
     */
    public function setExpiration($date)
    {
        $this->vencimiento = $date;
    }

    /**
     * 
     * @param string $codpago
     */
    public function setPaymentMethod($codpago)
    {
        $formaPago = new FormaPago();
        if ($formaPago->loadFromCode($codpago)) {
            $this->codpago = $codpago;
            $this->pagado = $formaPago->pagado;
            $this->setExpiration($formaPago->getExpiration($this->fecha));
        }
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->observaciones = $this->toolBox()->utils()->noHtml($this->observaciones);

        /// check payment date
        if ($this->pagado === false) {
            $this->fechapago = null;
        } elseif (empty($this->fechapago)) {
            $this->fechapago = date(self::DATE_STYLE);
        }

        /// check expiration date
        if (strtotime($this->vencimiento) < strtotime($this->fecha)) {
            $this->vencimiento = $this->fecha;
        }

        return parent::test();
    }

    /**
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'importe':
                return $this->previousData['pagado'] ? false : true;

            case 'pagado':
                $this->newPayment();
                return true;

            default:
                return parent::onChange($field);
        }
    }

    protected function onDelete()
    {
        $this->updateInvoice();
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (parent::saveInsert($values)) {
            if ($this->pagado) {
                $this->newPayment();
                $this->updateInvoice();
            }

            return true;
        }

        return false;
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveUpdate(array $values = [])
    {
        if (parent::saveUpdate($values)) {
            $this->updateInvoice();
            return true;
        }

        return false;
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        parent::setPreviousData(array_merge(['importe', 'pagado'], $fields));
    }

    protected function updateInvoice()
    {
        $paidAmount = 0.0;
        $invoice = $this->getInvoice();
        foreach ($invoice->getReceipts() as $receipt) {
            if ($receipt->pagado) {
                $paidAmount += $receipt->importe;
            }
        }

        $paid = $paidAmount == $invoice->total;
        if ($invoice->exists() && $invoice->pagada != $paid) {
            $invoice->pagada = $paid;
            $invoice->save();
        }
    }
}
