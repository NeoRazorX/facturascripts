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
namespace FacturaScripts\Core\Model\Base;

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

    /**
     *
     * @var string
     */
    public $coddivisa;

    /**
     *
     * @var string
     */
    public $codigofactura;

    /**
     *
     * @var bool
     */
    protected $disableInvoiceUpdate = false;

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

    abstract public function getPayments();

    abstract protected function newPayment();

    public function clear()
    {
        parent::clear();

        $appSettings = $this->toolBox()->appSettings();
        $this->coddivisa = $appSettings->get('default', 'coddivisa');
        $this->codpago = $appSettings->get('default', 'codpago');
        $this->fecha = \date(self::DATE_STYLE);
        $this->idempresa = $appSettings->get('default', 'idempresa');
        $this->importe = 0.0;
        $this->liquidado = 0.0;
        $this->numero = 1;
        $this->pagado = false;
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        foreach ($this->getPayments() as $pay) {
            if (false === $pay->delete()) {
                $this->toolBox()->i18nLog()->warning('cant-remove-payment');
                return false;
            }
        }

        return parent::delete();
    }

    /**
     * 
     * @param bool $disable
     */
    public function disableInvoiceUpdate($disable = true)
    {
        $this->disableInvoiceUpdate = $disable;
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

        /// set invoice code
        if (empty($this->codigofactura)) {
            $this->codigofactura = $this->getInvoice()->codigo;
        }

        /// check payment date
        if ($this->pagado === false) {
            $this->fechapago = null;
        } elseif (empty($this->fechapago)) {
            $this->fechapago = \date(self::DATE_STYLE);
        }

        /// check expiration date
        if (\strtotime($this->vencimiento) < \strtotime($this->fecha)) {
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
        parent::onDelete();
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
        parent::setPreviousData(\array_merge(['importe', 'pagado'], $fields));
    }

    protected function updateInvoice()
    {
        if ($this->disableInvoiceUpdate) {
            return;
        }

        $invoice = $this->getInvoice();
        $paid = $invoice->pagada;

        $generator = new ReceiptGenerator();
        $generator->update($invoice);
        if ($invoice->exists() && $invoice->pagada != $paid) {
            $invoice->save();
        }
    }
}
